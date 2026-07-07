<?php

namespace App\Domain\Consultas\Services;

use RuntimeException;

class ProviderAdapterRegistry
{
    /**
     * @var ProviderAdapterInterface[]
     */
    private array $adapters = [];

    public function register(ProviderAdapterInterface $adapter): void
    {
        $this->adapters[] = $adapter;
    }

    public function resolve(string $providerCode): ProviderAdapterInterface
    {
        foreach ($this->adapters as $adapter) {
            if ($adapter->supports($providerCode)) {
                return $adapter;
            }
        }

        throw new RuntimeException("No adapter found for provider [{$providerCode}].");
    }
}
