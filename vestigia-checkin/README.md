# 📋 Vestigia CheckIn

Sistema de Control de Horarios Empresarial inspirado en Google Classroom.

---

## 🎨 Identidad Corporativa

**Paleta de Colores:**
- **Primario:** `#1B4332` (Verde Inglés / Oscuro)
- **Secundario:** `#FFFCF2` (Crema)
- **Acento:** `#660708` (Burdeos)

**Estilo:** Elegante, estilo biblioteca clásica con tipografía Serif para títulos y Sans-Serif para el cuerpo.

---

## 🚀 Instalación

### 1. Requisitos Previos

- **Servidor Web:** Apache/Nginx con PHP 7.4+
- **Base de Datos:** MySQL 5.7+ o MariaDB 10.3+
- **PHP Extensions:** PDO, PDO_MySQL, mbstring, openssl

### 2. Configuración de la Base de Datos

1. Crear la base de datos:
```bash
mysql -u root -p
CREATE DATABASE vestigia_checkin CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

2. Importar el esquema:
```bash
mysql -u root -p vestigia_checkin < database/schema.sql
```

### 3. Configuración del Sistema

1. Editar el archivo `config.php` con tus credenciales:

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'vestigia_checkin');
define('DB_USER', 'tu_usuario');
define('DB_PASS', 'tu_contraseña');
define('APP_URL', 'http://localhost/vestigia-checkin');
```

2. Asegurarse de que el servidor web tenga permisos de escritura en la carpeta de assets (para fotos de perfil):

```bash
chmod 755 assets/img/
```

### 4. Acceso al Sistema

**URL de acceso:** `http://localhost/vestigia-checkin`

**Usuario por defecto:**
- **Email:** `admin@vestigia.com`
- **Password:** `admin123`

⚠️ **IMPORTANTE:** Cambiar la contraseña del administrador después del primer acceso.

---

## 📂 Estructura del Proyecto

```
/vestigia-checkin
  /assets
    /css        → Estilos (style.css)
    /js         → Scripts JavaScript (main.js, fichaje.js)
    /img        → Imágenes y logos
  /pages
    - login.php      → Página de inicio de sesión
    - main.php       → Dashboard principal
    - fichaje.php    → Módulo de fichaje
    - horario.php    → Consulta de horarios
    - solicitudes.php → Gestión de solicitudes
    - informes.php   → Informes y estadísticas
    - perfil.php     → Perfil de usuario
  /includes
    - db.php         → Conexión a base de datos
    - auth.php       → Autenticación
    - funciones.php  → Funciones auxiliares
    - header.php     → Cabecera común
    - sidebar.php    → Menú lateral
  /api
    - fichar.php     → API de fichaje
    - proyectos.php  → API de proyectos
    - usuarios.php   → API de usuarios
    - informes.php   → API de informes
  /database
    - schema.sql     → Esquema de base de datos
  config.php         → Configuración principal
  index.php          → Punto de entrada
```

---

## 👥 Roles y Permisos

### 🔑 Superadmin (Dirección)
- Control total del sistema
- Gestión de empleados (alta/baja/archivo)
- Acceso a todos los informes
- Creación de proyectos
- Modificación de fichajes

### 👔 Admin_RRHH (RRHH)
- Gestión de empleados
- Modificación de fichajes
- Aprobación de vacaciones
- Acceso a informes globales

### 📊 Subadmin (Jefe de Departamento)
- Gestión de proyectos de su departamento
- Control de horarios de su equipo
- Aprobación de vacaciones de su equipo
- Informes de su departamento

### 👤 User (Empleado)
- Fichaje diario
- Consulta de horario
- Informes propios
- Gestión de solicitudes

---

## ⏰ Gestión de Horarios

### Jornadas Definidas:

| Tipo Jornada | Horario | Días |
|---|---|---|
| **Jornada Estándar** | 8:00 - 19:00 | Lunes - Viernes |
| **Intensiva Verano** | 8:00 - 16:00 | 1 jun - 1 sep |
| **Media Jornada (Mañana)** | 8:00 - 13:00 | Lunes - Viernes |
| **Media Jornada (Tarde)** | 13:00 - 19:00 | Lunes - Viernes |

### Criterios:

- **Retraso:** Se contabiliza a partir de 1 minuto tras la hora de entrada
- **Horas Extras:** Acumulación mensual basada en exceso sobre las 40h semanales
- **Cierre Automático:** Jornadas abiertas se cierran automáticamente a las 00:00

---

## 📋 Funcionalidades Principales

### 🕐 Módulo de Fichaje
- Selección de proyecto al iniciar jornada
- Marcador de Teletrabajo opcional
- Notificaciones automáticas por retrasos o falta de fichaje
- Edición de fichajes (solo Superadmin y RRHH)

### 📁 Gestión de Proyectos
- Creación por Dirección o Jefes de Departamento
- Proyectos interdepartamentales
- Asignación múltiple: Un empleado puede trabajar en varios proyectos

### 📊 Informes y Solicitudes
- **Informes:** Filtros temporales y exportación a PDF/Excel
- **Solicitudes:** Bajas, vacaciones, cambios de horario, teletrabajo
- **Vacaciones:** Fijadas en enero para todo el año

---

## 🛠️ Mantenimiento

### Backup de Base de Datos

```bash
mysqldump -u root -p vestigia_checkin > backup_$(date +%Y%m%d).sql
```

### Actualización del Sistema

1. Hacer backup de la base de datos
2. Hacer backup de los archivos
3. Reemplazar archivos (excepto `config.php`)
4. Ejecutar scripts de migración si existen

---

## 🔒 Seguridad

- **Contraseñas:** Cifradas con `password_hash()` (bcrypt)
- **CSRF Protection:** Tokens en todos los formularios
- **SQL Injection:** Prepared statements en todas las consultas
- **XSS Protection:** Escapado de salida con función `e()`
- **Session Security:** Configuración segura de sesiones PHP

---

## 📧 Soporte

Para reportar problemas o solicitar nuevas funcionalidades, contactar con el equipo de desarrollo.

---

## 📄 Licencia

Copyright © 2026 Vestigia. Todos los derechos reservados.