<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Autorización de descuento por un administrador (HU-5.11).
 *
 * El email y la contraseña son las credenciales del ADMIN, no del usuario
 * autenticado: el vendedor sigue siendo quien solicita. Nunca se inicia sesión
 * con estas credenciales (ver VentaController::autorizarDescuento).
 */
class AutorizarDescuentoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('crear ventas') ?? false;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'exists:users,email'],
            'password' => ['required', 'string', 'min:8'],
            'operacion_id' => ['required', 'uuid'],
            'descuento_porcentaje' => ['required', 'numeric', 'min:0', 'max:100'],
            'monto_descuento' => ['required', 'numeric', 'min:0.01'],
            'motivo' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.required' => 'Ingresá el email del administrador.',
            'email.email' => 'El email del administrador no es válido.',
            'email.exists' => 'No existe un usuario con ese email.',
            'password.required' => 'Ingresá la contraseña del administrador.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'operacion_id.required' => 'Falta la operación que se está autorizando.',
            'operacion_id.uuid' => 'La operación no es válida.',
            'descuento_porcentaje.required' => 'Falta el porcentaje de descuento.',
            'descuento_porcentaje.max' => 'El descuento no puede superar el 100 %.',
            'monto_descuento.required' => 'Falta el monto del descuento.',
            'monto_descuento.min' => 'El monto del descuento debe ser mayor que cero.',
            'motivo.max' => 'El motivo no puede superar los 500 caracteres.',
        ];
    }
}
