import React, { useState, useEffect } from 'react'
import axios from 'axios'
import { useNavigate } from 'react-router-dom'

export default function ListingForm(){
  const [form, setForm] = useState({ title:'', price:'', location:'', description:'', image:'' })
  const navigate = useNavigate()

  function handleChange(e){
    setForm(prev => ({ ...prev, [e.target.name]: e.target.value }))
  }

  async function handleSubmit(e){
    e.preventDefault()
    await axios.post('/api/properties', form)
    navigate('/')
  }

  return (
    <div className="max-w-2xl bg-white p-6 rounded shadow">
      <h2 className="text-xl font-bold mb-4">Publicar nueva propiedad</h2>
      <form onSubmit={handleSubmit} className="space-y-3">
        <input name="title" placeholder="Título" value={form.title} onChange={handleChange} className="w-full border p-2 rounded" />
        <input name="price" placeholder="Precio" value={form.price} onChange={handleChange} className="w-full border p-2 rounded" />
        <input name="location" placeholder="Ubicación" value={form.location} onChange={handleChange} className="w-full border p-2 rounded" />
        <input name="image" placeholder="URL imagen" value={form.image} onChange={handleChange} className="w-full border p-2 rounded" />
        <textarea name="description" placeholder="Descripción" value={form.description} onChange={handleChange} className="w-full border p-2 rounded" />
        <div className="flex justify-end">
          <button className="bg-blue-600 text-white px-4 py-2 rounded">Publicar</button>
        </div>
      </form>
    </div>
  )
}