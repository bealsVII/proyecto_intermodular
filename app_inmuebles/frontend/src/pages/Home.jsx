import React, { useEffect, useState } from 'react'
import PropertyCard from '../components/PropertyCard'
import api from '../services/api'

export default function Home(){
  const [properties, setProperties] = useState([])
  useEffect(()=>{
    api.getProperties().then(setProperties)
  },[])

  return (
    <div>
      <h1 className="text-2xl font-bold mb-4">Propiedades disponibles</h1>
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        {properties.map(p => <PropertyCard key={p.id} property={p} />)}
      </div>
    </div>
  )
}