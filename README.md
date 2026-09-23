# 🖥️ Pixel Store — Sistema de Ventas e Inventario

Sistema web de ventas, inventario y catálogo online para **Pixel Store**, tienda de computadoras, laptops, componentes y accesorios tecnológicos en La Paz, Bolivia.

> **Todo el mundo tecnológico, pixel a pixel.**

---

## 📋 Descripción

Plataforma integral que digitaliza la operación completa de la tienda:

- **Punto de venta (POS)** con validación de stock en tiempo real
- **Inventario** con atributos técnicos (socket, RAM, vatios, etc.)
- **Trazabilidad** de números de serie vinculados a cliente para garantías
- **Cotizaciones formales** en PDF con validez configurable
- **Catálogo online** con filtros avanzados
- **Facturación electrónica** (SIN Bolivia)
- **Motor de compatibilidad** de componentes de PC
- **Programa de fidelización** con puntos

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

- PHP 8.3+
- Composer 2.6+
- Node.js 20+
- PostgreSQL 16+
- Git

### Pasos

```bash
# 1. Clonar el repositorio
git clone https://github.com/TU_USUARIO/pixel-store.git
cd pixel-store

# 2. Instalar dependencias
composer install
npm install

# 3. Configurar entorno
cp .env.example .env
php artisan key:generate

# 4. Configurar base de datos en .env
# DB_CONNECTION=pgsql
# DB_DATABASE=pixel_store
# DB_USERNAME=postgres
# DB_PASSWORD=tu_password

# 5. Crear base de datos y cargar schema
psql -U postgres -c "CREATE DATABASE pixel_store WITH ENCODING='UTF8' TEMPLATE=template0;"
php artisan migrate:fresh --seed

# 6. Compilar assets
npm run build

# 7. Servidor
php artisan serve
