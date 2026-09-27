# 08 — Despliegue

Guía para llevar el sistema a producción.

---

## 🎯 Requisitos de producción

| Recurso | Mínimo | Recomendado |
|---|---|---|
| VPS | 2 GB RAM, 2 vCPU | 4 GB RAM, 2 vCPU |
| Disco | 20 GB SSD | 40 GB SSD |
| Ancho de banda | 1 TB/mes | 2 TB/mes |
| SSL | Let's Encrypt | Let's Encrypt |
| Dominio | `pixelstore.com` (o similar) | — |

**Proveedores sugeridos**: DigitalOcean, Vultr, Linode, Hetzner.

---

## 🏗️ Stack en producción

```
[ Internet ]
    │
    ▼
[ Nginx ] ── HTTPS con Let's Encrypt
    │
    ▼
[ PHP-FPM 8.3 ]
    │
    ▼
[ PostgreSQL 18 ]
    │
    ▼
[ Redis ] (caché, sesiones, colas)
```

---

## 🚀 Despliegue paso a paso

### 1. Preparar el VPS

```bash
# Actualizar sistema
apt update && apt upgrade -y

# Instalar dependencias
apt install -y nginx php8.3-fpm php8.3-pgsql php8.3-mbstring \
    php8.3-xml php8.3-curl php8.3-zip php8.3-bcmath \
    postgresql-18 redis-server git unzip nodejs npm

# Instalar Composer
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer
```

### 2. Crear usuario de la app

```bash
useradd -m -s /bin/bash pixelstore
usermod -aG www-data pixelstore
```

### 3. Clonar el repositorio

```bash
su - pixelstore
git clone https://github.com/Aapaza9973/pixel-store.git
cd pixel-store
```

### 4. Instalar dependencias

```bash
# PHP (sin dev)
composer install --no-dev --optimize-autoloader

# Node
npm ci
npm run build

# Limpiar node_modules
rm -rf node_modules
```

### 5. Configurar `.env` de producción

```env
APP_NAME="Pixel Store"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://pixelstore.com
APP_TIMEZONE=America/La_Paz

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_DATABASE=pixel_store
DB_USERNAME=pixelstore
DB_PASSWORD=...contraseña_fuerte...

CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1

MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=notificaciones@pixelstore.com
MAIL_PASSWORD=...app_password...
MAIL_ENCRYPTION=tls
```

### 6. Configurar PostgreSQL

```bash
sudo -u postgres psql

CREATE USER pixelstore WITH PASSWORD '...';
CREATE DATABASE pixel_store OWNER pixelstore;
GRANT ALL PRIVILEGES ON DATABASE pixel_store TO pixelstore;
\q
```

### 7. Cargar schema + migraciones

```bash
php artisan migrate --force
php artisan db:seed --class=RoleSeeder --force
# (opcional) php artisan db:seed --class=CategoriaSeeder --force
```

### 8. Optimizar cachés

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

### 9. Configurar permisos

```bash
chmod -R 775 storage bootstrap/cache
chown -R pixelstore:www-data .
```

### 10. Configurar Nginx

Crea `/etc/nginx/sites-available/pixel-store`:

```nginx
server {
    listen 80;
    server_name pixelstore.com www.pixelstore.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name pixelstore.com www.pixelstore.com;

    root /home/pixelstore/pixel-store/public;
    index index.php;

    ssl_certificate /etc/letsencrypt/live/pixelstore.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/pixelstore.com/privkey.pem;

    add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

Activa el sitio:

```bash
ln -s /etc/nginx/sites-available/pixel-store /etc/nginx/sites-enabled/
nginx -t
systemctl reload nginx
```

### 11. SSL con Let's Encrypt

```bash
apt install -y certbot python3-certbot-nginx
certbot --nginx -d pixelstore.com -d www.pixelstore.com
```

### 12. Configurar el scheduler

Edita `crontab` del usuario `pixelstore`:

```bash
crontab -e
```

Agrega:

```cron
* * * * * cd /home/pixelstore/pixel-store && php artisan schedule:run >> /dev/null 2>&1
```

### 13. Configurar la cola de trabajo

Crea `/etc/supervisor/conf.d/pixel-store-worker.conf`:

```ini
[program:pixel-store-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /home/pixelstore/pixel-store/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
user=pixelstore
numprocs=2
redirect_stderr=true
stdout_logfile=/home/pixelstore/pixel-store/storage/logs/worker.log
stopwaitsecs=3600
```

Activa:

```bash
supervisorctl reread
supervisorctl update
supervisorctl start pixel-store-worker:*
```

---

## 💾 Backups automáticos

El comando `backup:database` está programado diario a las 02:00.

### Configuración en `.env`

```env
BACKUP_MYSQLDUMP_PATH=/usr/bin/pg_dump
BACKUP_GZIP_PATH=/usr/bin/gzip
BACKUP_NOTIFY_EMAIL=admin@pixelstore.com
```

### Backup manual

```bash
php artisan backup:database
```

### Restaurar un backup

```bash
# Descomprimir
gunzip backup_20260927_020000.sql.gz

# Restaurar
psql -U pixelstore -h 127.0.0.1 -d pixel_store -f backup_20260927_020000.sql
```

---

## 🔄 Actualizar la app

```bash
cd /home/pixelstore/pixel-store

# 1. Poner en mantenimiento
php artisan down --retry=60

# 2. Traer cambios
git pull origin main

# 3. Instalar dependencias nuevas
composer install --no-dev --optimize-autoloader
npm ci && npm run build && rm -rf node_modules

# 4. Migrar
php artisan migrate --force

# 5. Optimizar
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 6. Levantar
php artisan up
```

O usa un script `deploy.sh`:

```bash
#!/bin/bash
set -e
cd /home/pixelstore/pixel-store
php artisan down --retry=60
git pull origin main
composer install --no-dev --optimize-autoloader
npm ci && npm run build && rm -rf node_modules
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan queue:restart
php artisan up
```

---

## 📊 Monitoreo

| Aspecto | Herramienta |
|---|---|
| Uptime | UptimeRobot, Better Uptime |
| Errores | Sentry |
| Performance | Laravel Telescope (solo dev) |
| Logs | `storage/logs/laravel.log` |
| Métricas VPS | Netdata, htop |

---

## 🚨 Rollback

Si algo falla después del deploy:

```bash
# 1. Revertir último commit
git revert HEAD

# 2. Reinstalar
composer install --no-dev

# 3. Limpiar caché
php artisan optimize:clear

# 4. Reconstruir cachés
php artisan config:cache route:cache view:cache
```

---

## ✅ Checklist de go-live

- [ ] `APP_ENV=production` y `APP_DEBUG=false`
- [ ] HTTPS con SSL válido
- [ ] Base de datos con usuario dedicado (no postgres)
- [ ] Redis configurado
- [ ] Cola de trabajo con supervisor
- [ ] Cron del scheduler activo
- [ ] Backups automáticos verificados
- [ ] Logs rotando (logrotate)
- [ ] Monitoreo con Sentry + UptimeRobot
- [ ] Firewall configurado (UFW: 22, 80, 443)
- [ ] Fail2ban en SSH
- [ ] Actualizaciones de seguridad automáticas

---

## 🔐 Seguridad adicional

```bash
# Firewall
ufw allow 22/tcp
ufw allow 80/tcp
ufw allow 443/tcp
ufw enable

# Fail2ban
apt install -y fail2ban
systemctl enable fail2ban

# Deshabilitar login root por SSH
# En /etc/ssh/sshd_config:
PermitRootLogin no
PasswordAuthentication no
```

---

## ➡️ Siguiente paso

- [09 — Roadmap](09-roadmap.md) para ver las próximas fases
- [01 — Instalación](01-instalacion.md) para el setup local
