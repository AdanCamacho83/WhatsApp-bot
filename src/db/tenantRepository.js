'use strict';

class TenantRepository {
  constructor(db) {
    this.db = db;
  }

  create({ name, phone, email = null, timezone = 'America/Mexico_City' }) {
    const stmt = this.db.prepare(
      'INSERT INTO tenants (name, phone, email, timezone) VALUES (?, ?, ?, ?)'
    );
    const result = stmt.run(name, phone, email, timezone);
    return this.findById(result.lastInsertRowid);
  }

  findById(id) {
    return this.db.prepare('SELECT * FROM tenants WHERE id = ?').get(id);
  }

  findByPhone(phone) {
    return this.db.prepare('SELECT * FROM tenants WHERE phone = ?').get(phone);
  }

  findAll() {
    return this.db.prepare('SELECT * FROM tenants ORDER BY name').all();
  }

  update(id, { name, email, timezone }) {
    const fields = [];
    const values = [];
    if (name !== undefined) { fields.push('name = ?'); values.push(name); }
    if (email !== undefined) { fields.push('email = ?'); values.push(email); }
    if (timezone !== undefined) { fields.push('timezone = ?'); values.push(timezone); }
    if (fields.length === 0) return this.findById(id);
    values.push(id);
    this.db.prepare(`UPDATE tenants SET ${fields.join(', ')} WHERE id = ?`).run(...values);
    return this.findById(id);
  }

  delete(id) {
    return this.db.prepare('DELETE FROM tenants WHERE id = ?').run(id);
  }

  setBusinessHours(tenantId, hours) {
    const upsert = this.db.prepare(`
      INSERT INTO business_hours (tenant_id, day_of_week, open_time, close_time, is_open)
      VALUES (?, ?, ?, ?, ?)
      ON CONFLICT(tenant_id, day_of_week) DO UPDATE SET
        open_time = excluded.open_time,
        close_time = excluded.close_time,
        is_open = excluded.is_open
    `);
    const insertMany = this.db.transaction((rows) => {
      for (const row of rows) {
        upsert.run(tenantId, row.day_of_week, row.open_time, row.close_time, row.is_open ? 1 : 0);
      }
    });
    insertMany(hours);
    return this.getBusinessHours(tenantId);
  }

  getBusinessHours(tenantId) {
    return this.db
      .prepare('SELECT * FROM business_hours WHERE tenant_id = ? ORDER BY day_of_week')
      .all(tenantId);
  }
}

module.exports = TenantRepository;
