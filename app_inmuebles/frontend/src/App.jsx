import React from 'react'
import { Routes, Route } from 'react-router-dom'
import Home from './pages/Home'
import PropertyDetails from './pages/PropertyDetails'
import ListingForm from './pages/ListingForm'
import Header from './components/Header'

export default function App(){
  return (
    <div className="min-h-screen bg-gray-50">
      <Header />
      <main className="container mx-auto p-4">
        <Routes>
          <Route path="/" element={<Home/>} />
          <Route path="/property/:id" element={<PropertyDetails/>} />
          <Route path="/new" element={<ListingForm/>} />
        </Routes>
      </main>
    </div>
  )
}