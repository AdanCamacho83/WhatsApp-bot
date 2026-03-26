'use strict';

const express = require('express');
const TenantRepository = require('../db/tenantRepository');

module.exports = function tenantRoutes(db) {
  const router = express.Router();
  const repo   = new TenantRepository(db);

  router.get('/', (req, res) => {
    res.json(repo.findAll());
  });

  router.post('/', (req, res) => {
    const { name, phone, email, timezone } = req.body;
    if (!name || !phone) {
      return res.status(400).json({ error: 'name y phone son requeridos' });
    }
    try {
      const tenant = repo.create({ name, phone, email, timezone });
      res.status(201).json(tenant);
    } catch (err) {
      if (err.message && err.message.includes('UNIQUE')) {
        return res.status(409).json({ error: 'El teléfono ya está registrado' });
      }
      res.status(500).json({ error: err.message });
    }
  });

  router.get('/:id', (req, res) => {
    const tenant = repo.findById(req.params.id);
    if (!tenant) return res.status(404).json({ error: 'Empresa no encontrada' });
    res.json(tenant);
  });

  router.put('/:id', (req, res) => {
    const tenant = repo.findById(req.params.id);
    if (!tenant) return res.status(404).json({ error: 'Empresa no encontrada' });
    const updated = repo.update(req.params.id, req.body);
    res.json(updated);
  });

  router.delete('/:id', (req, res) => {
    const tenant = repo.findById(req.params.id);
    if (!tenant) return res.status(404).json({ error: 'Empresa no encontrada' });
    repo.delete(req.params.id);
    res.status(204).send();
  });

  router.get('/:id/horarios', (req, res) => {
    const tenant = repo.findById(req.params.id);
    if (!tenant) return res.status(404).json({ error: 'Empresa no encontrada' });
    res.json(repo.getBusinessHours(req.params.id));
  });

  router.put('/:id/horarios', (req, res) => {
    const tenant = repo.findById(req.params.id);
    if (!tenant) return res.status(404).json({ error: 'Empresa no encontrada' });
    const { hours } = req.body;
    if (!Array.isArray(hours)) {
      return res.status(400).json({ error: 'hours debe ser un arreglo' });
    }
    const result = repo.setBusinessHours(req.params.id, hours);
    res.json(result);
  });

  return router;
};
