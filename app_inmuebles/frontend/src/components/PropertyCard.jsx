import React from 'react'
import { Link } from 'react-router-dom'

export default function PropertyCard({ property }){
  return (
    <article className="bg-white rounded shadow p-4">
      <img src={property.image} alt={property.title} className="w-full h-48 object-cover rounded"/>
      <h3 className="text-lg font-semibold mt-2">{property.title}</h3>
      <p className="text-sm text-gray-600">{property.location}</p>
      <p className="mt-2 font-bold">{property.price} €</p>
      <div className="mt-3">
        <Link to={`/property/${property.id}`} className="text-blue-600 hover:underline">Ver detalles</Link>
      </div>
    </article>
  )
}