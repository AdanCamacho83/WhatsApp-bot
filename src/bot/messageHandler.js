'use strict';

const TenantRepository    = require('../db/tenantRepository');
const ClientRepository    = require('../db/clientRepository');
const AppointmentRepository = require('../db/appointmentRepository');

const DAYS = ['Domingo','Lunes','Martes','Miércoles','Jueves','Viernes','Sábado'];

class MessageHandler {
  constructor(db) {
    this.tenants      = new TenantRepository(db);
    this.clients      = new ClientRepository(db);
    this.appointments = new AppointmentRepository(db);
    this.sessions     = new Map();
  }

  async handle(message) {
    const senderPhone = message.from.replace('@c.us', '').replace(/\D/g, '');
    const body = (message.body || '').trim();

    const tenant = this.tenants.findByPhone(senderPhone);
    if (tenant) {
      return this._handleTenantMessage(tenant, message, body);
    }

    return this._handleClientMessage(senderPhone, message, body);
  }

  _handleTenantMessage(tenant, message, body) {
    const lower = body.toLowerCase();
    if (lower === 'citas hoy') {
      return this._listAppointmentsToday(tenant);
    }
    if (lower === 'proximas citas' || lower === 'próximas citas') {
      return this._listUpcoming(tenant);
    }
    if (lower === 'ayuda') {
      return this._tenantHelp();
    }
    return this._tenantHelp();
  }

  _handleClientMessage(senderPhone, message, body) {
    const lower = body.toLowerCase();

    if (lower === 'hola' || lower === 'inicio' || lower === 'menu' || lower === 'menú' || lower === 'ayuda') {
      return this._mainMenu();
    }

    const actionCommands = ['agendar', 'mis citas', 'cancelar cita'];
    const isAction = actionCommands.includes(lower) || lower.startsWith('cancelar #');
    if (!isAction) {
      return this._mainMenu();
    }

    const tenants = this.tenants.findAll();
    if (tenants.length === 0) {
      return '⚠️ No hay empresas registradas todavía. Contacta al administrador.';
    }

    if (lower === 'agendar') {
      return this._startBooking(senderPhone, tenants);
    }
    if (lower === 'mis citas') {
      return this._listClientAppointments(senderPhone, tenants);
    }
    if (lower === 'cancelar cita') {
      return this._cancelPrompt(senderPhone, tenants);
    }
    if (lower.startsWith('cancelar #')) {
      const id = parseInt(lower.replace('cancelar #', ''), 10);
      return this._cancelAppointment(senderPhone, id, tenants);
    }
    return this._mainMenu();
  }

  _mainMenu() {
    return (
      '👋 ¡Hola! Bienvenido al sistema de citas.\n\n' +
      'Puedes usar los siguientes comandos:\n\n' +
      '📅 *agendar* — Agendar una nueva cita\n' +
      '📋 *mis citas* — Ver tus citas pendientes\n' +
      '❌ *cancelar cita* — Cancelar una cita\n' +
      '❓ *ayuda* — Ver este menú\n'
    );
  }

  _tenantHelp() {
    return (
      '🏢 Panel de empresa\n\n' +
      '*citas hoy* — Ver citas de hoy\n' +
      '*próximas citas* — Ver próximas citas\n' +
      '*ayuda* — Ver este menú\n'
    );
  }

  _startBooking(senderPhone, tenants) {
    if (tenants.length === 1) {
      return (
        `Para agendar una cita con *${tenants[0].name}*, envía:\n\n` +
        `📝 *agendar YYYY-MM-DD HH:MM servicio*\n\n` +
        `Ejemplo:\n_agendar 2026-04-10 10:00 Consulta general_`
      );
    }
    const list = tenants.map((t, i) => `${i + 1}. ${t.name}`).join('\n');
    return `Selecciona una empresa enviando su número:\n\n${list}`;
  }

  _listClientAppointments(senderPhone, tenants) {
    const results = [];
    for (const tenant of tenants) {
      const client = this.clients.findByPhone(tenant.id, senderPhone);
      if (!client) continue;
      const appts = this.appointments.findByClient(client.id).filter(
        (a) => a.status === 'pending' || a.status === 'confirmed'
      );
      for (const a of appts) {
        results.push(
          `📅 *${a.appointment_date}* a las *${a.appointment_time}*\n` +
          `   🏢 ${tenant.name}\n` +
          `   💼 ${a.service}\n` +
          `   Estado: ${a.status}\n` +
          `   ID: #${a.id}`
        );
      }
    }
    if (results.length === 0) {
      return '📭 No tienes citas pendientes.';
    }
    return '📋 Tus citas:\n\n' + results.join('\n\n');
  }

  _cancelPrompt(senderPhone, tenants) {
    const msg = this._listClientAppointments(senderPhone, tenants);
    if (msg.startsWith('📭')) return msg;
    return (
      msg +
      '\n\nPara cancelar, envía: *cancelar #ID*\nEjemplo: _cancelar #3_'
    );
  }

  _cancelAppointment(senderPhone, id, tenants) {
    const appt = this.appointments.findById(id);
    if (!appt) return '❌ Cita no encontrada.';

    const tenant = tenants.find((t) => t.id === appt.tenant_id);
    if (!tenant) return '❌ Cita no encontrada.';

    const client = this.clients.findByPhone(tenant.id, senderPhone);
    if (!client || client.id !== appt.client_id) {
      return '❌ No tienes permiso para cancelar esta cita.';
    }
    if (appt.status === 'cancelled') return '⚠️ Esta cita ya está cancelada.';
    if (appt.status === 'completed') return '⚠️ Esta cita ya fue completada.';

    this.appointments.updateStatus(id, 'cancelled');
    return `✅ Cita #${id} del ${appt.appointment_date} a las ${appt.appointment_time} cancelada correctamente.`;
  }

  _listAppointmentsToday(tenant) {
    const today = new Date().toISOString().slice(0, 10);
    const appts = this.appointments.findByTenant(tenant.id, { date: today });
    if (appts.length === 0) return `📭 No hay citas para hoy (${today}).`;
    const lines = appts.map(
      (a) => `⏰ ${a.appointment_time} — ${a.client_name} — ${a.service} [${a.status}]`
    );
    return `📋 Citas de hoy (${today}):\n\n` + lines.join('\n');
  }

  _listUpcoming(tenant) {
    const today = new Date().toISOString().slice(0, 10);
    const appts = this.appointments.findUpcoming(tenant.id, today);
    if (appts.length === 0) return '📭 No hay próximas citas.';
    const lines = appts.map(
      (a) => `📅 ${a.appointment_date} ${a.appointment_time} — ${a.client_name} — ${a.service} [${a.status}]`
    );
    return '📋 Próximas citas:\n\n' + lines.join('\n');
  }
}

module.exports = MessageHandler;
