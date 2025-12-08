const sqlite3 = require('sqlite3').verbose()
const path = require('path')
const dbFile = path.join(__dirname, '..', 'data', 'db.sqlite')

const db = new sqlite3.Database(dbFile)

function runAsync(sql, params=[]) {
  return new Promise((resolve, reject) => {
    db.run(sql, params, function(err) {
      if (err) reject(err)
      else resolve(this)
    })
  })
}
function allAsync(sql, params=[]) {
  return new Promise((resolve, reject) => {
    db.all(sql, params, (err, rows) => {
      if (err) reject(err)
      else resolve(rows)
    })
  })
}
function getAsync(sql, params=[]) {
  return new Promise((resolve, reject) => {
    db.get(sql, params, (err, row) => {
      if (err) reject(err)
      else resolve(row)
    })
  })
}

module.exports = {
  init: async () => {
    const sql = `CREATE TABLE IF NOT EXISTS properties (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      title TEXT NOT NULL,
      price TEXT NOT NULL,
      location TEXT,
      description TEXT,
      image TEXT,
      created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )`
    await runAsync(sql)
  },
  allProperties: async () => {
    return await allAsync('SELECT * FROM properties ORDER BY created_at DESC')
  },
  getProperty: async (id) => {
    return await getAsync('SELECT * FROM properties WHERE id = ?', [id])
  },
  createProperty: async ({ title, price, location, description, image }) => {
    const result = await runAsync(
      'INSERT INTO properties (title, price, location, description, image) VALUES (?, ?, ?, ?, ?)',
      [title, price, location || '', description || '', image || '']
    )
    const id = result.lastID
    return await getAsync('SELECT * FROM properties WHERE id = ?', [id])
  }
}