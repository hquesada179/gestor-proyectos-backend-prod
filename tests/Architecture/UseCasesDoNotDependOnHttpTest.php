<?php

namespace Tests\Architecture;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Tests\TestCase;

/**
 * Verifica que las capas internas (Domain y Application) de los contextos no
 * dependan de la capa HTTP, ni de capas externas (Infrastructure), y que Domain
 * permanezca completamente aislado de Eloquent y Laravel.
 */
class UseCasesDoNotDependOnHttpTest extends TestCase
{
    public function test_domain_layer_does_not_depend_on_orm_or_framework(): void
    {
        $domainFiles = $this->collectPhpFiles(app_path('Contexts/*/Domain'));

        $this->assertNotEmpty($domainFiles, 'No se encontraron archivos en Domain para verificar.');

        $forbiddenNamespaces = [
            'App\\Models\\',
            'Illuminate\\',
            'Laravel\\',
            'Illuminate\\Database\\Eloquent',
        ];

        // La regla de dependencia exige un núcleo Domain agnóstico del ORM y del framework
        foreach ($domainFiles as $file) {
            $contents = (string) file_get_contents($file);

            foreach ($forbiddenNamespaces as $namespace) {
                $this->assertStringNotContainsString(
                    $namespace,
                    $contents,
                    basename($file)." (Domain) no debe depender de {$namespace}."
                );
            }
        }
    }

    public function test_domain_and_application_layers_do_not_import_http_layer(): void
    {
        $forbiddenHttpNamespaces = [
            'Illuminate\\Http',
            'App\\Http',
            'Symfony\\Component\\HttpFoundation',
            'Illuminate\\Routing',
            'Illuminate\\Support\\Facades\\Request',
            'Illuminate\\Support\\Facades\\Response',
            'Illuminate\\Support\\Facades\\Redirect',
        ];

        $files = array_merge(
            $this->collectPhpFiles(app_path('Contexts/*/Domain')),
            $this->collectPhpFiles(app_path('Contexts/*/Application'))
        );

        $this->assertNotEmpty($files, 'No se encontraron archivos en Domain o Application para verificar.');

        foreach ($files as $file) {
            $contents = (string) file_get_contents($file);

            foreach ($forbiddenHttpNamespaces as $namespace) {
                $this->assertStringNotContainsString(
                    $namespace,
                    $contents,
                    basename($file)." no debe depender de {$namespace}."
                );
            }
        }
    }

    public function test_domain_and_application_do_not_depend_on_outer_layers(): void
    {
        $domainFiles = $this->collectPhpFiles(app_path('Contexts/*/Domain'));
        $applicationFiles = $this->collectPhpFiles(app_path('Contexts/*/Application'));

        $this->assertNotEmpty($domainFiles, 'No se encontraron archivos en Domain.');
        $this->assertNotEmpty($applicationFiles, 'No se encontraron archivos en Application.');

        // Domain es el núcleo interno: no puede importar Application ni Infrastructure
        foreach ($domainFiles as $file) {
            $contents = (string) file_get_contents($file);

            $this->assertStringNotContainsString(
                '\\Application\\',
                $contents,
                basename($file).' (Domain) no debe depender de Application.'
            );
            $this->assertStringNotContainsString(
                '\\Infrastructure\\',
                $contents,
                basename($file).' (Domain) no debe depender de Infrastructure.'
            );
        }

        // Application solo orquesta puertos de Domain: no puede importar Infrastructure
        foreach ($applicationFiles as $file) {
            $contents = (string) file_get_contents($file);

            $this->assertStringNotContainsString(
                '\\Infrastructure\\',
                $contents,
                basename($file).' (Application) no debe depender de Infrastructure.'
            );
        }
    }

    /**
     * Recorre recursivamente los directorios que coinciden con el patrón glob y
     * retorna todas las rutas de archivos PHP encontrados.
     *
     * @return list<string>
     */
    private function collectPhpFiles(string $pattern): array
    {
        $directories = glob($pattern, GLOB_ONLYDIR) ?: [];
        $phpFiles = [];

        foreach ($directories as $directory) {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($directory)
            );

            foreach ($iterator as $fileInfo) {
                if ($fileInfo->isFile() && $fileInfo->getExtension() === 'php') {
                    $phpFiles[] = $fileInfo->getPathname();
                }
            }
        }

        return $phpFiles;
    }
}
