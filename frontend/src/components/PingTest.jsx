// src/components/PingTest.jsx
import React, { useEffect, useState } from 'react';
import api from '../lib/axios';

export default function PingTest() {
  const [status, setStatus] = useState('Connecting...');

  useEffect(() => {
    let mounted = true;

    async function checkConnection() {
      try {
        // Obtain CSRF cookie from Sanctum
        await api.get('/sanctum/csrf-cookie');

        // Call the protected/test API route
        const res = await api.get('/api/ping');

        if (!mounted) return;

        if (res?.data?.status === 'ok' || res?.data?.message === 'pong') {
          setStatus('Connected Successfully');
        } else {
          setStatus('Connected — unexpected response');
        }
      } catch (err) {
        const msg = err?.response?.data?.message || err.message || 'Unknown error';
        if (mounted) setStatus('Error: ' + msg);
      }
    }

    checkConnection();

    return () => {
      mounted = false;
    };
  }, []);

  return <div>{status}</div>;
}
