# Arquitectura del Sistema

## 📊 Diagrama de Componentes

```
┌─────────────────────────────────────────────────────────────────┐
│                         USUARIO (WhatsApp)                       │
└─────────────────────────────────────────────────────────────────┘
                                 │
                                 ↓
┌─────────────────────────────────────────────────────────────────┐
│                        TWILIO WEBHOOK                            │
│                      (public/index.php)                          │
└─────────────────────────────────────────────────────────────────┘
                                 │
                                 ↓
┌─────────────────────────────────────────────────────────────────┐
│                    WhatsAppController                            │
│  ┌──────────────────────────────────────────────────────────┐  │
│  │  • procesarMensaje()                                      │  │
│  │  • detectarIntencion()                                    │  │
│  │  • crearCita()                                            │  │
│  │  • consultarMiCita()                                      │  │
│  │  • cancelarCita()                                         │  │
│  │  • reprogramarCita()                                      │  │
│  └──────────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────────┘
           │                  │                  │
           ↓                  ↓                  ↓
    ┌──────────┐      ┌──────────┐      ┌──────────┐
    │ Services │      │  Models  │      │ Helpers  │
    └──────────┘      └──────────┘      └──────────┘
           │                  │                  │
           ↓                  ↓                  ↓
┌─────────────────┐  ┌─────────────────┐  ┌─────────────────┐
│ TwilioService   │  │  Cita           │  │  DateHelper     │
│ • sendMessage() │  │  • crear()      │  │  • formatear... │
├─────────────────┤  │  • obtener...() │  └─────────────────┘
│ GoogleCalendar  │  │  • cancelar()   │
│ Service         │  │  • reprogramar()│
│ • create...()   │  ├─────────────────┤
│ • update...()   │  │  Conversacion   │
├─────────────────┤  │  • obtener()    │
│ OpenAIService   │  │  • actualizar...│
│ • parseDate...()│  │  • eliminar()   │
└─────────────────┘  └─────────────────┘
           │                  │
           ↓                  ↓
┌─────────────────┐  ┌─────────────────┐
│  Twilio API     │  │   Database      │
│  (WhatsApp)     │  │   (MySQL)       │
└─────────────────┘  └─────────────────┘
           │
           ↓
┌─────────────────┐
│ Google Calendar │
│      API        │
└─────────────────┘
           │
           ↓
┌─────────────────┐
│   OpenAI API    │
│   (GPT-4)       │
└─────────────────┘
```

## 🔄 Flujo de Comunicación

### 1. Usuario crea una cita

```
Usuario (WhatsApp)
    ↓ "crear nueva cita"
Twilio Webhook
    ↓
WhatsAppController::procesarMensaje()
    ↓
WhatsAppController::detectarIntencion() → "crear"
    ↓
WhatsAppController::crearCita()
    ├─→ GoogleCalendarService::createAppointment()
    │   └─→ Google Calendar API
    └─→ Cita::crear()
        └─→ Database (MySQL)
    ↓
TwilioService::generateResponse()
    ↓
Usuario recibe confirmación
```

### 2. Usuario reprograma una cita

```
Usuario (WhatsApp)
    ↓ "reprogramar mi cita"
WhatsAppController::pedirNuevaFecha()
    ↓
Conversacion::actualizarEstado("esperando_fecha")
    ↓
Usuario responde: "mañana a las 5 pm"
    ↓
OpenAIService::parseDateTime()
    └─→ OpenAI API
    ↓
WhatsAppController::reprogramarCita()
    ↓
Conversacion::guardarFechaPropuesta()
    ↓
Usuario confirma: "sí"
    ↓
WhatsAppController::confirmarReprogramacion()
    ├─→ Cita::reprogramar()
    │   └─→ Database
    └─→ Conversacion::eliminar()
    ↓
Usuario recibe confirmación
```

### 3. Recordatorios automáticos (Cronjob)

```
Cronjob ejecuta recordatorios.php
    ↓
Cita::obtenerPendientesRecordatorio()
    ↓
Database devuelve citas
    ↓
Para cada cita:
    TwilioService::sendMessage()
    ↓
    Cita::marcarRecordatorioEnviado()
```

## 🗂️ Patrón de Diseño Utilizado

### **MVC + Services + Repository**

- **Models** (Repository Pattern): Acceso a datos
- **Controllers**: Lógica de negocio
- **Services**: Integración con APIs externas
- **Helpers**: Funciones auxiliares

### Ventajas:
- ✅ Separación de responsabilidades
- ✅ Código testeable
- ✅ Reutilizable
- ✅ Mantenible
- ✅ Escalable

## 📦 Dependencias

```json
{
  "require": {
    "google/apiclient": "^2.18",  // Google Calendar API
    "twilio/sdk": "^8.10"          // Twilio WhatsApp API
  }
}
```

### Futuras dependencias sugeridas:
- `vlucas/phpdotenv` - Variables de entorno
- `monolog/monolog` - Logging estructurado
- `phpunit/phpunit` - Tests unitarios
- `respect/validation` - Validación de datos

## 🔐 Seguridad

### Implementado:
- ✅ Configuración centralizada
- ✅ Prepared statements (SQL Injection)
- ✅ Error logging

### Por implementar:
- ⚠️ Variables de entorno (.env)
- ⚠️ Rate limiting
- ⚠️ Input validation
- ⚠️ CSRF protection
- ⚠️ API authentication

## 📊 Base de Datos

```
┌─────────────────┐         ┌─────────────────┐
│     citas       │         │ conversaciones  │
├─────────────────┤         ├─────────────────┤
│ id (PK)         │         │ telefono (PK)   │
│ telefono_usuario│─────────│                 │
│ fecha_inicio    │         │ estado          │
│ servicio        │         │ updated_at      │
│ recordatorio_.. │         │ fecha_propuesta │
│ created_at      │         └─────────────────┘
│ estado          │
└─────────────────┘
```

## 🚀 Escalabilidad

### Horizontal:
- Agregar más instancias del webhook
- Load balancer para distribuir carga

### Vertical:
- Optimizar queries SQL
- Implementar caché (Redis)
- Queue system para mensajes (RabbitMQ)

### Microservicios futuros:
- Servicio de notificaciones
- Servicio de reportes
- Servicio de analíticas
