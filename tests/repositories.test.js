'use strict';

const { initDatabase }        = require('../src/db/database');
const TenantRepository        = require('../src/db/tenantRepository');
const ClientRepository        = require('../src/db/clientRepository');
const AppointmentRepository   = require('../src/db/appointmentRepository');

describe('Database repositories', () => {
  let db;
  let tenants;
  let clients;
  let appointments;

  beforeEach(() => {
    db           = initDatabase(':memory:');
    tenants      = new TenantRepository(db);
    clients      = new ClientRepository(db);
    appointments = new AppointmentRepository(db);
  });

  afterEach(() => {
    db.close();
  });

  /* ── TenantRepository ── */
  describe('TenantRepository', () => {
    test('creates and retrieves a tenant', () => {
      const tenant = tenants.create({ name: 'Clínica A', phone: '5212345678' });
      expect(tenant.id).toBeDefined();
      expect(tenant.name).toBe('Clínica A');
      expect(tenant.phone).toBe('5212345678');
      expect(tenant.timezone).toBe('America/Mexico_City');
    });

    test('findByPhone returns the tenant', () => {
      tenants.create({ name: 'Barbería', phone: '5219876543' });
      const found = tenants.findByPhone('5219876543');
      expect(found).not.toBeNull();
      expect(found.name).toBe('Barbería');
    });

    test('findAll returns all tenants', () => {
      tenants.create({ name: 'A', phone: '111' });
      tenants.create({ name: 'B', phone: '222' });
      expect(tenants.findAll()).toHaveLength(2);
    });

    test('update changes tenant fields', () => {
      const t = tenants.create({ name: 'Old', phone: '333' });
      const updated = tenants.update(t.id, { name: 'New', email: 'a@b.com' });
      expect(updated.name).toBe('New');
      expect(updated.email).toBe('a@b.com');
    });

    test('delete removes tenant', () => {
      const t = tenants.create({ name: 'Del', phone: '444' });
      tenants.delete(t.id);
      expect(tenants.findById(t.id)).toBeUndefined();
    });

    test('duplicate phone throws (UNIQUE constraint)', () => {
      tenants.create({ name: 'A', phone: 'dup' });
      expect(() => tenants.create({ name: 'B', phone: 'dup' })).toThrow();
    });

    test('setBusinessHours and getBusinessHours', () => {
      const t = tenants.create({ name: 'Spa', phone: '555' });
      tenants.setBusinessHours(t.id, [
        { day_of_week: 1, open_time: '09:00', close_time: '18:00', is_open: true },
        { day_of_week: 2, open_time: '09:00', close_time: '18:00', is_open: true },
      ]);
      const hours = tenants.getBusinessHours(t.id);
      expect(hours).toHaveLength(2);
      expect(hours[0].day_of_week).toBe(1);
    });
  });

  /* ── ClientRepository ── */
  describe('ClientRepository', () => {
    let tenant;

    beforeEach(() => {
      tenant = tenants.create({ name: 'Empresa', phone: '000' });
    });

    test('creates and retrieves a client', () => {
      const client = clients.create({ tenantId: tenant.id, name: 'Ana', phone: '600' });
      expect(client.id).toBeDefined();
      expect(client.name).toBe('Ana');
    });

    test('findByPhone returns client in tenant scope', () => {
      clients.create({ tenantId: tenant.id, name: 'Luis', phone: '700' });
      const found = clients.findByPhone(tenant.id, '700');
      expect(found.name).toBe('Luis');
    });

    test('findOrCreate returns existing client', () => {
      const c1 = clients.create({ tenantId: tenant.id, name: 'Maria', phone: '800' });
      const c2 = clients.findOrCreate({ tenantId: tenant.id, name: 'Maria', phone: '800' });
      expect(c1.id).toBe(c2.id);
    });

    test('findAll returns only tenant clients', () => {
      const other = tenants.create({ name: 'Otra', phone: '999' });
      clients.create({ tenantId: tenant.id,  name: 'A', phone: 'p1' });
      clients.create({ tenantId: tenant.id,  name: 'B', phone: 'p2' });
      clients.create({ tenantId: other.id,   name: 'C', phone: 'p3' });
      expect(clients.findAll(tenant.id)).toHaveLength(2);
    });

    test('update changes client name', () => {
      const c = clients.create({ tenantId: tenant.id, name: 'Old', phone: 'ph1' });
      const updated = clients.update(c.id, { name: 'New' });
      expect(updated.name).toBe('New');
    });
  });

  /* ── AppointmentRepository ── */
  describe('AppointmentRepository', () => {
    let tenant;
    let client;

    beforeEach(() => {
      tenant = tenants.create({ name: 'Consultorio', phone: 'ct1' });
      client = clients.create({ tenantId: tenant.id, name: 'Pedro', phone: 'pt1' });
    });

    function makeAppt(overrides = {}) {
      return appointments.create({
        tenantId:        tenant.id,
        clientId:        client.id,
        service:         'Consulta',
        appointmentDate: '2026-04-10',
        appointmentTime: '10:00',
        ...overrides,
      });
    }

    test('creates and retrieves an appointment', () => {
      const a = makeAppt();
      expect(a.id).toBeDefined();
      expect(a.client_name).toBe('Pedro');
      expect(a.status).toBe('pending');
    });

    test('findByTenant returns appointments for a tenant', () => {
      makeAppt({ appointmentDate: '2026-04-10' });
      makeAppt({ appointmentDate: '2026-04-11' });
      expect(appointments.findByTenant(tenant.id)).toHaveLength(2);
    });

    test('findByTenant filters by date', () => {
      makeAppt({ appointmentDate: '2026-04-10' });
      makeAppt({ appointmentDate: '2026-04-11' });
      const result = appointments.findByTenant(tenant.id, { date: '2026-04-10' });
      expect(result).toHaveLength(1);
    });

    test('findByTenant filters by status', () => {
      const a = makeAppt();
      appointments.updateStatus(a.id, 'confirmed');
      makeAppt({ appointmentTime: '11:00' });
      const confirmed = appointments.findByTenant(tenant.id, { status: 'confirmed' });
      expect(confirmed).toHaveLength(1);
    });

    test('updateStatus changes status', () => {
      const a = makeAppt();
      const updated = appointments.updateStatus(a.id, 'confirmed');
      expect(updated.status).toBe('confirmed');
    });

    test('update changes multiple fields', () => {
      const a = makeAppt();
      const updated = appointments.update(a.id, { service: 'Revisión', notes: 'Con urgencia' });
      expect(updated.service).toBe('Revisión');
      expect(updated.notes).toBe('Con urgencia');
    });

    test('findUpcoming returns future pending/confirmed', () => {
      makeAppt({ appointmentDate: '2025-01-01' });
      makeAppt({ appointmentDate: '2030-12-31' });
      const upcoming = appointments.findUpcoming(tenant.id, '2026-01-01');
      expect(upcoming.every((a) => a.appointment_date >= '2026-01-01')).toBe(true);
    });

    test('delete removes appointment', () => {
      const a = makeAppt();
      appointments.delete(a.id);
      expect(appointments.findById(a.id)).toBeUndefined();
    });
  });
});
