<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\Dinero;
use PHPUnit\Framework\TestCase;

class DineroTest extends TestCase
{
    public function test_formatea_con_punto_de_miles_y_coma_decimal(): void
    {
        $this->assertSame('Bs 1.234,56', Dinero::formatear(1234.56));
        $this->assertSame('Bs 1.200,00', Dinero::formatear(1200));
    }

    public function test_redondea_a_dos_decimales(): void
    {
        $this->assertSame('Bs 0,01', Dinero::formatear(0.005));
        $this->assertSame('Bs 2,35', Dinero::formatear('2.345'));
    }

    public function test_valores_vacios_o_nulos_son_cero(): void
    {
        $this->assertSame('Bs 0,00', Dinero::formatear(null));
        $this->assertSame('Bs 0,00', Dinero::formatear(''));
        $this->assertSame('0,00', Dinero::numero(null));
    }

    public function test_numeros_grandes_mantienen_separador_de_miles(): void
    {
        $this->assertSame('Bs 1.234.567,89', Dinero::formatear(1234567.89));
    }
}
