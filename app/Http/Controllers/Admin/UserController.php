<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\LogAuditoria;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(User::class, 'usuario');
    }

    /**
     * Listado de usuarios con filtros por término, rol y estado.
     */
    public function index(Request $request): View
    {
        $query = User::query()->with('roles');

        if ($request->filled('buscar')) {
            $query->buscar($request->string('buscar')->trim()->toString());
        }

        if ($request->filled('rol')) {
            $rol = $request->string('rol')->trim()->toString();
            $query->whereHas('roles', function ($q) use ($rol): void {
                $q->where('name', $rol);
            });
        }

        if ($request->filled('estado')) {
            $estado = $request->string('estado')->trim()->toString();
            if (in_array($estado, ['activos', 'activo', '1'], true)) {
                $query->activos();
            } elseif (in_array($estado, ['inactivos', 'inactivo', '0'], true)) {
                $query->inactivos();
            }
        }

        $usuarios = $query->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $roles = Role::orderBy('name')->get();

        return view('admin.usuarios.index', [
            'usuarios' => $usuarios,
            'roles' => $roles,
        ]);
    }

    /**
     * Muestra el formulario para crear un nuevo usuario.
     */
    public function create(): View
    {
        $roles = Role::orderBy('name')->get();

        return view('admin.usuarios.create', [
            'roles' => $roles,
        ]);
    }

    /**
     * Almacena un usuario recién creado en la base de datos.
     */
    public function store(StoreUserRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request): void {
            $datos = $request->safe()->except(['roles', 'password']);
            $datos['password'] = Hash::make($request->validated('password'));

            $usuario = User::create($datos);
            $usuario->syncRoles($request->validated('roles'));
        });

        return redirect()
            ->route('admin.usuarios.index')
            ->with('success', 'Usuario creado exitosamente.');
    }

    /**
     * Muestra el detalle del usuario y sus últimos 20 accesos.
     */
    public function show(User $usuario): View
    {
        $usuario->load('roles');

        $ultimosAccesos = LogAuditoria::query()
            ->where('user_id', $usuario->id)
            ->latest('id')
            ->limit(20)
            ->get();

        return view('admin.usuarios.show', [
            'usuario' => $usuario,
            'ultimosAccesos' => $ultimosAccesos,
            'accesos' => $ultimosAccesos,
            'logs' => $ultimosAccesos,
        ]);
    }

    /**
     * Muestra el formulario para editar el usuario especificado.
     */
    public function edit(User $usuario): View
    {
        $roles = Role::orderBy('name')->get();
        $rolesAsignados = $usuario->roles()->pluck('name')->all();

        return view('admin.usuarios.edit', [
            'usuario' => $usuario,
            'roles' => $roles,
            'rolesAsignados' => $rolesAsignados,
        ]);
    }

    /**
     * Actualiza el usuario especificado en la base de datos.
     */
    public function update(UpdateUserRequest $request, User $usuario): RedirectResponse
    {
        DB::transaction(function () use ($request, $usuario): void {
            $datos = $request->safe()->except(['roles', 'password']);

            if ($request->filled('password')) {
                $datos['password'] = Hash::make($request->validated('password'));
            }

            $usuario->update($datos);
            $usuario->syncRoles($request->validated('roles'));
        });

        return redirect()
            ->route('admin.usuarios.index')
            ->with('success', 'Usuario actualizado exitosamente.');
    }

    /**
     * Elimina el usuario especificado.
     */
    public function destroy(User $usuario): RedirectResponse
    {
        try {
            DB::transaction(function () use ($usuario): void {
                $usuario->delete();
            });

            return redirect()
                ->route('admin.usuarios.index')
                ->with('success', 'Usuario eliminado exitosamente.');
        } catch (QueryException $e) {
            return redirect()
                ->route('admin.usuarios.index')
                ->with('error', 'No se puede eliminar el usuario porque tiene registros asociados en el sistema.');
        }
    }

    /**
     * Desactiva la cuenta del usuario especificado.
     */
    public function desactivar(User $usuario): RedirectResponse
    {
        $this->authorize('desactivar', $usuario);

        DB::transaction(function () use ($usuario): void {
            $usuario->update(['activo' => false]);
        });

        return redirect()
            ->route('admin.usuarios.index')
            ->with('success', "Usuario '{$usuario->name}' desactivado exitosamente.");
    }

    /**
     * Activa la cuenta del usuario especificado.
     */
    public function activar(User $usuario): RedirectResponse
    {
        $this->authorize('update', $usuario);

        DB::transaction(function () use ($usuario): void {
            $usuario->update(['activo' => true]);
        });

        return redirect()
            ->route('admin.usuarios.index')
            ->with('success', "Usuario '{$usuario->name}' activado exitosamente.");
    }

    /**
     * Envía el correo para restablecer la contraseña del usuario especificado.
     */
    public function resetPassword(User $usuario): RedirectResponse
    {
        $this->authorize('update', $usuario);

        $status = Password::sendResetLink(['email' => $usuario->email]);

        if ($status === Password::RESET_LINK_SENT) {
            return redirect()
                ->route('admin.usuarios.index')
                ->with('success', 'Enlace de restablecimiento de contraseña enviado exitosamente.');
        }

        return redirect()
            ->route('admin.usuarios.index')
            ->with('error', 'No se pudo enviar el enlace de restablecimiento de contraseña.');
    }

    /**
     * Muestra el historial completo de accesos y auditoría del usuario.
     */
    public function historial(User $usuario): View
    {
        $this->authorize('view', $usuario);

        $logs = LogAuditoria::query()
            ->where('user_id', $usuario->id)
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.usuarios.historial', [
            'usuario' => $usuario,
            'logs' => $logs,
            'historial' => $logs,
        ]);
    }

    /**
     * Alias de historial para compatibilidad de nomenclatura.
     */
    public function historialAccesos(User $usuario): View
    {
        return $this->historial($usuario);
    }
}
