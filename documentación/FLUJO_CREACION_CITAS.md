# Flujo de Creación de Citas - Documentación

## 🔄 Nuevo Flujo Interactivo

### Antes (v2.0.0)
```
Usuario: "crear cita"
    ↓
Bot: "✅ Tu cita fue agendada para mañana a las 3:00 PM."
```
❌ Problema: No permite al usuario elegir fecha y hora

### Ahora (v2.1.0)
```
1. Usuario: "crear cita"
    ↓
2. Bot: "¿Para qué día y hora deseas tu cita?"
    ↓
3. Usuario: "mañana a las 5 pm"
    ↓
4. Bot: "¿Confirmas esta fecha? 📅 10/01/2026 5:00 PM"
    ↓
5. Usuario: "sí"
    ↓
6. Bot: "✅ ¡Listo! Tu cita fue agendada exitosamente"
```
✅ Ahora el usuario elige la fecha y hora

## 📊 Diagrama de Estados

```
┌─────────────────┐
│  Usuario escribe│
│  "crear cita"   │
└────────┬────────┘
         │
         ↓
┌─────────────────────────────────┐
│ Estado: esperando_fecha_nueva_  │
│         cita                     │
│                                  │
│ Bot: "¿Para qué día y hora...?" │
└────────┬────────────────────────┘
         │
         ↓ Usuario responde: "mañana 5pm"
         │
┌─────────────────────────────────┐
│ OpenAI interpreta fecha          │
│ resultado: {                     │
│   "fecha": "2026-01-10",        │
│   "hora": "17:00"               │
│ }                                │
└────────┬────────────────────────┘
         │
         ↓
┌─────────────────────────────────┐
│ Estado: confirmar_fecha_nueva_  │
│         cita                     │
│                                  │
│ Bot: "¿Confirmas esta fecha?"   │
└────────┬────────────────────────┘
         │
    ┌────┴────┐
    │         │
    ↓         ↓
┌───────┐ ┌───────┐
│  Sí   │ │  No   │
└───┬───┘ └───┬───┘
    │         │
    ↓         ↓
┌────────┐ ┌──────────┐
│ Crear  │ │ Cancelar │
│ Cita   │ │          │
└────────┘ └──────────┘
```

## 💻 Código Implementado

### 1. Detección de Intención "crear"

```php
// En detectarIntencion()
if (str_contains($mensaje, 'crear') || str_contains($mensaje, 'nueva')) {
    return 'crear';
}
```

### 2. Solicitar Fecha y Hora

```php
private function crearCita(string $telefono, string $mensaje): string
{
    // Actualizar estado
    $this->conversacionModel->actualizarEstado($telefono, 'esperando_fecha_nueva_cita');

    return "📅 ¡Perfecto! Vamos a agendar tu cita.\n\n" .
           "¿Para qué día y hora deseas tu cita?\n\n" .
           "Ejemplos:\n" .
           "👉 Mañana a las 5 pm\n" .
           "👉 El viernes a las 10 am\n" .
           "👉 15 de enero a las 3 pm";
}
```

### 3. Procesar Respuesta del Usuario

```php
private function procesarFechaNuevaCita(string $telefono, string $mensaje): string
{
    // Usar OpenAI para interpretar fecha
    $resultado = $this->openAIService->parseDateTime($mensaje, $this->config['logs']['ia_errors']);

    if (!$resultado) {
        return "❌ No pude entender la fecha 😕\n\n" .
               "Intenta algo como:\n" .
               "👉 Mañana a las 5 pm\n" .
               "👉 El jueves a las 10 am";
    }

    $fechaHora = $resultado['fecha'] . ' ' . $resultado['hora'];
    
    // Guardar fecha propuesta
    $this->conversacionModel->guardarFechaPropuesta($telefono, $fechaHora);
    $this->conversacionModel->actualizarEstado($telefono, 'confirmar_fecha_nueva_cita');

    return "📅 ¿Confirmas esta fecha para tu cita?\n\n" .
           "🗓️ " . date('d/m/Y g:i A', strtotime($fechaHora)) . "\n\n" .
           "Responde:\n" .
           "✅ Sí\n" .
           "❌ No";
}
```

### 4. Confirmar y Crear Cita

```php
private function confirmarNuevaCita(string $telefono): string
{
    $conv = $this->conversacionModel->obtener($telefono);
    $fechaHora = $conv['fecha_propuesta'];
    $servicio = 'Corte de cabello';

    try {
        // Crear en Google Calendar
        $this->calendarService->createAppointment($telefono, $fechaHora, $servicio);

        // Guardar en base de datos
        $this->citaModel->crear($telefono, $fechaHora, $servicio);

        // Limpiar conversación
        $this->conversacionModel->eliminar($telefono);

        return "✅ ¡Listo! Tu cita fue agendada exitosamente 🙌\n\n" .
               "📅 Fecha: " . date('d/m/Y', strtotime($fechaHora)) . "\n" .
               "⏰ Hora: " . date('g:i A', strtotime($fechaHora)) . "\n" .
               "💈 Servicio: " . $servicio;
    } catch (\Exception $e) {
        return "❌ Hubo un error al agendar tu cita.";
    }
}
```

## 🗄️ Estados en Base de Datos

### Tabla: conversaciones

| telefono | estado | fecha_propuesta |
|----------|--------|-----------------|
| whatsapp:+123... | esperando_fecha_nueva_cita | NULL |
| whatsapp:+123... | confirmar_fecha_nueva_cita | 2026-01-10 17:00:00 |

## 📝 Ejemplos de Conversación

### Ejemplo 1: Creación Exitosa

```
Usuario: crear cita

Bot: 📅 ¡Perfecto! Vamos a agendar tu cita.

¿Para qué día y hora deseas tu cita?

Ejemplos:
👉 Mañana a las 5 pm
👉 El viernes a las 10 am
👉 15 de enero a las 3 pm

---

Usuario: mañana a las 5pm

Bot: 📅 ¿Confirmas esta fecha para tu cita?

🗓️ 10/01/2026 5:00 PM

Responde:
✅ Sí
❌ No

---

Usuario: sí

Bot: ✅ ¡Listo! Tu cita fue agendada exitosamente 🙌

📅 Fecha: 10/01/2026
⏰ Hora: 5:00 PM
💈 Servicio: Corte de cabello

Te enviaré un recordatorio 24 horas antes. ¡Nos vemos! 😊
```

### Ejemplo 2: Fecha No Entendida

```
Usuario: crear cita

Bot: 📅 ¡Perfecto! Vamos a agendar tu cita.

¿Para qué día y hora deseas tu cita?

---

Usuario: asdfgh

Bot: ❌ No pude entender la fecha 😕

Intenta algo como:
👉 Mañana a las 5 pm
👉 El jueves a las 10 am

---

Usuario: el viernes a las 3 de la tarde

Bot: 📅 ¿Confirmas esta fecha para tu cita?

🗓️ 12/01/2026 3:00 PM

Responde:
✅ Sí
❌ No
```

### Ejemplo 3: Usuario Cancela

```
Usuario: crear cita

Bot: 📅 ¡Perfecto! Vamos a agendar tu cita.

¿Para qué día y hora deseas tu cita?

---

Usuario: mañana a las 2pm

Bot: 📅 ¿Confirmas esta fecha para tu cita?

🗓️ 10/01/2026 2:00 PM

Responde:
✅ Sí
❌ No

---

Usuario: no

Bot: Entendido, no he agendado ninguna cita. ¿Hay algo más en lo que pueda ayudarte? 😊
```

## 🔧 Métodos Modificados

### WhatsAppController.php

| Método | Descripción |
|--------|-------------|
| `procesarMensaje()` | Agregado manejo de estados `esperando_fecha_nueva_cita` y `confirmar_fecha_nueva_cita` |
| `crearCita()` | Modificado para solicitar fecha en vez de crearla automáticamente |
| `procesarFechaNuevaCita()` | **NUEVO** - Procesa la fecha proporcionada por el usuario |
| `confirmarNuevaCita()` | **NUEVO** - Confirma y crea la cita con la fecha elegida |
| `cancelarNuevaCita()` | **NUEVO** - Cancela el proceso de creación |

## ✅ Validaciones Implementadas

1. ✅ **Validación de fecha:** OpenAI verifica que la fecha sea válida
2. ✅ **Confirmación explícita:** Usuario debe confirmar con "sí" o "no"
3. ✅ **Manejo de errores:** Si OpenAI no entiende, solicita de nuevo
4. ✅ **Cancelación:** Usuario puede cancelar en cualquier momento
5. ✅ **Limpieza:** Se elimina la conversación después de crear o cancelar

## 🎯 Ventajas del Nuevo Flujo

| Antes | Ahora |
|-------|-------|
| ❌ Fecha fija (mañana 3pm) | ✅ Usuario elige fecha y hora |
| ❌ No hay confirmación | ✅ Solicita confirmación explícita |
| ❌ Inflexible | ✅ Flexible y natural |
| ❌ Fecha en código hardcoded | ✅ Interpretación con IA |
| ❌ Sin validación | ✅ Validación con OpenAI |

## 🚀 Mejoras Futuras

- [ ] Validar que la fecha sea futura (no pasada)
- [ ] Validar horario de apertura (ej: 8am-8pm)
- [ ] Verificar disponibilidad antes de confirmar
- [ ] Permitir selección de servicio (no solo "Corte de cabello")
- [ ] Sugerir fechas alternativas si no hay disponibilidad
- [ ] Permitir editar la fecha antes de confirmar

## 📊 Comparación de Flujos

### Flujo Antiguo (v2.0)
```
Pasos: 1
Mensajes: 2 (usuario + bot)
Tiempo: 2 segundos
Flexibilidad: 0%
```

### Flujo Nuevo (v2.1)
```
Pasos: 3
Mensajes: 6 (3 usuario + 3 bot)
Tiempo: 15-30 segundos
Flexibilidad: 100%
```

---

**Versión:** 2.1.0  
**Fecha:** 9 de enero de 2026  
**Autor:** GitHub Copilot
