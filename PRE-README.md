# 🏛️ VESTIGIA – CheckIn App
### Sistema de gestión de fichaje y horarios corporativos

---

## 📌 Descripción del proyecto

**Vestigia** es una revista internacional de historia con sede en España y una plantilla de aproximadamente 400 empleados. Este proyecto consiste en el desarrollo de una aplicación web interna para la gestión de fichajes, horarios, proyectos y recursos humanos.

El sistema permite a los empleados fichar su entrada y salida, gestionar solicitudes, consultar sus horarios e informes, y a los responsables supervisar y administrar su equipo.

---

## 🎨 Identidad corporativa

| Elemento | Detalle |
|----------|---------|
| **Nombre** | Vestigia |
| **Sector** | Revista internacional de historia |
| **Logo corporativo** | Tipografía serif elegante |
| **Logo revista** | V iluminada estilo manuscrito medieval |
| **Color primario** | Verde oscuro `#184332` |
| **Color secundario** | Crema `#FFFCF2` |
| **Color acento** | Burdeos `#660708` |
| **Estilo** | Biblioteca Clásica – elegante y académico |
| **Idioma** | Español |

---

## 🛠️ Stack tecnológico

| Capa | Tecnología |
|------|-----------|
| **Frontend** | HTML5 + CSS3 + JavaScript vanilla |
| **Backend** | PHP |
| **Base de datos** | MySQL + MySQL Workbench |
| **Frameworks** | Ninguno (vanilla puro) |
| **Diseño** | Responsive, estilo Google Classroom |

---

## 👥 Estructura de la empresa

### Departamentos
- Dirección
- Recursos Humanos (RRHH)
- Contabilidad
- Desarrollo
- Diseño

### Roles y permisos

| Rol | Descripción | Permisos |
|-----|-------------|---------|
| **Superadmin** | Dirección | Gestión total: añadir/eliminar/archivar empleados, ver todos los informes, crear proyectos |
| **Admin RRHH** | Recursos Humanos | Añadir/eliminar/archivar empleados, aprobar vacaciones, modificar fichajes, ver todos los fichajes |
| **Subadmin** | Jefe de departamento | Crear proyectos de su dpto, gestionar horarios de su equipo, aprobar vacaciones de su equipo |
| **User** | Empleado | Fichar, ver su horario, sus informes, solicitudes, aprobar cambios de su superior |

---

## ⏰ Horarios

| Tipo | Horario |
|------|---------|
| **Jornada completa** | 8:00 a 19:00 (Lunes a Viernes) |
| **Jornada intensiva (verano)** | 8:00 a 16:00 (1 junio – 1 septiembre) |
| **Media jornada mañana** | 8:00 a 13:00 |
| **Media jornada tarde** | 13:00 a 19:00 |

- **Retraso:** cualquier minuto después de la hora de entrada sin justificar
- **Horas extras:** cada minuto cuenta, se acumulan mensualmente (más de 40h semanales)
- **Horario:** se comunica trimestralmente por adelantado
- **Vacaciones:** se fijan en enero para todo el año

---

## 📱 Pantallas de la aplicación

| Pantalla | Descripción |
|----------|-------------|
| **Login** | Acceso con foto de perfil, email y contraseña |
| **Main / Dashboard** | Pantalla principal estilo Google Classroom |
| **Fichaje** | Entrada/salida con selección de proyecto y opción teletrabajo |
| **Mi Horario** | Calendario semanal/mensual con eventos y reuniones |
| **Solicitudes** | Gestión de peticiones entre empleados y superiores |
| **Informes** | Reportes de horas, retrasos, extras y proyectos |
| **Perfil** | Datos personales, contrato, nóminas y configuración |

---

## ⚙️ Funcionalidades

### Fichaje
- Entrada y salida con selección de proyecto
- Checkbox de teletrabajo al fichar
- Cierre automático a las 00:00 si se olvida desfichar
- Email automático a subadmin y RRHH si el empleado llega tarde
- Email si el empleado no ficha en todo el día
- Solo superadmin y RRHH pueden modificar fichajes incorrectos

### Proyectos
- Crean proyectos: superadmin y subadmin
- Un empleado puede estar en varios proyectos a la vez
- Fecha de inicio y fin (la fecha fin es flexible)
- Proyectos pueden ser interdepartamentales

### Informes
- Exportables en PDF y Excel
- Filtro de fecha libre (hoy, esta semana, este mes, este año, semana anterior, rango personalizado)
- Contenido: horas trabajadas, retrasos, horas extras, tiempo por proyecto
- Visibilidad por rol: user ve los suyos, subadmin ve su equipo, superadmin/admin_rrhh ven todo

### Solicitudes
| Tipo | Aprobado por |
|------|-------------|
| Vacaciones | Subadmin o RRHH |
| Baja | RRHH |
| Cambio de horario (del superior al empleado) | El propio empleado |
| Cambio de horario (del empleado) | Subadmin o RRHH |
| Teletrabajo | Subadmin |

### Empleados
- Baja voluntaria → cuenta eliminada
- Despido / causa legal → cuenta archivada por respaldo legal

### Notificaciones
- Email con PHPMailer si el empleado llega tarde
- Email si no ficha en todo el día
- Notificaciones internas en la app para solicitudes pendientes

---

## 🗄️ Base de datos (MySQL)

| Tabla | Campos principales |
|-------|-------------------|
| **users** | id, nombre, email, password, foto, rol, departamento_id, tipo_jornada, activo, archivado |
| **departamentos** | id, nombre |
| **fichajes** | id, user_id, proyecto_id, hora_entrada, hora_salida, fecha, tarde, minutos_retraso, horas_extra, teletrabajo |
| **proyectos** | id, nombre, departamento_id, activo, fecha_inicio, fecha_fin |
| **proyecto_usuario** | id, proyecto_id, user_id |
| **horarios** | id, user_id, dia_semana, hora_inicio, hora_fin, trimestre, año |
| **vacaciones** | id, user_id, fecha_inicio, fecha_fin, estado, aprobado_por |
| **solicitudes** | id, user_id, tipo, descripcion, estado, aprobado_por, fecha |
| **eventos** | id, titulo, descripcion, fecha, departamento_id |

---

## 📋 Requisitos pendientes

- [ ] Definir logo final en alta resolución
- [ ] Confirmar requisitos con el cliente (profe) mediante las 22 preguntas enviadas
- [ ] Definir paleta tipográfica definitiva
- [ ] Decidir estructura de carpetas final antes de lanzar en Cline

---

## 🚀 Próximos pasos

1. Confirmar requisitos con el cliente
2. Generar estructura base del proyecto con Cline
3. Crear base de datos en MySQL Workbench
4. Desarrollar pantalla de Login
5. Desarrollar Dashboard principal
6. Ir pantalla por pantalla hasta completar la app

---

*Documento generado como pre-requisitos del proyecto. Sujeto a cambios según feedback del cliente.*
