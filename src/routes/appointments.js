'use strict';

const express = require('express');
const AppointmentRepository = require('../db/appointmentRepository');
const ClientRepository      = require('../db/clientRepository');
const TenantRepository      = require('../db/tenantRepository');

module.exports = function appointmentRoutes(db) {
  const router       = express.Router({ mergeParams: true });
  const appointments = new AppointmentRepository(db);
  const clients      = new ClientRepository(db);
  const tenants      = new TenantRepository(db);

  function ensureTenant(req, res, next) {
    const tenant = tenants.findById(req.params.tenantId);
    if (!tenant) return res.status(404).json({ error: 'Empresa no encontrada' });
    req.tenant = tenant;
    next();
  }

  router.get('/', ensureTenant, (req, res) => {
    const { status, date } = req.query;
    res.json(appointments.findByTenant(req.tenant.id, { status, date }));
  });

  router.post('/', ensureTenant, (req, res) => {
    const { clientId, service, appointmentDate, appointmentTime, durationMinutes, notes } = req.body;
    if (!clientId || !service || !appointmentDate || !appointmentTime) {
      return res.status(400).json({ error: 'clientId, service, appointmentDate y appointmentTime son requeridos' });
    }
    const client = clients.findById(clientId);
    if (!client || client.tenant_id !== req.tenant.id) {
      return res.status(404).json({ error: 'Cliente no encontrado' });
    }
    const conflict = appointments.hasConflict(
      req.tenant.id, appointmentDate, appointmentTime, durationMinutes || 60
    );
    if (conflict) {
      return res.status(409).json({ error: 'Ya existe una cita en ese horario' });
    }
    const appt = appointments.create({
      tenantId: req.tenant.id,
      clientId,
      service,
      appointmentDate,
      appointmentTime,
      durationMinutes: durationMinutes || 60,
      notes,
    });
    res.status(201).json(appt);
  });

  router.get('/:appointmentId', ensureTenant, (req, res) => {
    const appt = appointments.findById(req.params.appointmentId);
    if (!appt || appt.tenant_id !== req.tenant.id) {
      return res.status(404).json({ error: 'Cita no encontrada' });
    }
    res.json(appt);
  });

  router.put('/:appointmentId', ensureTenant, (req, res) => {
    const appt = appointments.findById(req.params.appointmentId);
    if (!appt || appt.tenant_id !== req.tenant.id) {
      return res.status(404).json({ error: 'Cita no encontrada' });
    }
    const updated = appointments.update(req.params.appointmentId, req.body);
    res.json(updated);
  });

  router.patch('/:appointmentId/estado', ensureTenant, (req, res) => {
    const appt = appointments.findById(req.params.appointmentId);
    if (!appt || appt.tenant_id !== req.tenant.id) {
      return res.status(404).json({ error: 'Cita no encontrada' });
    }
    const { status } = req.body;
    const valid = ['pending', 'confirmed', 'cancelled', 'completed'];
    if (!valid.includes(status)) {
      return res.status(400).json({ error: `Estado inválido. Valores permitidos: ${valid.join(', ')}` });
    }
    const updated = appointments.updateStatus(req.params.appointmentId, status);
    res.json(updated);
  });

  router.delete('/:appointmentId', ensureTenant, (req, res) => {
    const appt = appointments.findById(req.params.appointmentId);
    if (!appt || appt.tenant_id !== req.tenant.id) {
      return res.status(404).json({ error: 'Cita no encontrada' });
    }
    appointments.delete(req.params.appointmentId);
    res.status(204).send();
  });

  return router;
};
