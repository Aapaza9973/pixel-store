<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    /**
     * Determina si el usuario está autorizado a realizar esta solicitud.
     */
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('editar usuarios');
    }

    /**
     * Reglas de validación para la edición de usuarios.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        $usuario = $this->route('usuario');
        $usuarioId = is_object($usuario) ? $usuario->id : $usuario;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($usuarioId)],
            'password' => ['nullable', 'string', 'min:8', 'confirmed', 'regex:/[A-Z]/', 'regex:/[0-9]/'],
            'telefono' => ['nullable', 'string', 'max:30'],
            'nit_ci' => ['nullable', 'string', 'max:20'],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => ['string', 'exists:roles,name'],
            'activo' => ['boolean'],
        ];
    }

    /**
     * Mensajes de error personalizados en español.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email' => 'Ingrese un correo electrónico válido.',
            'email.unique' => 'El email ya está registrado.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
            'password.regex' => 'La contraseña debe incluir al menos una mayúscula y un número.',
            'roles.required' => 'Debe asignar al menos un rol.',
            'roles.min' => 'Debe asignar al menos un rol.',
            'roles.*.exists' => 'El rol seleccionado no es válido.',
        ];
    }

    /**
     * Normalizar datos antes de validar.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'activo' => $this->boolean('activo'),
        ]);
    }
}
