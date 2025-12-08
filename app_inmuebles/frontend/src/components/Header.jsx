import React from 'react'
import { Link } from 'react-router-dom'

export default function Header(){
  return (
    <header className="bg-white shadow">
      <div className="container mx-auto p-4 flex items-center justify-between">
        <Link to="/" className="text-2xl font-semibold">Inmuebles</Link>
        <nav className="space-x-4">
          <Link to="/" className="hover:underline">Inicio</Link>
          <Link to="/new" className="bg-blue-600 text-white px-3 py-1 rounded">Publicar</Link>
        </nav>
      </div>
    </header>
  )
}