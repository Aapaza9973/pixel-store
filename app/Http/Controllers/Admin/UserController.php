<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\LogAuditoria;
use App\Models\User;
use App\Services\AuditoriaService;
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
        $this->authorizeResource(User::class, 'user');
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

            $user = User::create($datos);
            $user->syncRoles($request->validated('roles'));
        });

        return redirect()
            ->route('admin.usuarios.index')
            ->with('success', 'Usuario creado exitosamente.');
    }

    /**
     * Muestra el detalle del usuario y sus últimos 20 accesos.
     */
    public function show(User $user): View
    {
        $user->load('roles');

        $ultimosAccesos = LogAuditoria::query()
            ->where('user_id', $user->id)
            ->latest('id')
            ->limit(20)
            ->get();

        return view('admin.usuarios.show', [
            'usuario' => $user,
            'ultimosAccesos' => $ultimosAccesos,
            'accesos' => $ultimosAccesos,
            'logs' => $ultimosAccesos,
        ]);
    }

    /**
     * Muestra el formulario para editar el usuario especificado.
     */
    public function edit(User $user): View
    {
        $roles = Role::orderBy('name')->get();
        $rolesAsignados = $user->roles()->pluck('name')->all();

        return view('admin.usuarios.edit', [
            'usuario' => $user,
            'roles' => $roles,
            'rolesAsignados' => $rolesAsignados,
        ]);
    }

    /**
     * Actualiza el usuario especificado en la base de datos.
     */
    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        // Estado de roles previo a la edición (syncRoles no dispara eventos Eloquent).
        $rolesAntes = $user->getRoleNames()->sort()->values()->all();

        DB::transaction(function () use ($request, $user): void {
            $datos = $request->safe()->except(['roles', 'password']);

            if ($request->filled('password')) {
                $datos['password'] = Hash::make($request->validated('password'));
            }

            $user->update($datos);
            $user->syncRoles($request->validated('roles'));
        });

        $user->refresh();
        $rolesDespues = $user->getRoleNames()->sort()->values()->all();

        if ($rolesAntes !== $rolesDespues) {
            app(AuditoriaService::class)->registrarCambioRoles($user, $rolesAntes, $rolesDespues);
        }

        return redirect()
            ->route('admin.usuarios.index')
            ->with('success', 'Usuario actualizado exitosamente.');
    }

    /**
     * Elimina el usuario especificado.
     */
    public function destroy(User $user): RedirectResponse
    {
        try {
            DB::transaction(function () use ($user): void {
                $user->delete();
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
    public function desactivar(User $user): RedirectResponse
    {
        $this->authorize('desactivar', $user);

        DB::transaction(function () use ($user): void {
            $user->update(['activo' => false]);
        });

        return redirect()
            ->route('admin.usuarios.index')
            ->with('success', "Usuario '{$user->name}' desactivado exitosamente.");
    }

    /**
     * Activa la cuenta del usuario especificado.
     */
    public function activar(User $user): RedirectResponse
    {
        $this->authorize('update', $user);

        DB::transaction(function () use ($user): void {
            $user->update(['activo' => true]);
        });

        return redirect()
            ->route('admin.usuarios.index')
            ->with('success', "Usuario '{$user->name}' activado exitosamente.");
    }

    /**
     * Envía el correo para restablecer la contraseña del usuario especificado.
     */
    public function resetPassword(User $user): RedirectResponse
    {
        $this->authorize('update', $user);

        $status = Password::sendResetLink(['email' => $user->email]);

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
    public function historial(User $user): View
    {
        $this->authorize('view', $user);

        $logs = LogAuditoria::query()
            ->where('user_id', $user->id)
            ->latest('id')
            ->paginate(20)
            ->withQueryString();

        return view('admin.usuarios.historial', [
            'usuario' => $user,
            'logs' => $logs,
            'historial' => $logs,
        ]);
    }

    /**
     * Alias de historial para compatibilidad de nomenclatura.
     */
    public function historialAccesos(User $user): View
    {
        return $this->historial($user);
    }
}
