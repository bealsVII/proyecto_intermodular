# Hotel ´Palms - Documentación de API pública

**Base URL:** `/api/v1`
**Versin:** 1.0.0
**Autenticación:** Session-based (Cookie)

---

## Authentication

### POST `/api/v1/auth/login`

Autenticar a un usuario.

**Cuerpo de solicitud:**
```json
{
    "email": "admin@hotel.com",
    "password": "admin123"
}