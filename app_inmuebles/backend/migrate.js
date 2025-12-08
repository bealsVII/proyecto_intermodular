const db = require('./src/db')

async function migrate() {
  try {
    await db.init()
    console.log('Migración completada: tabla properties creada (si no existía).')
  } catch (err) {
    console.error('Error en migración', err)
  }
}

migrate()