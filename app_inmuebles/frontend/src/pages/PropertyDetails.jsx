import React, { useEffect, useState } from 'react'
import { useParams } from 'react-router-dom'
import api from '../services/api'

export default function PropertyDetails(){
  const { id } = useParams()
  const [property, setProperty] = useState(null)
  useEffect(()=>{ api.getPropertyById(id).then(setProperty) },[id])

  if(!property) return <p>Cargando...</p>

  return (
    <div className="bg-white rounded shadow p-6">
      <img src={property.image} alt={property.title} className="w-full h-72 object-cover rounded"/>
      <h2 className="text-2xl font-bold mt-4">{property.title}</h2>
      <p className="text-gray-600">{property.location}</p>
      <p className="mt-3 font-semibold text-xl">{property.price} €</p>
      <p className="mt-4">{property.description}</p>
    </div>
  )
}