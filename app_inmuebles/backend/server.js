require('dotenv').config()
const express = require('express')
const cors = require('cors')
const path = require('path')
const db = require('./src/db')

const app = express()
const PORT = process.env.PORT || 4000

app.use(cors())
app.use(express.json())

// API routes
app.get('/api/properties', async (req, res) => {
  try {
    const rows = await db.allProperties()
    res.json(rows)
  } catch (err) {
    console.error(err)
    res.status(500).json({ error: 'Error al obtener propiedades' })
  }
})

app.get('/api/properties/:id', async (req, res) => {
  try {
    const prop = await db.getProperty(req.params.id)
    if (!prop) return res.status(404).json({ error: 'No encontrado' })
    res.json(prop)
  } catch (err) {
    console.error(err)
    res.status(500).json({ error: 'Error al obtener propiedad' })
  }
})

app.post('/api/properties', async (req, res) => {
  try {
    const { title, price, location, description, image } = req.body
    if (!title || !price) return res.status(400).json({ error: 'title y price son obligatorios' })
    const newProp = await db.createProperty({ title, price, location, description, image })
    res.status(201).json(newProp)
  } catch (err) {
    console.error(err)
    res.status(500).json({ error: 'Error al crear propiedad' })
  }
})

// Serve a simple message at root
app.get('/', (req, res) => {
  res.send('API Inmuebles corriendo')
})

app.listen(PORT, () => {
  console.log(`Server listening on port ${PORT}`)
})