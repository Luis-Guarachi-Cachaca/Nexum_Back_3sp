# Guía de Deployment en Railway - NEXUM API

## 📋 **Resumen del Proyecto:**

- **Framework:** Laravel 11 (PHP 8.2+)
- **Base de Datos:** PostgreSQL
- **Almacenamiento:** Cloudinary
- **Email:** Mailjet
- **Autenticación:** Laravel Sanctum
- **Autorización:** Spatie Laravel Permission
- **Auditoría:** Spatie Activitylog

---

## 🚀 **Paso 1: Crear Cuenta en Railway**

1. **Ir a:** https://railway.app/
2. **Registrarse:** Usar GitHub, Google o email
3. **Verificar email:** Confirmar cuenta
4. **Plan gratuito:** Railway ofrece $5/mes en créditos gratuitos

---

## 🚀 **Paso 2: Crear Nuevo Proyecto en Railway**

1. **Dashboard:** Click en "New Project"
2. **Origen:** Seleccionar "Deploy from GitHub repo"
3. **Conectar GitHub:** Autorizar Railway a acceder a tu repositorio
4. **Seleccionar repositorio:** Elegir `nexum-back`
5. **Branch:** Seleccionar `main` o `master`

---

## 🚀 **Paso 3: Configurar Base de Datos PostgreSQL**

### **3.1 Agregar Servicio PostgreSQL:**

1. **En el proyecto Railway:** Click en "New Service"
2. **Seleccionar:** "Database"
3. **Elegir:** "PostgreSQL"
4. **Plan:** Seleccionar plan gratuito (Hobby Dev)

### **3.2 Obtener Credenciales de PostgreSQL:**

1. **Click en el servicio PostgreSQL**
2. **Ir a:** "Variables" tab
3. **Copiar las siguientes variables:**
   - `DATABASE_URL` (URL completa de conexión)
   - `PGHOST` (host)
   - `PGPORT` (puerto)
   - `PGUSER` (usuario)
   - `PGPASSWORD` (contraseña)
   - `PGDATABASE` (nombre de la base de datos)

### **3.3 Configurar Variables de Base de Datos:**

En el servicio principal de Laravel, agregar estas variables:

```
DB_CONNECTION=pgsql
DB_URL=${DATABASE_URL}
DB_HOST=${PGHOST}
DB_PORT=${PGPORT}
DB_DATABASE=${PGDATABASE}
DB_USERNAME=${PGUSER}
DB_PASSWORD=${PGPASSWORD}
```

---

## 🚀 **Paso 4: Configurar Variables de Ambiente**

### **4.1 Variables Esenciales de Laravel:**

```
APP_ENV=production
APP_DEBUG=false
APP_URL=https://tu-proyecto.railway.app
APP_KEY=tu-app-key (generar con: php artisan key:generate)
```

### **4.2 Variables de Cloudinary:**

**Si ya tienes credenciales de Cloudinary:**
```
CLOUDINARY_URL=cloudinary://API_KEY:API_SECRET@CLOUD_NAME
```

**Si necesitas crear cuenta Cloudinary (gratis):**
1. Ir a: https://cloudinary.com/
2. Registrarte (plan gratuito)
3. En Dashboard → Settings → API Credentials
4. Copiar el formato: `cloudinary://API_KEY:API_SECRET@CLOUD_NAME`

### **4.3 Variables de Mailjet (Email):**

**Para la opción gratuita de Mailjet:**
1. Ir a: https://www.mailjet.com/
2. Registrarte (plan gratuito - 200 emails/mes)
3. En Account Settings → SMTP & Send API
4. Copiar API Key y Secret Key

**Variables a configurar:**
```
MAIL_MAILER=smtp
MAIL_HOST=in-v3.mailjet.com
MAIL_PORT=587
MAIL_USERNAME=tu-api-key-mailjet
MAIL_PASSWORD=tu-secret-key-mailjet
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=tu-email@ejemplo.com
MAIL_FROM_NAME="${APP_NAME}"
```

### **4.4 Variables de Cache y Sesión:**

```
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
```

### **4.5 Variables de Redis (Opcional - Mejora Performance):**

**Si quieres usar Redis en Railway:**
1. **Agregar servicio Redis:**
   - Click "New Service"
   - Seleccionar "Redis"
   - Plan gratuito

2. **Configurar variables:**
   - Click en servicio Redis
   - Copiar `REDIS_URL`
   - En servicio Laravel agregar: `REDIS_URL=${REDIS_URL}`

**Si NO quieres usar Redis (usar database):**
```
CACHE_DRIVER=database
SESSION_DRIVER=database
QUEUE_CONNECTION=database
```

---

## 🚀 **Paso 5: Configurar Build y Deploy**

### **5.1 Verificar Archivos de Configuración:**

**El proyecto ya tiene estos archivos:**
- `nixpacks.toml` - Configuración de build
- `Procfile` - Configuración de inicio

**Contenido actual de `nixpacks.toml`:**
```toml
[phases.build]
cmds = [
  "composer install --optimize-autoloader --no-dev",
  "php artisan storage:link --force",
  "php artisan config:cache",
  "php artisan view:cache"
]

[start]
cmd = "php artisan route:clear && php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=$PORT"
```

**Contenido actual de `Procfile`:**
```
web: php artisan migrate --force && php artisan storage:link --force && php artisan serve --host=0.0.0.0 --port=$PORT
```

### **5.2 Generar APP_KEY:**

**En tu local:**
```bash
php artisan key:generate
```

**Copiar el APP_KEY generado y agregarlo como variable en Railway.**

---

## 🚀 **Paso 6: Configurar GitHub Integration**

### **6.1 Preparar Repositorio:**

**Asegúrate de que tu repositorio tenga:**
- ✅ Todos los archivos del proyecto
- ✅ `.gitignore` configurado correctamente
- ✅ `composer.json` y `composer.lock`
- ✅ `nixpacks.toml` y `Procfile`
- ✅ NO incluir `.env` (se configura en Railway)

### **6.2 Push a GitHub:**

```bash
git add .
git commit -m "Ready for Railway deployment"
git push origin main
```

### **6.3 Railway Detectará Cambios:**

- Railway detectará el push automáticamente
- Iniciará el proceso de build
- Ejecutará las migraciones automáticamente

---

## 🚀 **Paso 7: Ejecutar Migraciones y Seeders**

### **7.1 Migraciones Automáticas:**

**El `Procfile` ya incluye:**
```bash
php artisan migrate --force
```

### **7.2 Ejecutar Seeders (Opcional):**

**Si quieres datos de prueba:**

**Opción A: Modificar Procfile:**
```
web: php artisan migrate --force && php artisan db:seed --force && php artisan storage:link --force && php artisan serve --host=0.0.0.0 --port=$PORT
```

**Opción B: Ejecutar manualmente en Railway:**
1. Click en servicio Laravel
2. Click en "Console" tab
3. Ejecutar: `php artisan db:seed --force`

---

## 🚀 **Paso 8: Verificar Deployment**

### **8.1 Revisar Logs:**

1. **Click en servicio Laravel**
2. **Ir a:** "Deployments" tab
3. **Verificar:** Build exitoso
4. **Click en deployment:** Ver logs detallados

### **8.2 Probar API:**

**Obtener URL del proyecto:**
1. En dashboard de Railway
2. Click en servicio Laravel
3. Copiar la URL (ej: `https://tu-proyecto.railway.app`)

**Probar endpoint:**
```bash
curl https://tu-proyecto.railway.app/api/v1/featured-profiles
```

**Expected:** JSON response con featured profiles

---

## 🔧 **Troubleshooting Común:**

### **Error: Database Connection Failed**

**Solución:**
1. Verificar variables de PostgreSQL en Railway
2. Asegurar que `DB_CONNECTION=pgsql`
3. Verificar que las variables estén correctamente referenciadas

### **Error: Cloudinary URL Missing**

**Solución:**
1. Agregar variable `CLOUDINARY_URL` en Railway
2. Formato: `cloudinary://API_KEY:API_SECRET@CLOUD_NAME`
3. Verificar credenciales en Cloudinary dashboard

### **Error: Mail Configuration Failed**

**Solución:**
1. Verificar variables de Mailjet
2. Asegurar que `MAIL_MAILER=smtp`
3. Verificar API Key y Secret Key de Mailjet

### **Error: Storage Link Failed**

**Solución:**
1. El `Procfile` ya incluye `php artisan storage:link --force`
2. Si falla, ejecutar manualmente en Railway Console

### **Error: Migration Failed**

**Solución:**
1. Verificar logs en Railway
2. Ejecutar `php artisan migrate:fresh --force` en Railway Console
3. Verificar conexión a base de datos

---

## 📊 **Costos del Plan Gratuito:**

### **Railway ($5/mes créditos):**
- **PostgreSQL:** ~$5/mes
- **Laravel App:** ~$5/mes
- **Redis (opcional):** ~$5/mes

**Total estimado:** $10-15/mes (puede variar según uso)

### **Servicios Externos (Gratis):**
- **Cloudinary:** Plan gratuito (25GB storage, 25GB bandwidth/mes)
- **Mailjet:** Plan gratuito (200 emails/mes)

---

## 🎯 **Variables de Ambiente Completas:**

### **Variables Obligatorias:**
```
APP_ENV=production
APP_DEBUG=false
APP_URL=https://tu-proyecto.railway.app
APP_KEY=tu-app-key-generado
DB_CONNECTION=pgsql
DB_URL=${DATABASE_URL}
DB_HOST=${PGHOST}
DB_PORT=${PGPORT}
DB_DATABASE=${PGDATABASE}
DB_USERNAME=${PGUSER}
DB_PASSWORD=${PGPASSWORD}
```

### **Variables de Cloudinary:**
```
CLOUDINARY_URL=cloudinary://API_KEY:API_SECRET@CLOUD_NAME
```

### **Variables de Mailjet:**
```
MAIL_MAILER=smtp
MAIL_HOST=in-v3.mailjet.com
MAIL_PORT=587
MAIL_USERNAME=tu-api-key-mailjet
MAIL_PASSWORD=tu-secret-key-mailjet
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=tu-email@ejemplo.com
MAIL_FROM_NAME="${APP_NAME}"
```

### **Variables de Cache/Sesión (sin Redis):**
```
CACHE_DRIVER=database
SESSION_DRIVER=database
QUEUE_CONNECTION=database
```

### **Variables de Cache/Sesión (con Redis):**
```
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
REDIS_URL=${REDIS_URL}
```

---

## ✅ **Checklist Final:**

- [ ] Cuenta Railway creada
- [ ] Proyecto Railway creado desde GitHub
- [ ] Servicio PostgreSQL agregado
- [ ] Variables de PostgreSQL configuradas
- [ ] APP_KEY generado y configurado
- [ ] CLOUDINARY_URL configurado
- [ ] Variables de Mailjet configuradas
- [ ] Variables de cache/sesión configuradas
- [ ] Repositorio GitHub actualizado
- [ ] Deployment exitoso en Railway
- [ ] Logs sin errores
- [ ] API probada y funcionando
- [ ] Migraciones ejecutadas
- [ ] Seeders ejecutados (opcional)

---

## 🚀 **Comandos Útiles en Railway Console:**

```bash
# Verificar migraciones
php artisan migrate:status

# Ejecutar seeders
php artisan db:seed --force

# Limpiar cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear

# Verificar conexión a base de datos
php artisan db:show

# Verificar variables de ambiente
php artisan env
```

---

## 📞 **Soporte:**

- **Railway Docs:** https://docs.railway.app/
- **Laravel Deployment:** https://laravel.com/docs/deployment
- **Cloudinary Docs:** https://cloudinary.com/documentation
- **Mailjet Docs:** https://dev.mailjet.com/

---

**Deployment listo para producción en Railway con plan gratuito.**
