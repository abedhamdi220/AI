// src/lib/axios.js
import axios from 'axios';

const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL || 'http://localhost:8000', // Laravel backend
  withCredentials: true, // send cookies (Sanctum)
  headers: {
    'X-Requested-With': 'XMLHttpRequest',
  },
});

export default api;
