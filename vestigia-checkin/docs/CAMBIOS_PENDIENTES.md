# 📋 Cambios Pendientes — Vestigia CheckIn

Documento de planificación de mejoras y correcciones pendientes de implementar.
Última actualización: abril 2026

---

## ✅ Cambios ya aplicados (rama `testing`)

### Fix — Horas extra desmedidas
**Problema:** `calcularHorasExtra()` comparaba solo la hora de salida real contra la esperada, ignorando a qué hora había entrado el empleado. Un empleado que entraba tarde y salía a su hora generaba extras falsos.

**Solución:** La función ahora calcula el tiempo real trabajado (entrada→salida) y lo compara contra la jornada esperada completa.

**Archivos modificados:**
- `includes/funciones.php` — nueva firma de `calcularHorasExtra()`
- `api/fichar.php` — usa la nueva firma al registrar salida
- `api/informes.php` — idem al editar fichaje

---

### Fix — Bug SQL en `notificarRetraso()`
**Problema:** Fallo de precedencia de operadores en el `WHERE`. El filtro `activo = 1 AND archivado = 0` solo aplicaba al rol `admin_rrhh`, no al `subadmin`, por lo que subadmins inactivos o archivados podían recibir emails de retraso.

**Solución:** Añadidos paréntesis correctos para que el filtro aplique a ambos roles.

**Archivos modificados:**
- `includes/funciones.php` — fix en la query de `notificarRetraso()`

---

### Feature — Propuestas de responsable a empleado
**Descripción:** Hasta ahora el flujo de solicitudes era únicamente empleado → responsable. Se ha añadido el flujo inverso: un responsable (subadmin, admin_rrhh, superadmin) puede enviar propuestas a un empleado concreto, y el empleado las acepta o rechaza.

**Cambios en BD:**
```sql
ALTER TABLE solicitudes
  ADD COLUMN destinatario_id INT(11) UNSIGNED DEFAULT NULL AFTER user_id,
  ADD CONSTRAINT fk_solicitudes_destinatario
    FOREIGN KEY (destinatario_id) REFERENCES users(id) ON DELETE SET NULL;
```
- `destinatario_id = NULL` → solicitud normal (empleado → responsable)
- `destinatario_id = valor` → propuesta inversa (responsable → empleado)

**Nuevas funciones en `includes/funciones.php`:**
- `crearPropuesta()` — crea una propuesta con destinatario
- `getPropuestasRecibidas()` — propuestas recibidas por un usuario
- `getPropuestasEnviadas()` — propuestas enviadas por un responsable
- `contarPropuestasPendientes()` — badge de notificaciones

**Nuevas tabs en `pages/solicitudes.php`:**
- **Recibidas** — visible para todos, muestra propuestas recibidas con botones Aceptar/Rechazar
- **Enviadas** — solo responsables, muestra el estado de sus propuestas
- **Nueva propuesta** — solo responsables, formulario para enviar propuesta a empleado

**Archivos modificados:**
- `database/schema.sql`
- `includes/funciones.php`
- `pages/solicitudes.php`

---

## 🔄 Cambios pendientes de implementar

### Feature — Refactorización de jornadas laborales

#### Contexto
El sistema actual tiene una única "jornada completa" de 8:00 a 19:00 (11h) que es incorrecta, y no distingue entre turno de mañana y tarde. Además existe una lógica de "jornada intensiva de verano" que se va a eliminar para simplificar.

#### Nuevos tipos de jornada

| Tipo | Turno | Horario | Horas/día |
|------|-------|---------|-----------|
| `completa_manana` | Mañana | 08:00 - 16:00 | 8h |
| `completa_tarde` | Tarde | 11:00 - 19:00 | 8h |
| `parcial_manana` | Mañana | 08:00 - 13:00 | 5h |
| `parcial_tarde` | Tarde | 14:00 - 19:00 | 5h |
| `sin_asignar` | — | Sin horario | — |

> ⚠️ Un empleado con `sin_asignar` **no puede fichar** hasta que RRHH o admin le asigne un turno.

#### Cambios por archivo

**`config.php`**
Reemplazar constantes actuales por:
```php
define('HORA_ENTRADA_COMPLETA_MANANA', '08:00');
define('HORA_SALIDA_COMPLETA_MANANA',  '16:00');
define('HORA_ENTRADA_COMPLETA_TARDE',  '11:00');
define('HORA_SALIDA_COMPLETA_TARDE',   '19:00');
define('HORA_ENTRADA_PARCIAL_MANANA',  '08:00');
define('HORA_SALIDA_PARCIAL_MANANA',   '13:00');
define('HORA_ENTRADA_PARCIAL_TARDE',   '14:00');
define('HORA_SALIDA_PARCIAL_TARDE',    '19:00');
```
Eliminar: `HORA_ENTRADA_NORMAL`, `HORA_SALIDA_NORMAL`, `HORA_ENTRADA_VERANO`, `HORA_SALIDA_VERANO`, `INICIO_JORNADA_VERANO`, `FIN_JORNADA_VERANO`

**`database/schema.sql`**
```sql
-- 1. Actualizar ENUM en users
ALTER TABLE users MODIFY tipo_jornada
  ENUM('completa_manana','completa_tarde','parcial_manana','parcial_tarde','sin_asignar')
  NOT NULL DEFAULT 'sin_asignar';

-- 2. Nueva tabla para cambios temporales de horario
CREATE TABLE cambios_horario_temporales (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id       INT UNSIGNED NOT NULL,
  tipo_jornada_temporal ENUM('completa_manana','completa_tarde','parcial_manana','parcial_tarde') NOT NULL,
  fecha_inicio  DATE NOT NULL,
  fecha_fin     DATE NOT NULL,
  solicitud_id  INT UNSIGNED DEFAULT NULL,
  created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id)      REFERENCES users(id)      ON DELETE CASCADE,
  FOREIGN KEY (solicitud_id) REFERENCES solicitudes(id) ON DELETE SET NULL
);
```

**`includes/funciones.php`**
- Reescribir `obtenerHorario()` con los 4 nuevos tipos, sin lógica de verano
- Eliminar `esJornadaVerano()`
- Nueva función `getJornadaEfectiva(int $userId, string $fecha)`:
  - Comprueba si existe un cambio temporal activo para esa fecha en `cambios_horario_temporales`
  - Si existe → devuelve el tipo temporal
  - Si no → devuelve `users.tipo_jornada`

**`api/fichar.php`**
- Usar `getJornadaEfectiva()` en vez de leer `$usuario['tipo_jornada']` directamente
- Bloquear fichaje si la jornada efectiva es `sin_asignar`, devolviendo error claro al usuario

**`pages/solicitudes.php`** + **`includes/funciones.php`**
Al aceptar una propuesta o solicitud de tipo `cambio_horario`:
- Si tiene `fecha_inicio` y `fecha_fin` → insertar en `cambios_horario_temporales` (cambio temporal)
- Si no tiene fechas → actualizar `users.tipo_jornada` directamente (cambio permanente)

**`api/usuarios.php`**
- Al crear empleado, `tipo_jornada` por defecto = `sin_asignar`
- El select de tipo de jornada en el formulario de creación/edición debe reflejar los nuevos valores

**`pages/fichaje.php`**
- Eliminar referencia a "Jornada intensiva"
- Mostrar mensaje informativo si el empleado tiene `sin_asignar`: *"Tu jornada laboral aún no ha sido asignada. Contacta con RRHH."*

#### Orden de ejecución
| Paso | Archivo | Acción |
|------|---------|--------|
| 1 | `database/schema.sql` | ALTER TABLE users + CREATE TABLE cambios_horario_temporales |
| 2 | `config.php` | Nuevas constantes, eliminar verano |
| 3 | `includes/funciones.php` | Reescribir `obtenerHorario()`, nueva `getJornadaEfectiva()` |
| 4 | `api/fichar.php` | Usar `getJornadaEfectiva()` + bloqueo `sin_asignar` |
| 5 | `pages/solicitudes.php` | Lógica al aceptar `cambio_horario` |
| 6 | `api/usuarios.php` | Default `sin_asignar` + nuevos valores en select |
| 7 | `pages/fichaje.php` | Quitar jornada intensiva + mensaje sin asignar |

---

## 💡 Ideas a valorar en el futuro

- **Notificación interna** al empleado cuando se le asigna o cambia la jornada
- **Historial de jornadas** por empleado para trazabilidad
- **Vista de calendario** con los cambios temporales activos por departamento

---

*Este documento se actualiza conforme se van planificando e implementando cambios.*
