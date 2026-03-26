'use strict';

require('dotenv').config();

const { createApp, initDatabase } = require('./src/app');

const PORT = process.env.PORT || 3000;
const db   = initDatabase();
const app  = createApp(db);

app.listen(PORT, () => {
  console.log(`✅ Servidor iniciado en http://localhost:${PORT}`);
});
