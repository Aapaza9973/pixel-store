<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Http\Requests\Admin\StoreUserRequest;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class StoreUserRequestTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    /**
     * Retorna una carga base válida para la validación.
     *
     * @return array<string, mixed>
     */
    private function datosValidos(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Carlos Gomez',
            'email' => 'carlos.gomez@pixelstore.com',
            'password' => 'Password123',
            'password_confirmation' => 'Password123',
            'telefono' => '70012345',
            'nit_ci' => '1234567',
            'roles' => ['Vendedor'],
            'activo' => true,
        ], $overrides);
    }

    /**
     * Ejecuta el validador con las reglas y mensajes de StoreUserRequest.
     */
    private function ejecutarValidador(array $data): \Illuminate\Validation\Validator
    {
        $request = new StoreUserRequest;

        return Validator::make($data, $request->rules(), $request->messages());
    }

    public function test_valida_datos_correctos(): void
    {
        $validator = $this->ejecutarValidador($this->datosValidos());

        $this->assertTrue($validator->passes());
        $this->assertEmpty($validator->errors()->all());
    }

    public function test_rechaza_email_duplicado_con_mensaje_especifico(): void
    {
        $admin = User::where('email', 'admin@pixelstore.com')->firstOrFail();

        $validator = $this->ejecutarValidador($this->datosValidos([
            'email' => $admin->email,
        ]));

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('email'));
        $this->assertSame('El email ya está registrado.', $validator->errors()->first('email'));
    }

    public function test_rechaza_password_debil(): void
    {
        // Sin mayúscula
        $valSinMayuscula = $this->ejecutarValidador($this->datosValidos([
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]));
        $this->assertTrue($valSinMayuscula->fails());
        $this->assertSame(
            'La contraseña debe incluir al menos una mayúscula y un número.',
            $valSinMayuscula->errors()->first('password')
        );

        // Sin número
        $valSinNumero = $this->ejecutarValidador($this->datosValidos([
            'password' => 'PasswordABC',
            'password_confirmation' => 'PasswordABC',
        ]));
        $this->assertTrue($valSinNumero->fails());
        $this->assertSame(
            'La contraseña debe incluir al menos una mayúscula y un número.',
            $valSinNumero->errors()->first('password')
        );

        // Menos de 8 caracteres
        $valCorta = $this->ejecutarValidador($this->datosValidos([
            'password' => 'Pass1',
            'password_confirmation' => 'Pass1',
        ]));
        $this->assertTrue($valCorta->fails());
        $this->assertSame(
            'La contraseña debe tener al menos 8 caracteres.',
            $valCorta->errors()->first('password')
        );

        // Confirmación no coincide
        $valNoCoincide = $this->ejecutarValidador($this->datosValidos([
            'password' => 'Password123',
            'password_confirmation' => 'Password456',
        ]));
        $this->assertTrue($valNoCoincide->fails());
        $this->assertSame(
            'Las contraseñas no coinciden.',
            $valNoCoincide->errors()->first('password')
        );
    }

    public function test_rechaza_sin_roles(): void
    {
        // Sin campo roles
        $valSinCampo = $this->ejecutarValidador($this->datosValidos([
            'roles' => null,
        ]));
        $this->assertTrue($valSinCampo->fails());
        $this->assertTrue($valSinCampo->errors()->has('roles'));
        $this->assertSame('Debe asignar al menos un rol.', $valSinCampo->errors()->first('roles'));

        // Array vacío de roles
        $valArrayVacio = $this->ejecutarValidador($this->datosValidos([
            'roles' => [],
        ]));
        $this->assertTrue($valArrayVacio->fails());
        $this->assertTrue($valArrayVacio->errors()->has('roles'));
        $this->assertSame('Debe asignar al menos un rol.', $valArrayVacio->errors()->first('roles'));
    }

    public function test_rechaza_rol_inexistente(): void
    {
        $validator = $this->ejecutarValidador($this->datosValidos([
            'roles' => ['RolSuperInventado'],
        ]));

        $this->assertTrue($validator->fails());
        $this->assertTrue($validator->errors()->has('roles.0'));
        $this->assertSame('El rol seleccionado no es válido.', $validator->errors()->first('roles.0'));
    }

    public function test_autorizacion_según_permiso_crear_usuarios(): void
    {
        $admin = User::where('email', 'admin@pixelstore.com')->firstOrFail();
        $vendedor = User::where('email', 'vendedor@pixelstore.com')->firstOrFail();

        $requestAdmin = new StoreUserRequest;
        $requestAdmin->setUserResolver(fn () => $admin);
        $this->assertTrue($requestAdmin->authorize());

        $requestVendedor = new StoreUserRequest;
        $requestVendedor->setUserResolver(fn () => $vendedor);
        $this->assertFalse($requestVendedor->authorize());
    }
}
