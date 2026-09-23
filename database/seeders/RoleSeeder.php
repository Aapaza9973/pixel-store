<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permisos = [
            'ver productos', 'crear productos', 'editar productos', 'eliminar productos',
            'ver categorias', 'crear categorias', 'editar categorias', 'eliminar categorias',
            'ver marcas', 'crear marcas', 'editar marcas', 'eliminar marcas',
            'ver atributos', 'crear atributos', 'editar atributos', 'eliminar atributos',
            'ver ubicaciones', 'crear ubicaciones', 'editar ubicaciones', 'eliminar ubicaciones',
            'ver clientes', 'crear clientes', 'editar clientes', 'eliminar clientes',
            'ver ventas', 'crear ventas', 'editar ventas', 'cancelar ventas',
            'ver cotizaciones', 'crear cotizaciones', 'editar cotizaciones', 'eliminar cotizaciones',
            'ver numeros-serie', 'editar numeros-serie',
            'ver devoluciones', 'crear devoluciones', 'aprobar devoluciones', 'rechazar devoluciones',
            'ver caja', 'crear caja', 'ver todos los cierres',
            'ver reportes', 'exportar reportes',
            'ver proveedores', 'crear proveedores', 'editar proveedores', 'eliminar proveedores',
            'ver ordenes-compra', 'crear ordenes-compra', 'editar ordenes-compra', 'recibir ordenes-compra',
            'ver pedidos', 'confirmar pedidos', 'cancelar pedidos',
            'ver usuarios', 'crear usuarios', 'editar usuarios', 'eliminar usuarios',
            'ver respaldos', 'crear respaldos',
            'ver encuestas',
            'ver facturacion', 'emitir facturacion', 'anular facturacion',
            'ver auditoria',
            'usar compatibilidad',
            'autorizar descuentos',
        ];

        foreach ($permisos as $permiso) {
            Permission::firstOrCreate(['name' => $permiso, 'guard_name' => 'web']);
        }

        // Admin: todos los permisos
        $admin = Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
        $admin->syncPermissions(Permission::all());

        // Vendedor
        $vendedor = Role::firstOrCreate(['name' => 'Vendedor', 'guard_name' => 'web']);
        $vendedor->syncPermissions([
            'ver productos', 'ver clientes', 'crear clientes', 'editar clientes',
            'ver ventas', 'crear ventas',
            'ver cotizaciones', 'crear cotizaciones',
            'ver numeros-serie',
            'ver devoluciones', 'crear devoluciones',
            'ver caja', 'crear caja',
            'ver reportes', 'ver pedidos', 'confirmar pedidos',
            'usar compatibilidad',
        ]);

        // Cajero
        $cajero = Role::firstOrCreate(['name' => 'Cajero', 'guard_name' => 'web']);
        $cajero->syncPermissions([
            'ver productos', 'ver clientes', 'crear clientes', 'editar clientes',
            'ver ventas', 'crear ventas', 'ver caja', 'crear caja',
        ]);

        // Inventario
        $inventario = Role::firstOrCreate(['name' => 'Inventario', 'guard_name' => 'web']);
        $inventario->syncPermissions([
            'ver productos', 'crear productos', 'editar productos', 'eliminar productos',
            'ver categorias', 'crear categorias', 'editar categorias', 'eliminar categorias',
            'ver marcas', 'crear marcas', 'editar marcas', 'eliminar marcas',
            'ver atributos', 'crear atributos', 'editar atributos', 'eliminar atributos',
            'ver ubicaciones', 'crear ubicaciones', 'editar ubicaciones', 'eliminar ubicaciones',
            'ver clientes',
            'ver numeros-serie', 'editar numeros-serie',
            'ver reportes',
            'ver proveedores', 'crear proveedores', 'editar proveedores',
            'ver ordenes-compra', 'crear ordenes-compra', 'editar ordenes-compra', 'recibir ordenes-compra',
            'usar compatibilidad',
        ]);

        // Cliente
        $cliente = Role::firstOrCreate(['name' => 'Cliente', 'guard_name' => 'web']);
        $cliente->syncPermissions(['ver cotizaciones', 'usar compatibilidad']);

        // Usuarios demo
        $adminUser = User::firstOrCreate(
            ['email' => 'admin@pixelstore.com'],
            [
                'name' => 'Administrador Pixel Store',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'activo' => true,
            ]
        );
        $adminUser->assignRole('Admin');

        $vendedorUser = User::firstOrCreate(
            ['email' => 'vendedor@pixelstore.com'],
            [
                'name' => 'Laura Vendedora',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'activo' => true,
            ]
        );
        $vendedorUser->assignRole('Vendedor');

        $inventarioUser = User::firstOrCreate(
            ['email' => 'inventario@pixelstore.com'],
            [
                'name' => 'Miguel Inventario',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'activo' => true,
            ]
        );
        $inventarioUser->assignRole('Inventario');

        $cajeroUser = User::firstOrCreate(
            ['email' => 'cajero@pixelstore.com'],
            [
                'name' => 'Sofía Cajera',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'activo' => true,
            ]
        );
        $cajeroUser->assignRole('Cajero');

        $this->command->info('✅ Roles, permisos y usuarios demo creados.');
        $this->command->info('   Admin:      admin@pixelstore.com / password');
        $this->command->info('   Vendedor:   vendedor@pixelstore.com / password');
        $this->command->info('   Inventario: inventario@pixelstore.com / password');
        $this->command->info('   Cajero:     cajero@pixelstore.com / password');
    }
}
