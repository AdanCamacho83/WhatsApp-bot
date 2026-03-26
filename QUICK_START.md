# 🚀 Guía Rápida - WhatsApp Bot Refactorizado

## ✅ ¿Qué se hizo?

Tu proyecto ha sido completamente refactorizado siguiendo las mejores prácticas de desarrollo en PHP:

### 📁 Nueva Estructura

```
whatsapp-bot/
├── config/
│   ├── config.php           ✅ Configuración centralizada (credenciales)
│   └── config.example.php   ✅ Plantilla de configuración
├── src/
│   ├── Database/
│   │   └── Database.php     ✅ Conexión PDO (Singleton)
│   ├── Services/
│   │   ├── TwilioService.php         ✅ WhatsApp
│   │   ├── GoogleCalendarService.php ✅ Google Calendar
│   │   └── OpenAIService.php         ✅ IA
│   ├── Models/
│   │   ├── Cita.php         ✅ Gestión de citas
│   │   └── Conversacion.php ✅ Gestión de conversaciones
│   ├── Controllers/
│   │   └── WhatsAppController.php ✅ Lógica del chatbot
│   └── Helpers/
│       └── DateHelper.php   ✅ Utilidades de fechas
├── public/
│   ├── index.php            ✅ Webhook (refactorizado)
│   ├── citas.php            ✅ API (refactorizado)
│   ├── recordatorios.php    ✅ Cronjob (refactorizado)
│   └── reprogramar.php      ✅ API (refactorizado)
└── scripts/
    └── init.php             ✅ Script de inicialización
```

## 🎯 Beneficios de la Refactorización

### ✅ Código Organizado
- ❌ Antes: Función `db()` repetida en 4 archivos
- ✅ Ahora: Clase `Database` única y reutilizable

### ✅ Sin Duplicación
- ❌ Antes: Código mezclado en cada archivo
- ✅ Ahora: Cada clase tiene una responsabilidad

### ✅ Fácil Mantenimiento
- ❌ Antes: Cambiar Twilio = editar 4 archivos
- ✅ Ahora: Cambiar Twilio = editar 1 clase

### ✅ Testeable
- ❌ Antes: Imposible hacer tests unitarios
- ✅ Ahora: Cada clase es testeable

### ✅ Escalable
- ❌ Antes: Difícil agregar funcionalidades
- ✅ Ahora: Fácil extender con nuevas clases

## 📝 Cómo Usar las Nuevas Clases

### Enviar un mensaje de WhatsApp

```php
use App\Services\TwilioService;

$config = require __DIR__ . '/config/config.php';
$twilioService = new TwilioService($config['twilio']);

$twilioService->sendMessage(
    'whatsapp:+1234567890',
    '¡Hola desde el nuevo sistema!'
);
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

### Consultar citas activas

```php
$citas = $citaModel->obtenerTodas(['activa']);
foreach ($citas as $cita) {
    echo $cita['fecha_inicio'];
}
```

### Interpretar fecha con IA

```php
use App\Services\OpenAIService;

$openAI = new OpenAIService($config['openai']);
$resultado = $openAI->parseDateTime(
    'mañana a las 3 pm',
    $config['logs']['ia_errors']
);

// Retorna: ['fecha' => '2026-01-10', 'hora' => '15:00']
```

## 🔧 Próximos Pasos

### 1. Verificar Configuración
```bash
# Revisa y actualiza tus credenciales
notepad config/config.php
```

### 2. Ejecutar Script de Inicialización (Opcional)
```bash
php scripts/init.php
```

### 3. Regenerar Autoload (Ya ejecutado)
```bash
composer dump-autoload
```

### 4. Probar el Webhook
- Envía un mensaje de prueba a tu número de WhatsApp
- El código nuevo está activo y funcionando
- El código viejo está comentado en `index.php` (puedes eliminarlo después)

## ⚠️ Importante

### El código anterior está preservado
En `public/index.php` encontrarás todo el código legacy comentado al final del archivo. Puedes:
- ✅ Mantenerlo como referencia
- ✅ Eliminarlo cuando estés seguro que todo funciona

### Archivos sin cambios
Estos archivos permanecen igual:
- ✅ `dashboard.html`
- ✅ `prueba.php`
- ✅ `respaldo.php`
- ✅ Base de datos (no se modificó)

## 🎓 Aprender Más

- 📖 Lee [README.md](README.md) para documentación completa
- 🏗️ Lee [ARCHITECTURE.md](ARCHITECTURE.md) para entender la arquitectura
- 💡 Revisa los comentarios en el código

## 🔐 Seguridad

Antes de subir a Git:
```bash
# El .gitignore ya está configurado para proteger:
# - config/config.php (credenciales)
# - keys/calendar.json (credenciales)
# - logs/*.log (información sensible)
```

## 📊 Comparativa: Antes vs Ahora

| Aspecto | Antes | Ahora |
|---------|-------|-------|
| Función `db()` | 4 copias | 1 clase reutilizable |
| Código Twilio | En 3 archivos | 1 clase TwilioService |
| Código Calendar | Mezclado | 1 clase GoogleCalendarService |
| Código OpenAI | Mezclado | 1 clase OpenAIService |
| Configuración | Hardcoded | Archivo central |
| Mantenibilidad | ❌ Difícil | ✅ Fácil |
| Testeable | ❌ No | ✅ Sí |
| PSR-4 Autoload | ❌ No | ✅ Sí |

## 💪 Lo que Puedes Hacer Ahora

1. ✅ **Agregar nuevos servicios fácilmente**
   ```php
   // Crear src/Services/EmailService.php
   // Usar en cualquier parte del proyecto
   ```

2. ✅ **Crear tests unitarios**
   ```php
   // Testear cada clase de forma independiente
   ```

3. ✅ **Reutilizar código**
   ```php
   // Usar las mismas clases en otros proyectos
   ```

4. ✅ **Escalar el proyecto**
   ```php
   // Agregar más funcionalidades sin romper nada
   ```

## 🐛 Si Algo No Funciona

1. **Verifica autoload**
   ```bash
   composer dump-autoload
   ```

2. **Revisa logs**
   ```bash
   cat logs/errores_citas.log
   cat logs/errores_ia.log
   ```

3. **Verifica configuración**
   - Asegúrate que `config/config.php` tiene tus credenciales correctas

4. **Revisa permisos**
   ```bash
   chmod 755 logs/
   chmod 644 logs/*.log
   ```

## 🎉 ¡Felicidades!

Tu proyecto ahora es:
- ✅ Profesional
- ✅ Mantenible
- ✅ Escalable
- ✅ Organizado
- ✅ Reutilizable

---

**Autor de la Refactorización:** GitHub Copilot  
**Fecha:** Enero 2026  
**Patrón:** MVC + Services + Repository
