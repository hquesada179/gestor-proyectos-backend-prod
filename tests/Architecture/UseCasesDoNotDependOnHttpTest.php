<?php

namespace Tests\Architecture;

use Tests\TestCase;

/**
 * Verifica que los casos de uso de los contextos no dependan de la capa HTTP.
 * La deuda de los puertos que aún referencian modelos Eloquent queda documentada
 * en ADR 005 y se corrige en una fase posterior.
 */
class UseCasesDoNotDependOnHttpTest extends TestCase
{
    public function test_use_cases_do_not_import_the_http_layer(): void
    {
        $files = glob(app_path('Contexts/*/Application/UseCases/*.php'));

        $this->assertNotEmpty($files, 'No se encontraron casos de uso para verificar.');

        foreach ($files as $file) {
            $contents = file_get_contents($file);

            $this->assertStringNotContainsString(
                'Illuminate\\Http',
                $contents,
                basename($file).' no debe depender de Illuminate\\Http.'
            );

            $this->assertStringNotContainsString(
                'App\\Http',
                $contents,
                basename($file).' no debe depender de App\\Http.'
            );
        }
    }
}
