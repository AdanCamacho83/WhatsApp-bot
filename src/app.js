'use strict';

require('dotenv').config();

const express = require('express');
const cors    = require('cors');
const path    = require('path');

const { initDatabase }   = require('./db/database');
const tenantRoutes       = require('./routes/tenants');
const clientRoutes       = require('./routes/clients');
const appointmentRoutes  = require('./routes/appointments');

function createApp(db) {
  const app = express();

  app.use(cors());
  app.use(express.json());
  app.use(express.urlencoded({ extended: true }));

  app.use(express.static(path.join(__dirname, 'public')));

  app.use('/api/empresas', tenantRoutes(db));
  app.use('/api/empresas/:tenantId/clientes', clientRoutes(db));
  app.use('/api/empresas/:tenantId/citas', appointmentRoutes(db));

  app.get('/health', (req, res) => res.json({ status: 'ok' }));

  app.use((req, res) => res.status(404).json({ error: 'Ruta no encontrada' }));

  app.use((err, req, res, next) => {
    console.error(err);
    res.status(500).json({ error: 'Error interno del servidor' });
  });

  return app;
}

module.exports = { createApp, initDatabase };
