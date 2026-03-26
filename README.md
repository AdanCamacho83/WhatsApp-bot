# 📅 Sistema de Agendamiento de Citas por WhatsApp

## 📋 Descripción del Proyecto

Sistema web de gestión de citas que permite a empresas administrar sus agendamientos de manera eficiente. El sistema está diseñado para ser multi-tenant, donde cada empresa tiene su propio espacio aislado para gestionar clientes, citas y horarios de atención.

## ✨ Características Principales

- ✅ **Multi-tenant**: Sistema multi-empresa con aislamiento de datos
- 📱 **Integración WhatsApp**: Preparado para integración con Twilio
- 📆 **Calendario Visual**: Interfaz de calendario con FullCalendar
- ⏰ **Gestión de Horarios**: Configuración de horarios por día con tiempo de atención
- 👥 **Gestión de Clientes**: Administración de clientes por empresa
- 📊 **Estados de Citas**: Control de citas activas, completadas, canceladas y reprogramadas
- 🔐 **Sistema de Sesiones**: Autenticación segura con timeout automático
- 🌐 **Integración Google Calendar**: Opcional y configurable

---

## 🏗️ Estructura del Proyecto

```
whatsapp-bot/
│
├── 📁 config/                          # Configuración del sistema
│   └── config.php                      # Configuración de BD y Google Calendar
│
├── 📁 src/                             # Código fuente PHP (PSR-4)
│   ├── 📁 Database/
│   │   └── Database.php                # Conexión PDO Singleton (con UTC)
│   │
│   ├── 📁 Models/                      # Modelos de datos
│   │   ├── Empresa.php                 # Gestión de empresas
│   │   ├── Cliente.php                 # Gestión de clientes
│   │   ├── Cita.php                    # Gestión de citas
│   │   └── Horario.php                 # Gestión de horarios
│   │
│   └── 📁 Utils/
│       └── SessionManager.php          # Manejo de sesiones (30min timeout)
│
├── 📁 public/                          # Archivos públicos (Document Root)
│   ├── index.php                       # Login de empresas
│   ├── dashboard.php                   # Panel principal con calendario
│   │
│   ├── 📁 API Endpoints
│   ├── citas.php                       # GET: Obtener citas del calendario
│   ├── guardar_cita.php                # POST: Crear nueva cita
│   ├── reprogramar.php                 # POST: Reprogramar cita existente
│   ├── recordatorios.php               # POST: Cambiar estado de citas
│   │
│   ├── obtener_horarios.php            # GET: Obtener horarios de empresa
│   └── guardar_horarios.php            # POST: Guardar configuración de horarios
│
├── 📁 keys/                            # Credenciales (NO versionar)
│   └── calendar.json                   # Credenciales Google Calendar
│
├── 📁 logs/                            # Logs del sistema
│
├── 📁 vendor/                          # Dependencias Composer
│   ├── google/apiclient/               # Google API Client
│   ├── google/apiclient-services/      # Google Calendar API
│   ├── twilio/sdk/                     # Twilio SDK (WhatsApp)
│   └── ...
│
├── 📁 documentación/                   # Documentación técnica
│   ├── Estructura del proyecto.txt
│   ├── DB.txt
│   └── ...
│
├── composer.json                       # Dependencias PHP
└── README.md                           # Este archivo
```

---

## 🗂️ Arquitectura de Código

### Flujo de Datos

```
┌─────────────────┐
│   NAVEGADOR    │
│   (Cliente)    │
└────────┬────────┘
         │
         │ HTTP Request
         ▼
┌─────────────────────────────────────────────────┐
│              PUBLIC (index.php)                 │
│  - Login                                        │
│  - SessionManager::iniciarSesion()              │
└────────┬────────────────────────────────────────┘
         │
         │ Sesión Válida
         ▼
┌─────────────────────────────────────────────────┐
│           DASHBOARD (dashboard.php)             │
│  - FullCalendar                                 │
│  - Modal Horarios                               │
│  - Modal Nueva Cita                             │
└────────┬────────────────────────────────────────┘
         │
         │ AJAX Calls
         ▼
┌─────────────────────────────────────────────────┐
│              API ENDPOINTS                      │
│  ┌───────────────────────────────────┐          │
│  │ citas.php                         │          │
│  │ guardar_cita.php                  │          │
│  │ reprogramar.php                   │          │
│  │ recordatorios.php                 │          │
│  │ obtener_horarios.php              │          │
│  │ guardar_horarios.php              │          │
│  └───────────┬───────────────────────┘          │
└──────────────┼──────────────────────────────────┘
               │
               │ Usa Models
               ▼
┌─────────────────────────────────────────────────┐
│            MODELS (src/Models/)                 │
│  ┌──────────────────────────────────┐           │
│  │ Empresa::obtenerPorCodigo()      │           │
│  │ Cliente::crear()                 │           │
│  │ Cita::obtenerPorRango()          │           │
│  │ Horario::guardar()               │           │
│  └──────────┬───────────────────────┘           │
└─────────────┼───────────────────────────────────┘
              │
              │ Consultas SQL
              ▼
┌─────────────────────────────────────────────────┐
│       DATABASE (src/Database/Database.php)      │
│  - PDO Singleton                                │
│  - SET time_zone = '+00:00' (UTC)              │
└────────┬────────────────────────────────────────┘
         │
         ▼
┌─────────────────┐
│   MYSQL 3308    │
│ whatsapp_agenda │
└─────────────────┘
```

---

## 🗄️ Base de Datos

### Tablas Principales

#### `empresas`
```sql
- id (INT, PK, AUTO_INCREMENT)
- codigo_empresa (VARCHAR, UNIQUE) -- Código único por empresa
- nombre (VARCHAR)
- telefono_twilio (VARCHAR) -- Número Twilio para WhatsApp
- email (VARCHAR)
- password (VARCHAR, hashed)
- fecha_registro (DATETIME)
```

#### `clientes`
```sql
- id (INT, PK, AUTO_INCREMENT)
- idEmpresa (INT, FK -> empresas.id) -- Multi-tenant
- nombre (VARCHAR)
- telefono (VARCHAR)
- email (VARCHAR)
- fecha_registro (DATETIME)
```

#### `citas`
```sql
- id (INT, PK, AUTO_INCREMENT)
- idCliente (INT, FK -> clientes.id)
- servicio (VARCHAR)
- fecha_inicio (DATETIME)
- estado (ENUM: 'activa', 'completada', 'cancelada', 'reprogramada')
- fecha_creacion (DATETIME)
```

#### `horarios`
```sql
- id (INT, PK, AUTO_INCREMENT)
- idEmpresa (INT, FK -> empresas.id)
- lunes_apertura (TIME)
- lunes_cierre (TIME)
- martes_apertura (TIME)
- martes_cierre (TIME)
- miercoles_apertura (TIME)
- miercoles_cierre (TIME)
- jueves_apertura (TIME)
- jueves_cierre (TIME)
- viernes_apertura (TIME)
- viernes_cierre (TIME)
- sabado_apertura (TIME)
- sabado_cierre (TIME)
- domingo_apertura (TIME)
- domingo_cierre (TIME)
- tiempo_atencion (TIME) -- Duración de cada cita
```

#### `conversaciones`
```sql
- id (INT, PK, AUTO_INCREMENT)
- idCliente (INT, FK -> clientes.id)
- mensaje (TEXT)
- fecha (DATETIME)
- tipo (ENUM: 'enviado', 'recibido')
```

### Relaciones

```
empresas (1) ──────┬──> (N) clientes
                   │
                   └──> (1) horarios

clientes (1) ──────┬──> (N) citas
                   │
                   └──> (N) conversaciones
```

---

## 🔧 Tecnologías Utilizadas

### Backend
- **PHP 8.x** - Lenguaje del servidor
- **MySQL** - Base de datos (Puerto 3308)
- **PDO** - Abstracción de base de datos
- **PSR-4** - Autoloading de clases
- **Composer** - Gestor de dependencias

### Frontend
- **FullCalendar 6.1.8** - Biblioteca de calendario
- **Font Awesome** - Iconos
- **JavaScript Vanilla** - Sin frameworks
- **CSS3** - Estilos personalizados

### APIs y Servicios
- **Google Calendar API** - Sincronización opcional de eventos
- **Twilio SDK** - Integración con WhatsApp (preparado)

### Dependencias Composer
```json
{
  "google/apiclient": "^2.0",
  "twilio/sdk": "^6.0",
  "monolog/monolog": "^2.0"
}
```

---

## 🚀 Flujo de Uso

### 1. Registro de Empresa
```
Usuario → registro_empresa.php
  ↓
Genera codigo_empresa automático (ej: EMP-001)
  ↓
Guarda en tabla empresas
  ↓
Redirige a login
```

### 2. Login
```
Usuario ingresa codigo_empresa + password
  ↓
index.php valida credenciales
  ↓
SessionManager::iniciarSesion()
  ↓
Redirige a dashboard.php
```

### 3. Configuración de Horarios
```
Dashboard → Click "Horarios"
  ↓
Abre modal con tabla de 7 días
  ↓
Usuario configura apertura/cierre por día
  ↓
Usuario define tiempo_atencion (ej: 01:30)
  ↓
guardar_horarios.php valida y guarda
  ↓
Validación: tiempo_atencion ≤ (cierre - apertura)
```

### 4. Creación de Cita
```
Dashboard → Click en día del calendario
  ↓
Abre modal "Nueva Cita"
  ↓
Usuario ingresa: servicio, fecha, hora, teléfono
  ↓
Validación: hora dentro de horario de atención
  ↓
guardar_cita.php crea cliente (si no existe)
  ↓
Crea cita con estado 'activa'
  ↓
Opcional: sincroniza con Google Calendar
  ↓
Calendario se actualiza automáticamente
```

### 5. Gestión de Citas
```
Ver citas en calendario (solo activas)
  ↓
Click en cita → Ver detalles
  ↓
Opciones: Reprogramar | Completar | Cancelar
  ↓
Estado cambia en BD
  ↓
Calendario se actualiza
```

---

## ⚙️ Configuración

### 1. Base de Datos (`config/config.php`)
```php
'database' => [
    'host' => 'localhost',
    'port' => 3308,
    'dbname' => 'whatsapp_agenda',
    'user' => 'root',
    'password' => '',
    'charset' => 'utf8mb4'
]
```

### 2. Google Calendar (`config/config.php`)
```php
'google_calendar' => [
    'activo' => false, // Cambiar a true para activar
    'credentials_path' => __DIR__ . '/../keys/calendar.json'
]
```

### 3. Zona Horaria
Todo el sistema trabaja en **UTC (+00:00)**:
- `date_default_timezone_set('UTC')` en PHP
- `SET time_zone = '+00:00'` en MySQL
- No hay conversiones GMT

---

## 🔐 Seguridad

### Sesiones
- Timeout automático: **30 minutos**
- Regeneración de ID en cada login
- Validación en cada request (SessionManager::requerirSesion())

### Multi-tenant
- Aislamiento por `idEmpresa` en todas las consultas
- Clientes solo ven sus propios datos
- Citas filtradas por empresa del usuario logueado

### SQL Injection
- Uso exclusivo de **Prepared Statements** (PDO)
- Binding de parámetros en todas las consultas

---

## 📝 Endpoints API

| Método | Endpoint | Descripción |
|--------|----------|-------------|
| GET | `/citas.php` | Obtener citas de los últimos 30 días |
| POST | `/guardar_cita.php` | Crear nueva cita |
| POST | `/reprogramar.php` | Cambiar fecha/hora de cita |
| POST | `/recordatorios.php` | Cambiar estado de cita |
| GET | `/obtener_horarios.php` | Obtener configuración de horarios |
| POST | `/guardar_horarios.php` | Guardar/actualizar horarios |

---

## 📌 Características Destacadas

### ✨ Sistema de Horarios
- **7 días configurables** individualmente
- **Checkbox "Descanso"** para días sin atención
- **"Aplicar a todos los días"** copia horario de lunes
- **Tiempo de atención** en formato 24h (HH:MM)
- **Validación automática**: tiempo_atencion ≤ horas disponibles

### 📅 Calendario Inteligente
- Vista mensual interactiva
- Clic en día para crear cita
- Solo muestra citas activas
- Colores por estado
- Recarga automática tras cambios

### 🎨 Interfaz de Usuario
- Diseño responsivo
- Modales modernos
- Validaciones en tiempo real
- Auto-formateo de campos de tiempo
- Mensajes de éxito/error claros

---

## 🔄 Estados del Sistema

### Estados de Cita
- **activa**: Cita agendada pendiente
- **completada**: Cita realizada
- **cancelada**: Cita cancelada por empresa/cliente
- **reprogramada**: Cita movida a nueva fecha

### Flujo de Estados
```
[Nueva Cita] → activa
                  ↓
        ┌─────────┼─────────┐
        ▼         ▼         ▼
   completada  cancelada  reprogramada
```

---

## 📦 Instalación

1. **Clonar repositorio**
```bash
git clone <repo-url>
cd whatsapp-bot
```

2. **Instalar dependencias**
```bash
composer install
```

3. **Configurar base de datos**
- Crear BD `whatsapp_agenda` en MySQL (puerto 3308)
- Ejecutar scripts SQL en `documentación/DB.txt`

4. **Configurar credenciales**
- Editar `config/config.php` con datos de BD
- (Opcional) Agregar `keys/calendar.json` para Google Calendar

5. **Configurar servidor web**
- Document Root: `/public`
- Habilitar `mod_rewrite`

6. **Acceder**
```
http://localhost/whatsapp-bot/public/index.php
```

---

## 👨‍💻 Desarrollo

### Agregar Nuevo Modelo
1. Crear clase en `src/Models/`
2. Extender funcionalidad de `Database`
3. Agregar métodos CRUD
4. Usar Prepared Statements

### Agregar Nuevo Endpoint
1. Crear archivo en `public/`
2. Requerir sesión: `SessionManager::requerirSesion()`
3. Obtener `$idEmpresa` del usuario logueado
4. Filtrar datos por empresa
5. Retornar JSON

---

**Última actualización**: Marzo 2026

## 🔧 Configuración

### 1. Instalar dependencias
```bash
composer install
```

### 2. Configurar credenciales
Editar `config/config.php` con tus datos:
- Base de datos MySQL
- Credenciales de Twilio
- ID del calendario de Google
- API Key de OpenAI

### 3. Configurar base de datos
La base de datos `whatsapp_agenda` debe tener las siguientes tablas:

**Tabla: citas**
- `id` (int, PK, auto_increment)
- `telefono_usuario` (varchar 40)
- `fecha_inicio` (datetime)
- `servicio` (varchar 100)
- `recordatorio_enviado` (tinyint, default 0)
- `created_at` (timestamp)
- `estado` (enum: 'activa', 'cancelada', 'atendida', 'esperando_fecha')

**Tabla: conversaciones**
- `telefono` (varchar 40, PK)
- `estado` (varchar 50)
- `updated_at` (datetime)
- `fecha_propuesta` (datetime)

### 4. Configurar webhook de Twilio
En la configuración de tu número de WhatsApp en Twilio, establece el webhook:
```
https://tu-dominio.com/public/index.php
```

### 5. Configurar cronjob para recordatorios
```bash
# Ejecutar cada hora
0 * * * * php /ruta/a/whatsapp-bot/public/recordatorios.php
```

## 📚 Uso de las Clases

### Enviar un mensaje de WhatsApp
```php
use App\Services\TwilioService;

$config = require __DIR__ . '/config/config.php';
$twilio = new TwilioService($config['twilio']);

$twilio->sendMessage('whatsapp:+1234567890', 'Hola desde el bot!');
```

### Crear una cita
```php
use App\Database\Database;
use App\Models\Cita;

Database::setConfig($config['database']);
$citaModel = new Cita();

$citaModel->crear(
    'whatsapp:+1234567890',
    '2026-01-15 14:00:00',
    'Corte de cabello'
);
```

### Interpretar fecha con IA
```php
use App\Services\OpenAIService;

$openAI = new OpenAIService($config['openai']);
$resultado = $openAI->parseDateTime('mañana a las 3 pm', $config['logs']['ia_errors']);

// Retorna: ['fecha' => '2026-01-10', 'hora' => '15:00']
```

### Obtener citas activas
```php
$citas = $citaModel->obtenerTodas(['activa']);
```

## 🔄 Flujo del Chatbot

### 1. Crear Cita (Nuevo Flujo Interactivo v2.1.0)

```
Usuario: "crear cita"
    ↓
Bot: "¿Para qué día y hora deseas tu cita?"
    ↓
Usuario: "mañana a las 5 pm"
    ↓
Bot: "¿Confirmas esta fecha? 📅 10/01/2026 5:00 PM"
    ↓
Usuario: "sí"
    ↓
Bot: "✅ ¡Listo! Tu cita fue agendada exitosamente"
    ↓
Sistema guarda en BD y Google Calendar
```

**Ventajas:**
- ✅ Usuario elige fecha y hora
- ✅ Interpretación de lenguaje natural con IA
- ✅ Confirmación explícita
- ✅ Flexible y profesional

### 2. Consultar Cita

```
Usuario: "consultar mi cita"
    ↓
Bot: "Tu servicio es el viernes 12 de enero de 2026 a las 5:00 PM 😊"
```

### 3. Reprogramar Cita

```
Usuario: "reprogramar mi cita"
    ↓
Bot: "¿Para qué día y hora deseas reprogramar?"
    ↓
Usuario: "el sábado a las 3 pm"
    ↓
Bot: "¿Confirmas esta nueva fecha? 📅 13/01/2026 3:00 PM"
    ↓
Usuario: "sí"
    ↓
Bot: "✅ Tu cita fue reprogramada exitosamente"
```

### 4. Cancelar Cita

```
Usuario: "cancelar mi cita"
    ↓
Bot: "❌ Tu cita ha sido cancelada correctamente"
```

### 5. Recordatorios Automáticos

```
Sistema (cronjob cada hora)
    ↓
Verifica citas en las próximas 24 horas
    ↓
Envía WhatsApp: "⏰ Recordatorio de tu cita mañana a las 5:00 PM"
```

## 🔄 Flujo del Chatbot (Legacy)

1. **Usuario envía mensaje** → Twilio webhook → `public/index.php`
2. **WhatsAppController procesa** → Detecta intención
3. **Según intención**:
   - Crear cita → Guarda en BD y Google Calendar
   - Consultar → Lee de BD
   - Cancelar → Actualiza estado
   - Reprogramar → Solicita nueva fecha → Confirma → Actualiza

## 🚀 Ventajas de la Nueva Estructura

### ✅ Separación de responsabilidades
- Cada clase tiene una función específica
- Código más fácil de mantener y testear

### ✅ Reutilización
- Las clases se pueden usar en cualquier archivo
- No hay código duplicado

### ✅ Escalabilidad
- Fácil agregar nuevas funcionalidades
- Agregar nuevos servicios sin modificar código existente

### ✅ Configuración centralizada
- Todas las credenciales en un solo lugar
- Fácil cambiar entre ambientes (dev/prod)

### ✅ PSR-4 Autoloading
- No más `require` manuales
- Carga automática de clases

## 🛠️ Próximas mejoras sugeridas

1. **Agregar .env para credenciales**
   ```bash
   composer require vlucas/phpdotenv
   ```

2. **Implementar logging estructurado**
   ```bash
   composer require monolog/monolog
   ```

3. **Tests unitarios**
   ```bash
   composer require --dev phpunit/phpunit
   ```

4. **Validación de datos**
   ```bash
   composer require respect/validation
   ```

## 📝 Notas

- El código legacy se mantiene comentado en `public/index.php` para referencia
- Se puede eliminar después de verificar que todo funciona correctamente
- Los logs se guardan en la carpeta `logs/`

## 🔒 Seguridad

**IMPORTANTE**: Antes de subir a producción:
1. Mover credenciales a archivo `.env`
2. Agregar `.env` al `.gitignore`
3. Validar todos los inputs de usuario
4. Implementar rate limiting
5. Usar HTTPS para todos los webhooks

## 📞 Soporte

Para dudas o issues, revisar los logs en:
- `logs/errores_citas.log`
- `logs/errores_ia.log`
- `logs/recordatorios.log`
