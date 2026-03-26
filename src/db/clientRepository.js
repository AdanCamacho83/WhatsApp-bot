'use strict';

class ClientRepository {
  constructor(db) {
    this.db = db;
  }

  create({ tenantId, name, phone, email = null }) {
    const stmt = this.db.prepare(
      'INSERT INTO clients (tenant_id, name, phone, email) VALUES (?, ?, ?, ?)'
    );
    const result = stmt.run(tenantId, name, phone, email);
    return this.findById(result.lastInsertRowid);
  }

  findById(id) {
    return this.db.prepare('SELECT * FROM clients WHERE id = ?').get(id);
  }

  findByPhone(tenantId, phone) {
    return this.db
      .prepare('SELECT * FROM clients WHERE tenant_id = ? AND phone = ?')
      .get(tenantId, phone);
  }

  findOrCreate({ tenantId, name, phone, email = null }) {
    const existing = this.findByPhone(tenantId, phone);
    if (existing) return existing;
    return this.create({ tenantId, name, phone, email });
  }

  findAll(tenantId) {
    return this.db
      .prepare('SELECT * FROM clients WHERE tenant_id = ? ORDER BY name')
      .all(tenantId);
  }

  update(id, { name, email }) {
    const fields = [];
    const values = [];
    if (name !== undefined) { fields.push('name = ?'); values.push(name); }
    if (email !== undefined) { fields.push('email = ?'); values.push(email); }
    if (fields.length === 0) return this.findById(id);
    values.push(id);
    this.db.prepare(`UPDATE clients SET ${fields.join(', ')} WHERE id = ?`).run(...values);
    return this.findById(id);
  }

  delete(id) {
    return this.db.prepare('DELETE FROM clients WHERE id = ?').run(id);
  }
}

module.exports = ClientRepository;
