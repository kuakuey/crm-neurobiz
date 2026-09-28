# Workflows n8n — CRM Neurobiz

Importar desde n8n: **⋯ → Import from File**.

Variables de entorno recomendadas en n8n:

- `CRM_BASE_URL` — ej. `https://crm.neurobiz.test`
- `CRM_HMAC_SECRET` — el mismo HMAC guardado en Integraciones
- Token Sanctum: credencial Header Auth `Authorization: Bearer …` (generar en Integraciones)

El CRM también emite eventos al webhook único configurado en Integraciones → n8n. El flujo `01-crm-event-bus.json` es el receptor de esos eventos (`X-CRM-Signature` HMAC-SHA256 del body).

| Archivo | Uso |
|---|---|
| 01 | Bus de eventos CRM → switch por tipo |
| 02 | Conversación Chatwoot → persona + deal Lead |
| 03 | Recordatorio operativo (completar con filtro 24h) |
| 04 | Resultado diagnóstico gratuito → `/webhooks/diagnostico` |
| 05 | Deal ganado → tarea de onboarding |
| 06 | Deal sin movimiento 7 días (wait + verificación) |
| 07 | Conversación resuelta + deal abierto → follow-up |

El CRM ya crea el lead al recibir el webhook de Chatwoot; el flujo 02 es opcional si quieres orquestar desde n8n en lugar del CRM.

## Ingesta de mensajes de WhatsApp

n8n autentica con el token Sanctum (Header Auth `Authorization: Bearer …`, generado en Integraciones). Ese token no se guarda en el frontend ni en este repositorio.

Por cada mensaje entrante:

`POST /api/v1/whatsapp/messages`

```json
{
  "phone": "+593 99 123 4567",
  "first_name": "Ana",
  "result": "Hola, quiero información",
  "external_ref": "chatwoot:msg:123"
}
```

El CRM busca duplicados por teléfono normalizado (`+593 99 123 4567`, `0991234567` y `593991234567` son el mismo contacto), crea la persona con fuente WhatsApp y sin gestor si no existe, y registra la actividad. Reenviar el mismo `external_ref` no crea otra actividad.

`POST /api/v1/contacts/find-duplicates` es el equivalente de `find_duplicate_contacts`. `GET /api/v1/channels?name=WhatsApp` devuelve el canal. Sin token, estas rutas responden 401.

La vista **Leads sin asignar** está en `/contactos?filtro=sin-asignar` y la usa Dirección comercial.
