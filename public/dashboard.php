<?php
require_once __DIR__ . '/../vendor/autoload.php';

use App\Utils\SessionManager;
use App\Database\Database;
use App\Models\Empresa;

// Requerir sesión activa
SessionManager::requerirSesion();

// Obtener datos del usuario
$usuario = SessionManager::obtenerUsuario();

// Cargar configuración e inicializar base de datos
$config = require __DIR__ . '/../config/config.php';
Database::setConfig($config['database']);

// Obtener información completa de la empresa
$empresaModel = new Empresa();
$empresa = $empresaModel->obtenerPorId($usuario['id']);

$codigoEmpresa = $empresa['codigo_empresa'] ?? 'N/A';
$nombreEmpresa = $empresa['nombre_empresa'] ?? 'Mi Empresa';
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Dashboard - Agenda de Citas</title>
  <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.8/index.global.min.js"></script>
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: Arial, sans-serif; background: #f8f9fa; }
    
    /* Header */
    .header {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      color: white;
      padding: 15px 30px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    
    .header-left {
      display: flex;
      align-items: center;
      gap: 15px;
    }
    
    .header-left h1 {
      font-size: 24px;
      font-weight: 600;
    }
    
    .header-right {
      display: flex;
      align-items: center;
      gap: 20px;
    }
    
    .user-info {
      display: flex;
      flex-direction: column;
      align-items: flex-end;
      gap: 5px;
    }
    
    .user-info .user-name {
      font-weight: 600;
      font-size: 14px;
    }
    
    .user-info .company-name {
      font-size: 12px;
      opacity: 0.9;
    }
    
    .codigo-container {
      display: flex;
      align-items: center;
      gap: 8px;
    }
    
    .codigo-badge {
      background: rgba(255, 255, 255, 0.25);
      padding: 6px 14px;
      border-radius: 20px;
      font-weight: bold;
      font-size: 14px;
      letter-spacing: 1.5px;
      cursor: pointer;
      transition: all 0.2s;
      border: 1px solid rgba(255, 255, 255, 0.3);
      display: flex;
      align-items: center;
      gap: 6px;
    }
    
    .codigo-badge:hover {
      background: rgba(255, 255, 255, 0.35);
      transform: scale(1.05);
    }
    
    .codigo-badge:active {
      transform: scale(0.98);
    }
    
    .codigo-label {
      font-size: 11px;
      opacity: 0.85;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }
    
    .btn-logout {
      background: rgba(255, 255, 255, 0.2);
      border: 1px solid rgba(255, 255, 255, 0.3);
      color: white;
      padding: 8px 20px;
      border-radius: 8px;
      cursor: pointer;
      font-size: 14px;
      font-weight: 500;
      display: flex;
      align-items: center;
      gap: 8px;
      transition: all 0.3s ease;
      text-decoration: none;
    }
    
    .btn-logout:hover {
      background: rgba(255, 255, 255, 0.3);
      border-color: rgba(255, 255, 255, 0.5);
      transform: translateY(-2px);
    }
    
    /* Container */
    .container {
      padding: 30px;
      max-width: 1200px;
      margin: 0 auto;
    }
    
    .calendar-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
      background: white;
      padding: 20px;
      border-radius: 10px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    }
    
    .calendar-title {
      font-size: 20px;
      font-weight: 600;
      color: #333;
    }
    
    #calendar { 
      background: white; 
      padding: 20px; 
      border-radius: 10px;
      box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    }
    
    /* Menú contextual */
    .context-menu {
      position: absolute;
      background: white;
      border: 1px solid #ddd;
      border-radius: 6px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.15);
      padding: 8px 0;
      min-width: 180px;
      z-index: 1000;
      display: none;
    }
    
    .context-menu.active {
      display: block;
    }
    
    .context-menu-item {
      padding: 10px 16px;
      cursor: pointer;
      display: flex;
      align-items: center;
      gap: 10px;
      font-size: 14px;
      color: #333;
      transition: background 0.2s;
    }
    
    .context-menu-item:hover {
      background: #f5f5f5;
    }
    
    .context-menu-item.danger:hover {
      background: #fee;
      color: #c00;
    }
    
    .context-menu-divider {
      height: 1px;
      background: #e0e0e0;
      margin: 4px 0;
    }

    /* Modal para editar fecha/hora */
    .modal-overlay {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.5);
      z-index: 2000;
      justify-content: center;
      align-items: center;
    }

    .modal-overlay.active {
      display: flex;
    }

    .modal-content {
      background: white;
      border-radius: 12px;
      padding: 24px;
      max-width: 400px;
      width: 90%;
      box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
      animation: modalFadeIn 0.2s ease-out;
    }

    @keyframes modalFadeIn {
      from {
        opacity: 0;
        transform: translateY(-20px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .modal-header {
      font-size: 20px;
      font-weight: bold;
      margin-bottom: 20px;
      color: #333;
    }

    .form-group {
      margin-bottom: 20px;
    }

    .form-group label {
      display: block;
      margin-bottom: 8px;
      font-weight: 500;
      color: #555;
      font-size: 14px;
    }

    .form-group input {
      width: 100%;
      padding: 12px;
      border: 2px solid #e0e0e0;
      border-radius: 8px;
      font-size: 16px;
      box-sizing: border-box;
      transition: border-color 0.2s;
    }

    .form-group input:focus {
      outline: none;
      border-color: #198754;
    }

    .modal-buttons {
      display: flex;
      gap: 12px;
      margin-top: 24px;
    }

    .btn {
      flex: 1;
      padding: 12px;
      border: none;
      border-radius: 8px;
      font-size: 16px;
      font-weight: 500;
      cursor: pointer;
      transition: all 0.2s;
    }

    .btn-primary {
      background: #667eea;
      color: white;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .btn-primary:hover {
      background: #5568d3;
      transform: translateY(-1px);
      box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
    }

    .btn-secondary {
      background: #6c757d;
      color: white;
    }

    .btn-secondary:hover {
      background: #5c636a;
    }
  </style>
</head>
<body>

<!-- Header -->
<div class="header">
  <div class="header-left">
    <i class="fas fa-calendar-check" style="font-size: 28px;"></i>
    <h1>Dashboard de Citas</h1>
  </div>
  <div class="header-right">
    <div class="user-info">
      <span class="user-name">
        <i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($usuario['usuario']); ?>
      </span>
      <span class="company-name">
        <i class="fas fa-building"></i> <?php echo htmlspecialchars($nombreEmpresa); ?>
      </span>
      <div class="codigo-container">
        <span class="codigo-label">Código:</span>
        <span class="codigo-badge" onclick="copiarCodigo()" title="Click para copiar">
          <i class="fas fa-tag"></i>
          <span id="codigoEmpresa"><?php echo htmlspecialchars($codigoEmpresa); ?></span>
        </span>
      </div>
    </div>
    <a href="cerrar_sesion.php" class="btn-logout" onclick="return confirm('¿Estás seguro de que deseas cerrar sesión?');">
      <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
    </a>
  </div>
</div>

<!-- Container -->
<div class="container">
  <div class="calendar-header">
    <h2 class="calendar-title"><i class="fas fa-calendar-alt"></i> Calendario de Citas</h2>
    <div style="display: flex; gap: 10px;">
      <button class="btn btn-secondary" id="btnHorarios">
        <i class="fas fa-clock"></i> Horarios
      </button>
      <button class="btn btn-primary" id="btnNuevaCita">
        <i class="fas fa-plus"></i> Nueva Cita
      </button>
    </div>
  </div>

  <div id="calendar"></div>
</div>

<!-- Menú contextual -->
<div id="contextMenu" class="context-menu">
  <div class="context-menu-item" id="menuVerDetalles">
    <span>ℹ️</span> Ver detalles
  </div>
  <div class="context-menu-divider"></div>
  <div class="context-menu-item" id="menuEditarHorario">
    <span>🕐</span> Editar horario
  </div>
  <div class="context-menu-divider"></div>
  <div class="context-menu-item danger" id="menuCancelar">
    <span>❌</span> Cancelar cita
  </div>
</div>

<!-- Modal para editar fecha y hora -->
<div id="modalEditarFecha" class="modal-overlay">
  <div class="modal-content">
    <div class="modal-header">🕐 Editar fecha y hora</div>
    <div class="form-group">
      <label for="inputFecha">📅 Fecha</label>
      <input type="date" id="inputFecha" required>
    </div>
    <div class="form-group">
      <label for="inputHora">⏰ Hora</label>
      <input type="time" id="inputHora" required>
    </div>
    <div class="modal-buttons">
      <button class="btn btn-secondary" id="btnCancelarModal">Cancelar</button>
      <button class="btn btn-primary" id="btnGuardarFecha">Guardar</button>
    </div>
  </div>
</div>

<!-- Modal para crear nueva cita -->
<div id="modalNuevaCita" class="modal-overlay">
  <div class="modal-content">
    <div class="modal-header">➕ Nueva Cita</div>
    <div class="form-group">
      <label for="inputTelefono">📞 Teléfono (11 dígitos) *</label>
      <input type="tel" id="inputTelefono" placeholder="Ej: 52123456789" maxlength="11" pattern="[0-9]{11}" required>
      <small style="color: #666; font-size: 12px;">Formato: código país + número (sin espacios ni guiones)</small>
    </div>
    <div class="form-group">
      <label for="inputServicioNuevo">💈 Servicio *</label>
      <input type="text" id="inputServicioNuevo" placeholder="Ej: Corte de cabello" required>
    </div>
    <div class="form-group">
      <label for="inputFechaNueva">📅 Fecha *</label>
      <input type="date" id="inputFechaNueva" required>
    </div>
    <div class="form-group">
      <label for="inputHoraNueva">⏰ Hora *</label>
      <input type="time" id="inputHoraNueva" required>
    </div>
    <div class="modal-buttons">
      <button class="btn btn-secondary" id="btnCancelarNuevaCita">Cancelar</button>
      <button class="btn btn-primary" id="btnGuardarNuevaCita">Crear Cita</button>
    </div>
  </div>
</div>

<!-- Modal para gestionar horarios -->
<div id="modalHorarios" class="modal-overlay">
  <div class="modal-content" style="max-width: 700px;">
    <div class="modal-header">🕐 Configurar Horarios de Atención</div>
    
    <div style="max-height: 500px; overflow-y: auto; padding: 10px 0;">
      <table style="width: 100%; border-collapse: collapse;">
        <thead>
          <tr style="background: #f8f9fa; border-bottom: 2px solid #dee2e6;">
            <th style="padding: 10px; text-align: left;">Día</th>
            <th style="padding: 10px; text-align: center;">Descanso</th>
            <th style="padding: 10px; text-align: center;">Apertura</th>
            <th style="padding: 10px; text-align: center;">Cierre</th>
          </tr>
        </thead>
        <tbody id="horariosDias">
          <!-- Se llenará dinámicamente con JavaScript -->
        </tbody>
      </table>
      
      <!-- Checkbox para aplicar horario de lunes al resto -->
      <div id="aplicarRestoDiasContainer" style="display: none; background: #e7f3ff; padding: 12px; border-radius: 8px; margin-top: 10px; border-left: 4px solid #0066cc;">
        <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; font-size: 14px;">
          <input type="checkbox" id="aplicarRestoDias" style="width: 18px; height: 18px; cursor: pointer;">
          <span style="font-weight: 500; color: #0066cc;">
            <i class="fas fa-copy"></i> Aplicar este horario al resto de días
          </span>
        </label>
        <small style="color: #666; display: block; margin-left: 28px; margin-top: 5px;">
          Copiará la apertura y cierre de lunes a todos los demás días
        </small>
      </div>
      
      <div style="margin-top: 30px; padding-top: 20px; border-top: 2px solid #dee2e6;">
        <h4 style="margin-bottom: 15px; color: #333;">⚙️ Configuración General</h4>
        <div style="display: flex; justify-content: center;">
          <div class="form-group" style="max-width: 300px; width: 100%;">
            <label for="tiempoAtencion" style="display: block; margin-bottom: 8px;">
              ⏱️ Tiempo de Atención por Cita
              <span style="background: #0066cc; color: white; padding: 2px 8px; border-radius: 4px; font-size: 11px; margin-left: 8px;">FORMATO 24H</span>
            </label>
            <input type="text" id="tiempoAtencion" class="form-control" 
                   placeholder="HH:MM (Ej: 01:30)" 
                   pattern="^([0-1][0-9]|2[0-3]):[0-5][0-9]$"
                   maxlength="5"
                   style="font-size: 18px; padding: 12px; text-align: center; font-weight: 600; letter-spacing: 1px;">
            <small style="color: #666; font-size: 12px; display: block; margin-top: 5px; text-align: center;">
              <strong>Ejemplos:</strong> 00:30 (30 min) | 01:00 (1 hora) | 01:30 (1h 30min)
            </small>
          </div>
        </div>
      </div>
    </div>
    
    <div class="modal-buttons">
      <button class="btn btn-secondary" id="btnCancelarHorarios">Cancelar</button>
      <button class="btn btn-primary" id="btnGuardarHorarios">
        <i class="fas fa-save"></i> Guardar Horarios
      </button>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  var calendarEl = document.getElementById('calendar');
  var contextMenu = document.getElementById('contextMenu');
  var currentEvent = null;

  var calendar = new FullCalendar.Calendar(calendarEl, {
    initialView: 'dayGridMonth',
    locale: 'es',
    editable: true,
    eventDurationEditable: true,
    events: 'citas.php',
    
    // Callback para depuración de eventos
    eventDidMount: function(info) {
      console.log('Evento cargado:', info.event.title, info.event.start);
    },
    
    // Callback para errores al cargar eventos
    eventSourceFailure: function(error) {
      console.error('Error al cargar eventos:', error);
      alert('Error al cargar las citas. Revisa la consola del navegador.');
    },
    
    // Mostrar menú contextual al hacer clic en evento
    eventClick: function(info) {
      info.jsEvent.preventDefault();
      currentEvent = info.event;
      
      // Posicionar menú en la posición del clic
      contextMenu.style.left = info.jsEvent.pageX + 'px';
      contextMenu.style.top = info.jsEvent.pageY + 'px';
      contextMenu.classList.add('active');
    },

    // Drag and drop para reprogramar
    eventDrop: function(info) {
      if (!confirm("¿Deseas reprogramar esta cita?")) {
        info.revert();
        return;
      }

      // Preservar la hora original: extraer hora de oldEvent y aplicar a la nueva fecha
      var horaOriginal = info.oldEvent.start.getHours();
      var minutosOriginales = info.oldEvent.start.getMinutes();
      
      // Crear nueva fecha con la hora original
      var nuevaFecha = new Date(info.event.start);
      nuevaFecha.setHours(horaOriginal, minutosOriginales, 0, 0);

      // Formatear fecha en formato MySQL sin conversión UTC
      var year = nuevaFecha.getFullYear();
      var month = String(nuevaFecha.getMonth() + 1).padStart(2, '0');
      var day = String(nuevaFecha.getDate()).padStart(2, '0');
      var hours = String(nuevaFecha.getHours()).padStart(2, '0');
      var minutes = String(nuevaFecha.getMinutes()).padStart(2, '0');
      var fechaFormateada = year + '-' + month + '-' + day + ' ' + hours + ':' + minutes + ':00';

      fetch('reprogramar.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          id: info.event.id,
          fecha: fechaFormateada
        })
      })
      .then(res => res.json())
      .then(data => {
        if (!data.ok) {
          alert(data.error || 'Error al reprogramar');
          info.revert();
        } else {
          alert('Cita reprogramada y WhatsApp enviado');
          info.event.setStart(nuevaFecha);
        }
      })
      .catch(err => {
        console.error(err);
        alert('Error de conexión');
        info.revert();
      });
    }
  });

  calendar.render();

  // Botón Nueva Cita
  var modalNuevaCita = document.getElementById('modalNuevaCita');
  var inputTelefono = document.getElementById('inputTelefono');
  var inputServicioNuevo = document.getElementById('inputServicioNuevo');
  var inputFechaNueva = document.getElementById('inputFechaNueva');
  var inputHoraNueva = document.getElementById('inputHoraNueva');

  document.getElementById('btnNuevaCita').addEventListener('click', function() {
    // Limpiar formulario
    inputTelefono.value = '';
    inputServicioNuevo.value = '';
    inputFechaNueva.value = '';
    inputHoraNueva.value = '';
    modalNuevaCita.classList.add('active');
  });

  // Cancelar modal nueva cita
  document.getElementById('btnCancelarNuevaCita').addEventListener('click', function() {
    modalNuevaCita.classList.remove('active');
  });

  // Cerrar modal al hacer clic fuera
  modalNuevaCita.addEventListener('click', function(e) {
    if (e.target === modalNuevaCita) {
      modalNuevaCita.classList.remove('active');
    }
  });

  // Guardar nueva cita
  document.getElementById('btnGuardarNuevaCita').addEventListener('click', function() {
    var telefono = inputTelefono.value.trim();
    var servicio = inputServicioNuevo.value.trim();
    var fecha = inputFechaNueva.value;
    var hora = inputHoraNueva.value;

    // Validar campos obligatorios
    if (!telefono || !servicio || !fecha || !hora) {
      alert('⚠️ Todos los campos son obligatorios');
      return;
    }

    // Validar que el teléfono tenga exactamente 11 dígitos numéricos
    if (!/^\d{11}$/.test(telefono)) {
      alert('⚠️ El teléfono debe contener exactamente 11 dígitos numéricos\nPuede que falte la lada\n\nEjemplo: 52123456789');
      return;
    }

    // Crear fecha completa
    var fechaHoraString = fecha + ' ' + hora + ':00';
    var fechaHora = new Date(fecha + 'T' + hora);

    if (isNaN(fechaHora.getTime())) {
      alert('⚠️ Fecha u hora inválida');
      return;
    }

    // Validar que no sea fecha pasada
    var ahora = new Date();
    if (fechaHora <= ahora) {
      alert('⚠️ No puedes crear una cita en el pasado o en la hora actual.\n\nPor favor selecciona una fecha y hora futura.');
      return;
    }

    // Formatear teléfono con prefijo whatsapp:+
    var telefonoFormateado = 'whatsapp:+' + telefono;

    // Cerrar modal
    modalNuevaCita.classList.remove('active');

    // Enviar solicitud
    fetch('crear_cita_dashboard.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        telefono: telefonoFormateado,
        servicio: servicio,
        fecha: fechaHoraString
      })
    })
    .then(res => res.json())
    .then(data => {
      if (!data.ok) {
        alert('❌ Error: ' + (data.error || 'No se pudo crear la cita'));
      } else {
        alert('✅ Cita creada exitosamente');
        calendar.refetchEvents();
      }
    })
    .catch(err => {
      console.error(err);
      alert('❌ Error de conexión');
    });
  });

  // Cerrar menú al hacer clic fuera
  document.addEventListener('click', function(e) {
    if (!contextMenu.contains(e.target) && !e.target.closest('.fc-event')) {
      contextMenu.classList.remove('active');
    }
  });

  // Opción: Ver detalles
  document.getElementById('menuVerDetalles').addEventListener('click', function() {
    contextMenu.classList.remove('active');
    
    if (!currentEvent) return;
    
    // Obtener información del evento
    var fechaHora = currentEvent.start;
    var servicio = currentEvent.title || 'Sin servicio';
    var telefono = currentEvent.extendedProps?.telefono || 'No disponible';
    var estado = currentEvent.extendedProps?.estado || 'activa';
    
    // Limpiar prefijo 'whatsapp:' del teléfono
    if (telefono.startsWith('whatsapp:')) {
      telefono = telefono.replace('whatsapp:', '');
    }
    
    // Formatear fecha y hora
    var fechaFormateada = fechaHora.toLocaleDateString('es-MX', {
      weekday: 'long',
      year: 'numeric',
      month: 'long',
      day: 'numeric'
    });
    var horaFormateada = fechaHora.toLocaleTimeString('es-MX', {
      hour: '2-digit',
      minute: '2-digit',
      hour12: true
    });
    
    // Mostrar detalles
    var mensaje = '📋 DETALLES DE LA CITA\n\n' +
                  '📞 Teléfono: ' + telefono + '\n\n' +
                  '📅 Fecha: ' + fechaFormateada + '\n\n' +
                  '⏰ Hora: ' + horaFormateada + '\n\n' +
                  '💈 Servicio: ' + servicio + '\n\n' +
                  '📊 Estado: ' + estado.toUpperCase();
    
    alert(mensaje);
  });

  // Opción: Editar horario
  var modalEditarFecha = document.getElementById('modalEditarFecha');
  var inputFecha = document.getElementById('inputFecha');
  var inputHora = document.getElementById('inputHora');
  
  document.getElementById('menuEditarHorario').addEventListener('click', function() {
    contextMenu.classList.remove('active');
    
    if (!currentEvent) return;
    
    // Obtener fecha y hora actuales del evento
    var fechaActual = currentEvent.start;
    
    // Establecer valores por defecto en los inputs
    var year = fechaActual.getFullYear();
    var month = String(fechaActual.getMonth() + 1).padStart(2, '0');
    var day = String(fechaActual.getDate()).padStart(2, '0');
    var hours = String(fechaActual.getHours()).padStart(2, '0');
    var minutes = String(fechaActual.getMinutes()).padStart(2, '0');
    
    inputFecha.value = year + '-' + month + '-' + day;
    inputHora.value = hours + ':' + minutes;
    
    // Mostrar modal
    modalEditarFecha.classList.add('active');
  });

  // Cancelar modal
  document.getElementById('btnCancelarModal').addEventListener('click', function() {
    modalEditarFecha.classList.remove('active');
  });

  // Cerrar modal al hacer clic fuera
  modalEditarFecha.addEventListener('click', function(e) {
    if (e.target === modalEditarFecha) {
      modalEditarFecha.classList.remove('active');
    }
  });

  // Guardar nueva fecha y hora
  document.getElementById('btnGuardarFecha').addEventListener('click', function() {
    if (!currentEvent) return;
    
    var fechaSeleccionada = inputFecha.value;
    var horaSeleccionada = inputHora.value;
    
    if (!fechaSeleccionada || !horaSeleccionada) {
      alert('Por favor selecciona fecha y hora');
      return;
    }
    
    // Crear fecha en formato local sin conversión a UTC
    var fechaHoraString = fechaSeleccionada + ' ' + horaSeleccionada + ':00';
    var nuevaFecha = new Date(fechaSeleccionada + 'T' + horaSeleccionada);
    
    if (isNaN(nuevaFecha.getTime())) {
      alert('Fecha u hora inválida');
      return;
    }
    
    // Validar que no sea fecha u hora pasada (incluyendo el mismo día con hora anterior)
    var ahora = new Date();
    if (nuevaFecha <= ahora) {
      alert('No puedes programar una cita en el pasado o en la hora actual.\n\nPor favor selecciona una fecha y hora futura.');
      return;
    }
    
    // Cerrar modal
    modalEditarFecha.classList.remove('active');
    
    // Enviar solicitud en formato MySQL sin conversión a UTC
    fetch('reprogramar.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        id: currentEvent.id,
        fecha: fechaHoraString
      })
    })
    .then(res => res.json())
    .then(data => {
      if (!data.ok) {
        alert(data.error || 'Error al reprogramar');
      } else {
        alert('✅ Cita reprogramada exitosamente');
        currentEvent.setStart(nuevaFecha);
        calendar.refetchEvents();
      }
    })
    .catch(err => {
      console.error(err);
      alert('Error de conexión');
    });
  });

  // Opción: Cancelar cita
  document.getElementById('menuCancelar').addEventListener('click', function() {
    contextMenu.classList.remove('active');
    
    if (!currentEvent) return;
    
    if (!confirm('¿Estás seguro de cancelar esta cita?\n\n' + currentEvent.title + '\n' + currentEvent.start.toLocaleString())) {
      return;
    }
    
    fetch('cancelar_cita.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        id: currentEvent.id
      })
    })
    .then(res => res.json())
    .then(data => {
      if (!data.ok) {
        alert(data.error || 'Error al cancelar');
      } else {
        alert('❌ Cita cancelada exitosamente');
        currentEvent.remove();
        calendar.refetchEvents();
      }
    })
    .catch(err => {
      console.error(err);
      alert('Error de conexión');
    });
  });
});

// Función para copiar código de empresa al portapapeles
function copiarCodigo() {
  const codigo = document.getElementById('codigoEmpresa').textContent;
  
  navigator.clipboard.writeText(codigo).then(() => {
    // Mostrar feedback visual
    const badge = document.querySelector('.codigo-badge');
    const originalHTML = badge.innerHTML;
    badge.innerHTML = '<i class="fas fa-check"></i> ¡Copiado!';
    badge.style.background = 'rgba(40, 167, 69, 0.3)';
    
    setTimeout(() => {
      badge.innerHTML = originalHTML;
      badge.style.background = 'rgba(255, 255, 255, 0.25)';
    }, 2000);
  }).catch(err => {
    console.error('Error al copiar:', err);
    alert('No se pudo copiar el código. Intenta seleccionarlo manualmente.');
  });
}

// ==================== GESTIÓN DE HORARIOS ====================
const modalHorarios = document.getElementById('modalHorarios');
const btnHorarios = document.getElementById('btnHorarios');
const btnCancelarHorarios = document.getElementById('btnCancelarHorarios');
const btnGuardarHorarios = document.getElementById('btnGuardarHorarios');

const dias = [
  { id: 'lunes', nombre: 'Lunes' },
  { id: 'martes', nombre: 'Martes' },
  { id: 'miercoles', nombre: 'Miércoles' },
  { id: 'jueves', nombre: 'Jueves' },
  { id: 'viernes', nombre: 'Viernes' },
  { id: 'sabado', nombre: 'Sábado' },
  { id: 'domingo', nombre: 'Domingo' }
];

// Abrir modal de horarios
btnHorarios.addEventListener('click', function() {
  cargarHorarios();
  modalHorarios.classList.add('active');
});

// Cerrar modal
btnCancelarHorarios.addEventListener('click', function() {
  modalHorarios.classList.remove('active');
});

modalHorarios.addEventListener('click', function(e) {
  if (e.target === modalHorarios) {
    modalHorarios.classList.remove('active');
  }
});

// Cargar horarios existentes
function cargarHorarios() {
  fetch('obtener_horarios.php')
    .then(res => res.json())
    .then(data => {
      if (data.ok) {
        renderizarDias(data.horarios);
      } else {
        renderizarDias(null);
      }
    })
    .catch(err => {
      console.error('Error al cargar horarios:', err);
      renderizarDias(null);
    });
}

// Renderizar tabla de días
function renderizarDias(horarios) {
  const tbody = document.getElementById('horariosDias');
  tbody.innerHTML = '';
  
  dias.forEach((dia, index) => {
    const apertura = horarios ? horarios[`${dia.id}_apertura`] : '00:00:00';
    const cierre = horarios ? horarios[`${dia.id}_cierre`] : '00:00:00';
    const esDescanso = apertura === '00:00:00' && cierre === '00:00:00';
    
    const tr = document.createElement('tr');
    tr.style.borderBottom = '1px solid #dee2e6';
    tr.innerHTML = `
      <td style="padding: 10px; font-weight: 500;">${dia.nombre}</td>
      <td style="padding: 10px; text-align: center;">
        <input type="checkbox" id="descanso_${dia.id}" ${esDescanso ? 'checked' : ''} 
               onchange="toggleDescanso('${dia.id}')" style="width: 20px; height: 20px; cursor: pointer;">
      </td>
      <td style="padding: 10px; text-align: center;">
        <input type="time" id="apertura_${dia.id}" value="${apertura.substring(0, 5)}" 
               ${esDescanso ? 'disabled' : ''} 
               ${dia.id === 'lunes' ? 'onchange="onLunesChange()"' : ''}
               style="padding: 8px; border: 1px solid #dee2e6; border-radius: 4px; width: 100px;">
      </td>
      <td style="padding: 10px; text-align: center;">
        <input type="time" id="cierre_${dia.id}" value="${cierre.substring(0, 5)}" 
               ${esDescanso ? 'disabled' : ''} 
               ${dia.id === 'lunes' ? 'onchange="onLunesChange()"' : ''}
               style="padding: 8px; border: 1px solid #dee2e6; border-radius: 4px; width: 100px;">
      </td>
    `;
    tbody.appendChild(tr);
    
    // Mostrar el checkbox de "aplicar al resto" después del lunes
    if (dia.id === 'lunes') {
      const aplicarContainer = document.getElementById('aplicarRestoDiasContainer');
      aplicarContainer.style.display = 'block';
      tbody.parentElement.parentElement.insertBefore(aplicarContainer, tbody.parentElement.nextSibling);
    }
  });
  
  // Cargar configuración general
  if (horarios) {
    document.getElementById('tiempoAtencion').value = horarios.tiempo_atencion ? horarios.tiempo_atencion.substring(0, 5) : '00:00';
  }
  
  // Configurar el checkbox de aplicar al resto
  configurarAplicarResto();
}

// Toggle día de descanso
function toggleDescanso(diaId) {
  const checkbox = document.getElementById(`descanso_${diaId}`);
  const apertura = document.getElementById(`apertura_${diaId}`);
  const cierre = document.getElementById(`cierre_${diaId}`);
  
  if (checkbox.checked) {
    apertura.value = '00:00';
    cierre.value = '00:00';
    apertura.disabled = true;
    cierre.disabled = true;
  } else {
    apertura.disabled = false;
    cierre.disabled = false;
  }
}

// Detectar cambios en lunes para mostrar/ocultar el checkbox de aplicar
function onLunesChange() {
  const lunesApertura = document.getElementById('apertura_lunes').value;
  const lunesCierre = document.getElementById('cierre_lunes').value;
  const aplicarCheckbox = document.getElementById('aplicarRestoDias');
  
  // Si el checkbox está marcado, aplicar automáticamente
  if (aplicarCheckbox && aplicarCheckbox.checked) {
    aplicarHorarioLunesAResto();
  }
}

// Configurar el comportamiento del checkbox "aplicar al resto"
function configurarAplicarResto() {
  const aplicarCheckbox = document.getElementById('aplicarRestoDias');
  
  if (aplicarCheckbox) {
    aplicarCheckbox.addEventListener('change', function() {
      if (this.checked) {
        aplicarHorarioLunesAResto();
      }
    });
  }
}

// Aplicar horario de lunes al resto de días
function aplicarHorarioLunesAResto() {
  const lunesApertura = document.getElementById('apertura_lunes').value;
  const lunesCierre = document.getElementById('cierre_lunes').value;
  
  // Validar que lunes tenga horarios válidos
  if (!lunesApertura || !lunesCierre || lunesApertura === '00:00' || lunesCierre === '00:00') {
    return;
  }
  
  // Aplicar a todos los demás días
  dias.forEach(dia => {
    if (dia.id !== 'lunes') {
      const descansoCheckbox = document.getElementById(`descanso_${dia.id}`);
      const apertura = document.getElementById(`apertura_${dia.id}`);
      const cierre = document.getElementById(`cierre_${dia.id}`);
      
      // Desmarcar descanso y habilitar campos
      if (descansoCheckbox.checked) {
        descansoCheckbox.checked = false;
        apertura.disabled = false;
        cierre.disabled = false;
      }
      
      // Copiar valores de lunes
      apertura.value = lunesApertura;
      cierre.value = lunesCierre;
    }
  });
}

// Auto-formatear campo de tiempo de atención (formato 24h)
document.getElementById('tiempoAtencion').addEventListener('input', function(e) {
  let value = e.target.value.replace(/[^\d]/g, ''); // Solo dígitos
  
  if (value.length >= 2) {
    value = value.substring(0, 2) + ':' + value.substring(2, 4);
  }
  
  e.target.value = value.substring(0, 5);
});

// Validar formato al salir del campo
document.getElementById('tiempoAtencion').addEventListener('blur', function(e) {
  const value = e.target.value;
  const regex = /^([0-1][0-9]|2[0-3]):[0-5][0-9]$/;
  
  if (value && !regex.test(value)) {
    alert('⚠️ Formato incorrecto. Use formato 24 horas: HH:MM (Ej: 01:30)');
    e.target.focus();
  }
});

// Guardar horarios
btnGuardarHorarios.addEventListener('click', function() {
  const horarios = {};
  
  // Recopilar horarios de cada día
  dias.forEach(dia => {
    const apertura = document.getElementById(`apertura_${dia.id}`).value + ':00';
    const cierre = document.getElementById(`cierre_${dia.id}`).value + ':00';
    
    horarios[`${dia.id}_apertura`] = apertura;
    horarios[`${dia.id}_cierre`] = cierre;
  });
  
  // Configuración general
  const tiempoAtencionValue = document.getElementById('tiempoAtencion').value;
  
  // Validar formato 24h
  const regex = /^([0-1][0-9]|2[0-3]):[0-5][0-9]$/;
  if (!tiempoAtencionValue || !regex.test(tiempoAtencionValue)) {
    alert('⚠️ Por favor, especifica el tiempo de atención en formato 24h (HH:MM)\nEjemplo: 01:30 para 1 hora 30 minutos');
    document.getElementById('tiempoAtencion').focus();
    return;
  }
  
  horarios.tiempo_atencion = tiempoAtencionValue + ':00';
  
  // Validar que tiempo_atencion no esté vacío
  if (!horarios.tiempo_atencion || horarios.tiempo_atencion === ':00' || horarios.tiempo_atencion === '00:00:00') {
    alert('⚠️ Por favor, especifica el tiempo de atención por cita');
    return;
  }
  
  // Enviar a servidor
  fetch('guardar_horarios.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(horarios)
  })
  .then(res => res.json())
  .then(data => {
    if (data.ok) {
      alert('✅ ' + data.mensaje);
      modalHorarios.classList.remove('active');
    } else {
      alert('❌ ' + data.error);
    }
  })
  .catch(err => {
    console.error('Error:', err);
    alert('❌ Error de conexión al guardar horarios');
  });
});
</script>
</body>
</html>