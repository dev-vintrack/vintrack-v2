<?php

use App\Application\Consultas\Exceptions\ConsultationOperationException;
use App\Application\Consultas\Services\ConsultationService;
use App\Domain\Consultas\Services\ProviderAdapterInterface;
use App\Domain\Consultas\Services\ProviderAdapterRegistry;
use App\Domain\Consultas\ValueObjects\ConsultationRequest;
use App\Domain\Consultas\ValueObjects\ConsultationResponse;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;

require dirname(__DIR__, 2).'/vendor/autoload.php';

[$script, $database, $payloadPath, $worker, $barrier, $output] = $argv + array_fill(0, 6, null);
if ($database !== 'vintrack_sprint08_idempotency' || ! is_file($payloadPath) || ! in_array($worker, ['0', '1'], true)) {
    throw new RuntimeException('Unsafe worker arguments.');
}

putenv('APP_ENV=testing');
putenv('DB_CONNECTION=mysql');
putenv('DB_DATABASE='.$database);
putenv('MAIL_MAILER=array');
$_ENV['APP_ENV'] = 'testing';
$_ENV['DB_CONNECTION'] = 'mysql';
$_ENV['DB_DATABASE'] = $database;
$_ENV['MAIL_MAILER'] = 'array';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
$payload = json_decode(file_get_contents($payloadPath), true, 512, JSON_THROW_ON_ERROR);

$adapter = new class($payload['adapter_code'], $payload['suffix']) implements ProviderAdapterInterface
{
    public function __construct(private readonly string $adapterCode, private readonly string $counterKey) {}

    public function supports(string $adapterCode): bool
    {
        return $adapterCode === $this->adapterCode;
    }

    public function consult(ConsultationRequest $request): ConsultationResponse
    {
        DB::statement(
            'INSERT INTO sprint08_provider_calls (operation_key, calls, updated_at) VALUES (?, 1, NOW(6)) ON DUPLICATE KEY UPDATE calls = calls + 1, updated_at = NOW(6)',
            [$this->counterKey]
        );
        usleep(1500000);

        return new ConsultationResponse(true, 200, null, ['vin' => $request->value(), 'rawData' => []], 'fake-'.$this->counterKey, [
            'active_theft' => 0, 'open_lien' => 0, 'junk_salvage' => 0, 'odometer_issue' => 0,
        ]);
    }
};
$registry = new ProviderAdapterRegistry;
$registry->register($adapter);
$app->instance(ProviderAdapterRegistry::class, $registry);

$deadline = microtime(true) + 15;
while (! is_file($barrier) && microtime(true) < $deadline) {
    usleep(1000);
}

try {
    $result = $app->make(ConsultationService::class)->consult(
        $payload['user_id'], $payload['provider_id'], 'vin', $payload['value'],
        [$payload['service_code']], $payload['keys'][(int) $worker]
    );
    $answer = ['result' => 'COMPLETED', 'consultation_id' => $result->consultation()->id(), 'success' => $result->response()->success()];
} catch (ConsultationOperationException $exception) {
    $answer = ['result' => $exception->errorCode, 'message' => $exception->getMessage()];
} catch (Throwable $exception) {
    $answer = ['result' => 'ERROR', 'type' => $exception::class, 'message' => $exception->getMessage()];
}

file_put_contents($output, json_encode($answer, JSON_THROW_ON_ERROR), LOCK_EX);
