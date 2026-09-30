<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class UpdateUserRequestTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    /**
     * Retorna una carga base válida para la actualización.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function datosValidos(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Carlos Gomez Actualizado',
            'email' => 'carlos.actualizado@pixelstore.com',
            'password' => null,
            'password_confirmation' => null,
            'telefono' => '70099999',
            'nit_ci' => '9876543',
            'roles' => ['Vendedor'],
            'activo' => true,
        ], $overrides);
    }

    /**
     * Instancia UpdateUserRequest con el parámetro de ruta 'usuario' configurado.
     */
    private function crearRequestConUsuario(mixed $usuario): UpdateUserRequest
    {
        $request = new UpdateUserRequest;

        $route = new Route('PUT', 'admin/usuarios/{usuario}', []);
        $route->bind(request());
        $route->setParameter('usuario', $usuario);

        $request->setRouteResolver(fn () => $route);

        return $request;
    }

    /**
     * Ejecuta el validador con las reglas y mensajes de UpdateUserRequest.
     *
     * @param  array<string, mixed>  $data
     */
    private function ejecutarValidador(array $data, mixed $usuario = null): \Illuminate\Validation\Validator
    {
        $usuario = $usuario ?? User::where('email', 'admin@pixelstore.com')->firstOrFail();
        $request = $this->crearRequestConUsuario($usuario);

        return Validator::make($data, $request->rules(), $request->messages());
    }

    public function test_valida_datos_correctos(): void
    {
        $admin = User::where('email', 'admin@pixelstore.com')->firstOrFail();
        $validator = $this->ejecutarValidador($this->datosValidos(), $admin);

        $this->assertTrue($validator->passes());
        $this->assertEmpty($validator->errors()->all());
    }

    public function test_permite_mismo_email_del_usuario(): void
    {
        $admin = User::where('email', 'admin@pixelstore.com')->firstOrFail();

        $validator = $this->ejecutarValidador($this->datosValidos([
            'email' => $admin->email,
        ]), $admin);

        $this->assertTrue($validator->passes());
        $this->assertFalse($validator->errors()->has('email'));
    }

    public function test_rechaza_email_de_otro_usuario(): void
    {
        $admin = User::where('email', 'admin@pixelstore.com')->firstOrFail();
        $vendedor = User::where('email', 'vendedor@pixelstore.com')->firstOrFail();

        // El vendedor intenta actualizar su email al email del admin
        $validator = $this->ejecutarValidador($this->datosValidos([
            'email' => $admin->email,
        ]), $vendedor);

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('email'));
        $this->assertSame('El email ya está registrado.', $validator->errors()->first('email'));
    }

    public function test_permite_password_vacio(): void
    {
        $admin = User::where('email', 'admin@pixelstore.com')->firstOrFail();

        // Sin campo password
        $datosSinPassword = $this->datosValidos();
        unset($datosSinPassword['password'], $datosSinPassword['password_confirmation']);
        $valSinPassword = $this->ejecutarValidador($datosSinPassword, $admin);
        $this->assertTrue($valSinPassword->passes());

        // Con password null
        $valPasswordNull = $this->ejecutarValidador($this->datosValidos([
            'password' => null,
            'password_confirmation' => null,
        ]), $admin);
        $this->assertTrue($valPasswordNull->passes());
    }

    public function test_rechaza_password_debil_si_se_envia(): void
    {
        $admin = User::where('email', 'admin@pixelstore.com')->firstOrFail();

        // Sin mayúscula
        $valSinMayuscula = $this->ejecutarValidador($this->datosValidos([
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]), $admin);
        $this->assertTrue($valSinMayuscula->fails());
        $this->assertSame(
            'La contraseña debe incluir al menos una mayúscula y un número.',
            $valSinMayuscula->errors()->first('password')
        );

        // Sin número
        $valSinNumero = $this->ejecutarValidador($this->datosValidos([
            'password' => 'PasswordABC',
            'password_confirmation' => 'PasswordABC',
        ]), $admin);
        $this->assertTrue($valSinNumero->fails());
        $this->assertSame(
            'La contraseña debe incluir al menos una mayúscula y un número.',
            $valSinNumero->errors()->first('password')
        );

        // Menos de 8 caracteres
        $valCorta = $this->ejecutarValidador($this->datosValidos([
            'password' => 'Pass1',
            'password_confirmation' => 'Pass1',
        ]), $admin);
        $this->assertTrue($valCorta->fails());
        $this->assertSame(
            'La contraseña debe tener al menos 8 caracteres.',
            $valCorta->errors()->first('password')
        );

        // Confirmación no coincide
        $valNoCoincide = $this->ejecutarValidador($this->datosValidos([
            'password' => 'Password123',
            'password_confirmation' => 'Password456',
        ]), $admin);
        $this->assertTrue($valNoCoincide->fails());
        $this->assertSame(
            'Las contraseñas no coinciden.',
            $valNoCoincide->errors()->first('password')
        );
    }

    public function test_autorizacion_segun_permiso_editar_usuarios(): void
    {
        $admin = User::where('email', 'admin@pixelstore.com')->firstOrFail();
        $vendedor = User::where('email', 'vendedor@pixelstore.com')->firstOrFail();

        $requestAdmin = new UpdateUserRequest;
        $requestAdmin->setUserResolver(fn () => $admin);
        $this->assertTrue($requestAdmin->authorize());

        $requestVendedor = new UpdateUserRequest;
        $requestVendedor->setUserResolver(fn () => $vendedor);
        $this->assertFalse($requestVendedor->authorize());
    }
}
