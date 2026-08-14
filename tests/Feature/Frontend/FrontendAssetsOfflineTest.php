<?php

namespace Tests\Feature\Frontend;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Tests\TestCase;

class FrontendAssetsOfflineTest extends TestCase
{
    public function test_critical_frontend_assets_are_versioned_local_and_datatables_is_executable_offline(): void
    {
        $root = public_path('vendor/vintrack');
        $required = [
            'jquery-3.7.1.min.js', 'bootstrap-5.3.2.min.css', 'bootstrap-5.3.2.bundle.min.js',
            'bootstrap-icons-1.11.2.min.css', 'fonts/bootstrap-icons.woff2',
            'datatables-1.13.6.min.js', 'datatables-1.13.6.bootstrap5.min.js',
            'datatables-1.13.6.bootstrap5.min.css', 'datatables-es-ES-1.13.6.json',
            'datatables-buttons-2.4.2.min.js', 'datatables-responsive-2.5.0.min.js',
            'jszip-3.10.1.min.js', 'pdfmake-0.2.7.min.js', 'pdfmake-0.2.7-vfs_fonts.js',
        ];
        foreach ($required as $file) {
            $this->assertFileExists($root.'/'.$file);
            $this->assertGreaterThan(1000, filesize($root.'/'.$file));
        }

        $jquery = file_get_contents($root.'/jquery-3.7.1.min.js');
        $dataTables = file_get_contents($root.'/datatables-1.13.6.min.js');
        $this->assertStringContainsString('jQuery v3.7.1', $jquery);
        $this->assertStringContainsString('DataTables 1.13.6', $dataTables);
        $this->assertStringContainsString('DataTable', $dataTables);
        $this->assertJson(file_get_contents($root.'/datatables-es-ES-1.13.6.json'));
    }

    public function test_application_views_have_no_runtime_dependency_on_migrated_cdns(): void
    {
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(resource_path('views')));
        $contents = '';
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php' && $file->getFilename() !== 'welcome.blade.php') {
                $contents .= file_get_contents($file->getPathname());
            }
        }

        foreach (['code.jquery.com', 'cdn.datatables.net', 'cdnjs.cloudflare.com', 'cdn.jsdelivr.net/npm/bootstrap'] as $origin) {
            $this->assertStringNotContainsString($origin, $contents);
        }
        $this->assertStringContainsString('vendor/vintrack/datatables-1.13.6.min.js', $contents);
    }
}
