# 🖥️ Pixel Store — Sistema de Ventas e Inventario

![Laravel](https://img.shields.io/badge/Laravel-13-FF2D20?style=flat-square&logo=laravel&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-8.3-777BB4?style=flat-square&logo=php&logoColor=white)
![PostgreSQL](https://img.shields.io/badge/PostgreSQL-18-4169E1?style=flat-square&logo=postgresql&logoColor=white)
![Tailwind](https://img.shields.io/badge/Tailwind-3-38B2AC?style=flat-square&logo=tailwind-css&logoColor=white)
![Tests](https://img.shields.io/badge/tests-96%20passing-success?style=flat-square)
![License](https://img.shields.io/badge/license-private-red?style=flat-square)

Sistema web de ventas, inventario y catálogo online para **Pixel Store**, tienda de computadoras, laptops, componentes y accesorios tecnológicos en La Paz, Bolivia.

<p align="center">
  <img src="public/images/logo/pixel-logo-horizontal.png" alt="Pixel Store" width="500">
</p>

<p align="center">
  <strong>Todo el mundo tecnológico, pixel a pixel.</strong>
</p>

---

## 📋 Descripción

Plataforma integral que digitaliza la operación completa de la tienda:

- 🛒 **Punto de venta (POS)** con validación de stock en tiempo real
- 📦 **Inventario** con atributos técnicos (socket, RAM, vatios, etc.)
- 🔍 **Trazabilidad** de números de serie vinculados a cliente para garantías
- 📄 **Cotizaciones formales** en PDF con validez configurable
- 🌐 **Catálogo online** con filtros avanzados
- 🧾 **Facturación electrónica** (SIN Bolivia)
- ⚙️ **Motor de compatibilidad** de componentes de PC
- 🎁 **Programa de fidelización** con puntos

---

## 🛠️ Stack Tecnológico

| Capa | Tecnología |
|---|---|
| Backend | PHP 8.3 + Laravel 13 |
| Frontend | Blade + Tailwind CSS + Alpine.js |
| Base de datos | PostgreSQL 18 |
| Build | Vite |
| Permisos | Spatie Laravel-Permission |
| PDF | DomPDF |
| Tests | PHPUnit 12 |

---

## 🚀 Instalación

### Requisitos

| Herramienta | Versión mínima | Versión probada |
|---|:-:|:-:|
| PHP | 8.3 | 8.3.30 |
| Composer | 2.6 | 2.8.x |
| Node.js | 20 | 22.x |
| npm | 10 | 10.x |
| PostgreSQL | 16 | 18.6 |
| Git | 2.40 | 2.4x |

### Pasos

```bash
# 1. Clonar el repositorio
git clone https://github.com/Aapaza9973/pixel-store.git
cd pixel-store

# 2. Instalar dependencias PHP y JS
composer install
npm install

# 3. Configurar entorno
cp .env.example .env
php artisan key:generate
```

**4. Configurar la base de datos** — edita `.env`:

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=pixel_store
DB_USERNAME=postgres
DB_PASSWORD=tu_password
```

**5. Crear la base de datos y cargar schema + seeders:**

```bash
# Linux / macOS
psql -U postgres -c "CREATE DATABASE pixel_store WITH ENCODING='UTF8' TEMPLATE=template0;"

# Windows (PowerShell, PostgreSQL 18)
& "C:\Program Files\PostgreSQL\18\bin\psql.exe" -U postgres `
    -c "CREATE DATABASE pixel_store WITH ENCODING='UTF8' TEMPLATE=template0;"

# Cargar schema, migraciones y datos demo
php artisan migrate:fresh --seed
```

> 💡 **Windows**: si `psql` no está en tu PATH, usa la ruta completa como en el ejemplo anterior. Alternativamente, crea la BD desde **pgAdmin** (Query Tool).

**6. Compilar assets:**

```bash
npm run build
```

**7. Levantar el servidor:**

```bash
php artisan serve
```

Acceder a **http://localhost:8000**

---

## 👤 Usuarios demo

Una vez corridos los seeders (`migrate:fresh --seed`), tendrás estos usuarios disponibles:

| Rol | Email | Password |
|---|---|---|
| 👑 Admin | `admin@pixelstore.com` | `password` |
| 💼 Vendedor | `vendedor@pixelstore.com` | `password` |
| 📦 Inventario | `inventario@pixelstore.com` | `password` |
| 💰 Cajero | `cajero@pixelstore.com` | `password` |

> ⚠️ **Solo para desarrollo**. En producción cambiar todas las contraseñas y crear usuarios reales.

---

## 🧪 Tests

```bash
# Correr toda la suite
php artisan test

# Solo un módulo específico
php artisan test --filter=ProductoTest

# Con cobertura (requiere xdebug)
php artisan test --coverage
```

**Estado actual**: ✅ **96 tests passing** (316 assertions)

| Módulo | Tests | Estado |
|---|:-:|:-:|
| Auth + Roles | 20 | ✅ |
| Productos (CRUD + filtros) | 18 | ✅ |
| InventoryService | 14 | ✅ |
| Stock y ubicaciones | 14 | ✅ |
| Alertas | 8 | ✅ |
| Ubicaciones (almacén) | 6 | ✅ |
| Dashboard | 2 | ✅ |
| Otros (perfil, sesión, ejemplo) | 14 | ✅ |

---

## 📊 Estado del proyecto

| Sprint | Módulo | Estado |
|:-:|---|:-:|
| **1** | Inventario completo (productos, categorías, marcas, atributos técnicos, ubicaciones, alertas) | ✅ **100%** |
| **2** | POS, Cotizaciones, Números de serie | 🚧 En desarrollo |
| **3** | Catálogo público, Pedidos online | ⏳ Pendiente |
| **4** | Facturación electrónica SIN, Reportes avanzados | ⏳ Pendiente |
| **5** | Motor de compatibilidad, IA | ⏳ Pendiente |
