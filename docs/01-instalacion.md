# 01 — Instalación

Guía paso a paso para levantar el proyecto en un entorno local.

---

## 📋 Requisitos

| Herramienta | Versión mínima | Versión probada | Notas |
|---|:-:|:-:|---|
| PHP | 8.3 | 8.3.30 | Requiere extensiones `pdo_pgsql`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `bcmath`, `fileinfo` |
| Composer | 2.6 | 2.8.x | Gestor de dependencias PHP |
| Node.js | 20 | 22.x | Para compilar assets |
| npm | 10 | 10.x | Gestor de paquetes JS |
| PostgreSQL | 16 | 18.6 | Recomendado 18 |
| Git | 2.40 | 2.4x | Control de versiones |

### Verificar extensiones PHP

```bash
php -m | grep -i pgsql
# Debe mostrar: pdo_pgsql y pgsql
```

Si faltan, edita `php.ini` y descomenta:
```ini
extension=pdo_pgsql
extension=pgsql
```

---

## 🚀 Instalación paso a paso

### 1. Clonar el repositorio

```bash
git clone https://github.com/Aapaza9973/pixel-store.git
cd pixel-store
```

### 2. Instalar dependencias

```bash
composer install
npm install
```

### 3. Configurar el entorno

```bash
cp .env.example .env
php artisan key:generate
```

### 4. Editar `.env` con los datos de tu PostgreSQL

```env
APP_NAME="Pixel Store"
APP_ENV=local
APP_DEBUG=true
APP_TIMEZONE=America/La_Paz
APP_LOCALE=es
APP_URL=http://localhost:8000

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=pixel_store
DB_USERNAME=postgres
DB_PASSWORD=tu_password
DB_SCHEMA=public
DB_SSLMODE=prefer

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
```

### 5. Crear la base de datos

**Linux / macOS:**
```bash
psql -U postgres -c "CREATE DATABASE pixel_store WITH ENCODING='UTF8' TEMPLATE=template0;"
```

**Windows (PowerShell):**
```powershell
& "C:\Program Files\PostgreSQL\18\bin\psql.exe" -U postgres `
    -c "CREATE DATABASE pixel_store WITH ENCODING='UTF8' TEMPLATE=template0;"
```

**Alternativa**: crear la BD desde **pgAdmin** → Query Tool.

### 6. Cargar el schema + migraciones + seeders

```bash
php artisan migrate:fresh --seed
```

**Resultado esperado:**
```
✅ Roles, permisos y usuarios demo creados.
✅ 11 categorías creadas.
✅ 19 marcas creadas.
✅ 21 atributos técnicos creados.
✅ 21 valores de atributo creados.
✅ 2 ubicaciones creadas.
✅ 15 productos demo con atributos y stock por ubicación.
✅ Alertas de stock generadas para productos bajo umbral.
```

### 7. Compilar assets

```bash
npm run build
```

### 8. Levantar el servidor

```bash
php artisan serve
```

Acceder a **http://localhost:8000**

---

## 👤 Usuarios demo

| Rol | Email | Password |
|---|---|---|
| 👑 Admin | `admin@pixelstore.com` | `password` |
| 💼 Vendedor | `vendedor@pixelstore.com` | `password` |
| 📦 Inventario | `inventario@pixelstore.com` | `password` |
| 💰 Cajero | `cajero@pixelstore.com` | `password` |

---

## 🧪 Verificar la instalación

```bash
# 1. Ver el estado de la BD
php artisan db:show

# 2. Ver las rutas registradas
php artisan route:list

# 3. Correr los tests
php artisan test
# Esperado: 96 passing

# 4. Formatear código
./vendor/bin/pint --dirty
```

---

## 🚨 Troubleshooting

### Error: `could not find driver`

**Causa**: PHP no tiene habilitada la extensión `pdo_pgsql`.

**Solución**:
1. Abre `php.ini` (busca con `php --ini`)
2. Descomenta:
   ```ini
   extension=pdo_pgsql
   extension=pgsql
   ```
3. Reinicia el servidor y la terminal

### Error: `psql no se reconoce como comando`

**Causa**: PostgreSQL no está en el PATH de Windows.

**Solución**: usa la ruta completa:
```powershell
& "C:\Program Files\PostgreSQL\18\bin\psql.exe" ...
```

O agrega al PATH:
```powershell
[Environment]::SetEnvironmentVariable(
    "Path",
    "C:\Program Files\PostgreSQL\18\bin;" + [Environment]::GetEnvironmentVariable("Path", "Machine"),
    "Machine"
)
```

### Error: `No application encryption key has been specified`

**Causa**: falta `APP_KEY` en `.env`.

**Solución**:
```bash
php artisan key:generate
```

### Error: `Route [admin.categorias.index] not defined`

**Causa**: cachés de rutas desactualizadas.

**Solución**:
```bash
php artisan optimize:clear
```

### Error: Tests fallan con `no such table`

**Causa**: la BD de tests (`pixel_store_test`) no existe o no tiene el schema.

**Solución**:
```bash
& "C:\Program Files\PostgreSQL\18\bin\psql.exe" -U postgres `
    -c "CREATE DATABASE pixel_store_test WITH ENCODING='UTF8' TEMPLATE=template0;"

& "C:\Program Files\PostgreSQL\18\bin\psql.exe" -U postgres `
    -d pixel_store_test -f "database\schema\pgsql-schema.sql"
```

---

## 🌐 Frontend (Vite)

### Modo desarrollo

```bash
# Terminal 1: servidor Laravel
php artisan serve

# Terminal 2: Vite con hot-reload
npm run dev
```

### Build de producción

```bash
npm run build
```

Los assets se generan en `public/build/`.

---

## 🐳 Alternativa con Docker (futuro)

Si en el futuro quieres dockerizar:

```yaml
# docker-compose.yml
services:
  app:
    build: .
    ports: ["8000:8000"]
    depends_on: [postgres]
  postgres:
    image: postgres:18-alpine
    environment:
      POSTGRES_DB: pixel_store
      POSTGRES_PASSWORD: secret
    volumes:
      - pgdata:/var/lib/postgresql/data
```

---

## ➡️ Siguiente paso

Una vez instalado, lee:
- [02 — Arquitectura](02-arquitectura.md) para entender el proyecto
- [03 — Base de datos](03-base-de-datos.md) para conocer el schema
- [07 — Testing](07-testing.md) para correr y escribir tests
