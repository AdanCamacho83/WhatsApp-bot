'use strict';

const express = require('express');
const ClientRepository = require('../db/clientRepository');
const TenantRepository = require('../db/tenantRepository');

module.exports = function clientRoutes(db) {
  const router  = express.Router({ mergeParams: true });
  const clients = new ClientRepository(db);
  const tenants = new TenantRepository(db);

  function ensureTenant(req, res, next) {
    const tenant = tenants.findById(req.params.tenantId);
    if (!tenant) return res.status(404).json({ error: 'Empresa no encontrada' });
    req.tenant = tenant;
    next();
  }

  router.get('/', ensureTenant, (req, res) => {
    res.json(clients.findAll(req.params.tenantId));
  });

  router.post('/', ensureTenant, (req, res) => {
    const { name, phone, email } = req.body;
    if (!name || !phone) {
      return res.status(400).json({ error: 'name y phone son requeridos' });
    }
    try {
      const client = clients.create({ tenantId: req.tenant.id, name, phone, email });
      res.status(201).json(client);
    } catch (err) {
      if (err.message && err.message.includes('UNIQUE')) {
        return res.status(409).json({ error: 'El teléfono ya está registrado para esta empresa' });
      }
      res.status(500).json({ error: err.message });
    }
  });

  router.get('/:clientId', ensureTenant, (req, res) => {
    const client = clients.findById(req.params.clientId);
    if (!client || client.tenant_id !== req.tenant.id) {
      return res.status(404).json({ error: 'Cliente no encontrado' });
    }
    res.json(client);
  });

  router.put('/:clientId', ensureTenant, (req, res) => {
    const client = clients.findById(req.params.clientId);
    if (!client || client.tenant_id !== req.tenant.id) {
      return res.status(404).json({ error: 'Cliente no encontrado' });
    }
    const updated = clients.update(req.params.clientId, req.body);
    res.json(updated);
  });

  router.delete('/:clientId', ensureTenant, (req, res) => {
    const client = clients.findById(req.params.clientId);
    if (!client || client.tenant_id !== req.tenant.id) {
      return res.status(404).json({ error: 'Cliente no encontrado' });
    }
    clients.delete(req.params.clientId);
    res.status(204).send();
  });

  return router;
};
