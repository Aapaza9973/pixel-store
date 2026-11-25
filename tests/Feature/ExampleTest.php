<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_la_home_sirve_la_tienda_publica(): void
    {
        $response = $this->get('/');

        $response->assertOk();
        $response->assertSee('PIXEL STORE');
        $response->assertSee('Ver catálogo');
    }
}
