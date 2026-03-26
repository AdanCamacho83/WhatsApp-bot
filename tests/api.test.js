'use strict';

const request = require('supertest');
const { createApp } = require('../src/app');
const { initDatabase } = require('../src/db/database');

let app;
let db;

beforeAll(() => {
  db  = initDatabase(':memory:');
  app = createApp(db);
});

afterAll(() => {
  db.close();
});

describe('GET /health', () => {
  test('returns ok', async () => {
    const res = await request(app).get('/health');
    expect(res.status).toBe(200);
    expect(res.body.status).toBe('ok');
  });
});

describe('/api/empresas', () => {
  let tenantId;

  test('GET / returns empty array initially', async () => {
    const res = await request(app).get('/api/empresas');
    expect(res.status).toBe(200);
    expect(Array.isArray(res.body)).toBe(true);
  });

  test('POST / creates a tenant', async () => {
    const res = await request(app).post('/api/empresas').send({
      name: 'Clínica Test',
      phone: '5201111111',
      timezone: 'America/Mexico_City',
    });
    expect(res.status).toBe(201);
    expect(res.body.name).toBe('Clínica Test');
    tenantId = res.body.id;
  });

  test('POST / returns 400 without required fields', async () => {
    const res = await request(app).post('/api/empresas').send({ name: 'Sin telefono' });
    expect(res.status).toBe(400);
  });

  test('POST / returns 409 on duplicate phone', async () => {
    const res = await request(app).post('/api/empresas').send({
      name: 'Otra clínica',
      phone: '5201111111',
    });
    expect(res.status).toBe(409);
  });

  test('GET /:id returns the tenant', async () => {
    const res = await request(app).get(`/api/empresas/${tenantId}`);
    expect(res.status).toBe(200);
    expect(res.body.id).toBe(tenantId);
  });

  test('GET /:id returns 404 for unknown', async () => {
    const res = await request(app).get('/api/empresas/9999');
    expect(res.status).toBe(404);
  });

  test('PUT /:id updates tenant', async () => {
    const res = await request(app).put(`/api/empresas/${tenantId}`).send({ name: 'Actualizada' });
    expect(res.status).toBe(200);
    expect(res.body.name).toBe('Actualizada');
  });

  describe('horarios', () => {
    test('PUT /:id/horarios sets business hours', async () => {
      const res = await request(app).put(`/api/empresas/${tenantId}/horarios`).send({
        hours: [
          { day_of_week: 1, open_time: '08:00', close_time: '17:00', is_open: true },
          { day_of_week: 2, open_time: '08:00', close_time: '17:00', is_open: true },
        ],
      });
      expect(res.status).toBe(200);
      expect(res.body).toHaveLength(2);
    });

    test('GET /:id/horarios retrieves hours', async () => {
      const res = await request(app).get(`/api/empresas/${tenantId}/horarios`);
      expect(res.status).toBe(200);
      expect(res.body.length).toBeGreaterThan(0);
    });

    test('PUT /:id/horarios returns 400 if hours not array', async () => {
      const res = await request(app).put(`/api/empresas/${tenantId}/horarios`).send({ hours: 'bad' });
      expect(res.status).toBe(400);
    });
  });

  /* ── Clientes ── */
  describe('/api/empresas/:id/clientes', () => {
    let clientId;

    test('POST creates a client', async () => {
      const res = await request(app)
        .post(`/api/empresas/${tenantId}/clientes`)
        .send({ name: 'Juan Pérez', phone: '5209999999' });
      expect(res.status).toBe(201);
      expect(res.body.name).toBe('Juan Pérez');
      clientId = res.body.id;
    });

    test('GET returns clients for tenant', async () => {
      const res = await request(app).get(`/api/empresas/${tenantId}/clientes`);
      expect(res.status).toBe(200);
      expect(res.body.length).toBeGreaterThan(0);
    });

    test('GET /:clientId returns specific client', async () => {
      const res = await request(app).get(`/api/empresas/${tenantId}/clientes/${clientId}`);
      expect(res.status).toBe(200);
      expect(res.body.id).toBe(clientId);
    });

    test('PUT /:clientId updates client', async () => {
      const res = await request(app)
        .put(`/api/empresas/${tenantId}/clientes/${clientId}`)
        .send({ name: 'Juan Updated' });
      expect(res.status).toBe(200);
      expect(res.body.name).toBe('Juan Updated');
    });

    /* ── Citas ── */
    describe('/api/empresas/:id/citas', () => {
      let appointmentId;

      test('POST creates an appointment', async () => {
        const res = await request(app)
          .post(`/api/empresas/${tenantId}/citas`)
          .send({
            clientId:        clientId,
            service:         'Consulta',
            appointmentDate: '2030-06-15',
            appointmentTime: '10:00',
            durationMinutes: 60,
          });
        expect(res.status).toBe(201);
        expect(res.body.service).toBe('Consulta');
        appointmentId = res.body.id;
      });

      test('POST returns 409 on conflict', async () => {
        const res = await request(app)
          .post(`/api/empresas/${tenantId}/citas`)
          .send({
            clientId:        clientId,
            service:         'Otra',
            appointmentDate: '2030-06-15',
            appointmentTime: '10:00',
            durationMinutes: 60,
          });
        expect(res.status).toBe(409);
      });

      test('GET returns appointments', async () => {
        const res = await request(app).get(`/api/empresas/${tenantId}/citas`);
        expect(res.status).toBe(200);
        expect(res.body.length).toBeGreaterThan(0);
      });

      test('GET /:id returns specific appointment', async () => {
        const res = await request(app).get(`/api/empresas/${tenantId}/citas/${appointmentId}`);
        expect(res.status).toBe(200);
        expect(res.body.id).toBe(appointmentId);
      });

      test('PATCH /:id/estado updates status', async () => {
        const res = await request(app)
          .patch(`/api/empresas/${tenantId}/citas/${appointmentId}/estado`)
          .send({ status: 'confirmed' });
        expect(res.status).toBe(200);
        expect(res.body.status).toBe('confirmed');
      });

      test('PATCH /:id/estado returns 400 for invalid status', async () => {
        const res = await request(app)
          .patch(`/api/empresas/${tenantId}/citas/${appointmentId}/estado`)
          .send({ status: 'invalid' });
        expect(res.status).toBe(400);
      });

      test('DELETE removes appointment', async () => {
        const res = await request(app).delete(`/api/empresas/${tenantId}/citas/${appointmentId}`);
        expect(res.status).toBe(204);
      });
    });

    test('DELETE /:clientId removes client', async () => {
      const res = await request(app).delete(`/api/empresas/${tenantId}/clientes/${clientId}`);
      expect(res.status).toBe(204);
    });
  });

  test('DELETE /:id removes tenant', async () => {
    const res = await request(app).delete(`/api/empresas/${tenantId}`);
    expect(res.status).toBe(204);
  });
});

describe('MessageHandler', () => {
  const MessageHandler = require('../src/bot/messageHandler');

  let handler;

  beforeEach(() => {
    const testDb = initDatabase(':memory:');
    handler = new MessageHandler(testDb);
  });

  function fakeMsg(from, body) {
    return { from: `${from}@c.us`, body };
  }

  test('returns main menu for "hola"', async () => {
    const reply = await handler.handle(fakeMsg('52123', 'hola'));
    expect(reply).toMatch(/Bienvenido/);
    expect(reply).toMatch(/agendar/);
  });

  test('returns main menu for unknown command', async () => {
    const reply = await handler.handle(fakeMsg('52123', 'xyz'));
    expect(reply).toMatch(/menú|menu|comandos/i);
  });

  test('tenant gets help menu for "ayuda"', async () => {
    const TenantRepository = require('../src/db/tenantRepository');
    const tr = new TenantRepository(handler.tenants.db);
    tr.create({ name: 'Test', phone: '52999' });

    const reply = await handler.handle(fakeMsg('52999', 'ayuda'));
    expect(reply).toMatch(/Panel de empresa/);
  });

  test('"mis citas" with no appointments returns empty notice', async () => {
    // Add a tenant so the handler doesn't bail out early
    const TenantRepository = require('../src/db/tenantRepository');
    const tr = new TenantRepository(handler.tenants.db);
    tr.create({ name: 'Test', phone: '52888' });

    const reply = await handler.handle(fakeMsg('52000', 'mis citas'));
    expect(reply).toMatch(/No tienes citas/);
  });

  test('"cancelar #999" returns not found', async () => {
    // Add a tenant so the handler doesn't bail out early
    const TenantRepository = require('../src/db/tenantRepository');
    const tr = new TenantRepository(handler.tenants.db);
    try { tr.create({ name: 'Test2', phone: '52777' }); } catch { /* already exists */ }

    const reply = await handler.handle(fakeMsg('52000', 'cancelar #999'));
    expect(reply).toMatch(/no encontrada/i);
  });
});
