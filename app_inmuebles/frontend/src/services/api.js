import axios from 'axios'
export default {
  getProperties: async () => {
    const res = await axios.get('/api/properties')
    return res.data
  },
  getPropertyById: async (id) => {
    const res = await axios.get(`/api/properties/${id}`)
    return res.data
  },
  createProperty: async (payload) => {
    const res = await axios.post('/api/properties', payload)
    return res.data
  }
}