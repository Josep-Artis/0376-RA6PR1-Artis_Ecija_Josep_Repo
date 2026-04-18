## 1. Stack Tecnológico
* **Lenguajes:** HTML5, CSS3, JavaScript (Vanilla).
* **Backend:** PHP.
* **Base de Datos:** MySQL.
* **Requisitos:** Sin frameworks, diseño responsive inspirado en Google Classroom.
* **Idioma:** Español.

---

## 2. Identidad Corporativa
La estética debe reflejar prestigio y conocimiento académico.

* **Paleta de Colores:**
    * **Primario:** `#1B4332` (Verde Inglés / Oscuro)
    * **Secundario:** `#FFFCF2` (Crema)
    * **Acento:** `#660708` (Burdeos)
* **Estilo Visual:** Elegante, estilo biblioteca clásica.
* **Tipografía:** Serif para títulos (autoridad) y Sans-Serif limpia para el cuerpo de texto (modernidad).

---

## 3. Estructura de Carpetas
```text
/vestigia-checkin
  /assets
    /css
    /js
    /img
  /pages
    - login.php
    - main.php
    - fichaje.php
    - horario.php
    - solicitudes.php
    - informes.php
    - perfil.php
  /includes
    - db.php
    - auth.php
    - funciones.php
  /api
    - fichar.php
    - proyectos.php
    - usuarios.php
    - informes.php
  config.php
  index.php

## 4. Arquitectura de Base de Datos (MySQL)
*Tablas Principales:
*users: id, nombre, email, password, foto, rol (superadmin/admin_rrhh/subadmin/user), departamento_id, tipo_jornada (completa/media_manana/media_tarde), activo, archivado.

*departamentos: id, nombre (RRHH, Dirección, Contabilidad, Desarrollo, Diseño).

*fichajes: id, user_id, proyecto_id, hora_entrada, hora_salida, fecha, tarde (boolean), minutos_retraso, horas_extra, teletrabajo (boolean).

*proyectos: id, nombre, departamento_id, activo, fecha_inicio, fecha_fin.

*proyecto_usuario: id, proyecto_id, user_id.

*horarios: id, user_id, dia_semana, hora_inicio, hora_fin, trimestre, año.

*vacaciones: id, user_id, fecha_inicio, fecha_fin, estado, aprobado_por.

*solicitudes: id, user_id, tipo (vacaciones, baja, cambio_horario, teletrabajo), descripcion, estado, aprobado_por, fecha.

*eventos: id, titulo, descripcion, fecha, departamento_id.

## 5. Gestión de Horarios
* Jornada Estándar: 8:00 a 19:00 (Lunes a Viernes).

*Intensiva Verano (1 jun - 1 sep): 8:00 a 16:00.

*Media Jornada (Mañana): 8:00 a 13:00.

*Media Jornada (Tarde): 13:00 a 19:00.

*Criterios de Retraso: Se contabiliza a partir de 1 minuto tras la hora de entrada.

*Horas Extras: Acumulación mensual basada en exceso sobre las 40h semanales.

## 6. Roles y Permisos
* Superadmin (Dirección): Control total del sistema, gestión de empleados (alta/baja/archivo), acceso a todos los informes y creación de proyectos.

* Admin_RRHH (RRHH): Gestión de empleados, modificación de fichajes y aprobación de vacaciones.

* Subadmin (Jefe de Depto): Gestión de proyectos de su departamento, control de horarios de su equipo y aprobación de vacaciones.

* User (Empleado): Fichaje diario, consulta de horario, informes propios y gestión de solicitudes.

## 7. Funcionalidades del Sistema
* Módulo de Fichaje
*Selección de proyecto al iniciar jornada.

*Marcador de Teletrabajo opcional.

*Cierre automático de jornadas abiertas a las 00:00.

*Notificaciones automáticas vía PHPMailer por retrasos o falta de fichaje.

*Edición de fichajes restringida exclusivamente a Superadmin y RRHH.

##Gestión de Proyectos
*Creación por parte de Dirección o Jefes de Departamento.

*Soporte para proyectos interdepartamentales.

*Asignación múltiple: Un empleado puede colaborar en varios proyectos simultáneamente.

## Informes y Solicitudes
*Informes: Filtros temporales personalizados y exportación a PDF/Excel según rol.

*Solicitudes: Gestión centralizada de bajas, vacaciones (fijadas en enero), cambios de horario y teletrabajo.