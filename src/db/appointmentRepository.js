'use strict';

class AppointmentRepository {
  constructor(db) {
    this.db = db;
  }

  create({ tenantId, clientId, service, appointmentDate, appointmentTime, durationMinutes = 60, notes = null }) {
    const stmt = this.db.prepare(`
      INSERT INTO appointments
        (tenant_id, client_id, service, appointment_date, appointment_time, duration_minutes, notes)
      VALUES (?, ?, ?, ?, ?, ?, ?)
    `);
    const result = stmt.run(tenantId, clientId, service, appointmentDate, appointmentTime, durationMinutes, notes);
    return this.findById(result.lastInsertRowid);
  }

  findById(id) {
    return this.db.prepare(`
      SELECT a.*, c.name AS client_name, c.phone AS client_phone
      FROM appointments a
      JOIN clients c ON c.id = a.client_id
      WHERE a.id = ?
    `).get(id);
  }

  findByTenant(tenantId, { status = null, date = null } = {}) {
    let query = `
      SELECT a.*, c.name AS client_name, c.phone AS client_phone
      FROM appointments a
      JOIN clients c ON c.id = a.client_id
      WHERE a.tenant_id = ?
    `;
    const params = [tenantId];
    if (status) { query += ' AND a.status = ?'; params.push(status); }
    if (date)   { query += ' AND a.appointment_date = ?'; params.push(date); }
    query += ' ORDER BY a.appointment_date, a.appointment_time';
    return this.db.prepare(query).all(...params);
  }

  findByClient(clientId) {
    return this.db.prepare(`
      SELECT a.*, c.name AS client_name, c.phone AS client_phone
      FROM appointments a
      JOIN clients c ON c.id = a.client_id
      WHERE a.client_id = ?
      ORDER BY a.appointment_date DESC, a.appointment_time DESC
    `).all(clientId);
  }

  findUpcoming(tenantId, fromDate) {
    return this.db.prepare(`
      SELECT a.*, c.name AS client_name, c.phone AS client_phone
      FROM appointments a
      JOIN clients c ON c.id = a.client_id
      WHERE a.tenant_id = ?
        AND a.appointment_date >= ?
        AND a.status IN ('pending','confirmed')
      ORDER BY a.appointment_date, a.appointment_time
    `).all(tenantId, fromDate);
  }

  updateStatus(id, status) {
    this.db.prepare('UPDATE appointments SET status = ? WHERE id = ?').run(status, id);
    return this.findById(id);
  }

  update(id, { service, appointmentDate, appointmentTime, durationMinutes, notes, status }) {
    const fields = [];
    const values = [];
    if (service !== undefined)          { fields.push('service = ?');            values.push(service); }
    if (appointmentDate !== undefined)  { fields.push('appointment_date = ?');   values.push(appointmentDate); }
    if (appointmentTime !== undefined)  { fields.push('appointment_time = ?');   values.push(appointmentTime); }
    if (durationMinutes !== undefined)  { fields.push('duration_minutes = ?');   values.push(durationMinutes); }
    if (notes !== undefined)            { fields.push('notes = ?');              values.push(notes); }
    if (status !== undefined)           { fields.push('status = ?');             values.push(status); }
    if (fields.length === 0) return this.findById(id);
    values.push(id);
    this.db.prepare(`UPDATE appointments SET ${fields.join(', ')} WHERE id = ?`).run(...values);
    return this.findById(id);
  }

  delete(id) {
    return this.db.prepare('DELETE FROM appointments WHERE id = ?').run(id);
  }

  hasConflict(tenantId, appointmentDate, appointmentTime, durationMinutes, excludeId = null) {
    let query = `
      SELECT COUNT(*) AS cnt FROM appointments
      WHERE tenant_id = ?
        AND appointment_date = ?
        AND status IN ('pending','confirmed')
        AND (
          (appointment_time <= ? AND time(appointment_time, '+' || duration_minutes || ' minutes') > ?)
          OR
          (appointment_time < time(?, '+' || ? || ' minutes') AND appointment_time >= ?)
        )
    `;
    const params = [tenantId, appointmentDate, appointmentTime, appointmentTime,
                    appointmentTime, durationMinutes, appointmentTime];
    if (excludeId) { query += ' AND id != ?'; params.push(excludeId); }
    const row = this.db.prepare(query).get(...params);
    return row.cnt > 0;
  }
}

module.exports = AppointmentRepository;
