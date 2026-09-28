# Auditoría: ingesta de mensajes de WhatsApp

Este CRM es Laravel con SQLite en local (MySQL si `DB_CONNECTION=mysql`). No es el Sales Hub de Next.js/Supabase. El contrato de n8n se apoya en las tablas que ya existían, sin renombrar columnas.

## Mapeo del contrato

| Contrato del prompt | En este CRM |
|---|---|
| `contacts` | `people` |
| `first_name` | `name` |
| `phone` | `phone_raw` |
| `phone_normalized` | `people.phone_normalized` (columna generada) |
| `source_id` | `people.source_id` → `lead_sources` |
| `next_action`, `next_action_at` | mismas columnas, nuevas |
| `owner_user_id` en contactos | `people.owner_id` (ya era nullable) |
| `company_id` | `organizations.id` vía `organization_person` |
| `channels` / `lead_sources` | tablas nuevas |
| `activities.contact_id` | `activities.person_id` |
| `activity_type` | `activities.type` |
| `result` | `activities.body` |
| `completed` | `activities.is_done` |
| `owner_user_id` en actividades | `activities.user_id` (ya era nullable) |
| `external_ref` | `activities.external_ref` |
| RPC `find_duplicate_contacts` | `POST /api/v1/contacts/find-duplicates` |
| service role | token Sanctum Bearer. No hay service role de Supabase en este repo |

## Qué había y qué faltaba

| Requisito | Antes | Ahora |
|---|---|---|
| Deduplicar teléfonos | `phone_e164` lo calculaba la app | `phone_normalized` generada y usada por la búsqueda |
| Catálogo WhatsApp, Instagram, Facebook, Email | no existía | `channels` y `lead_sources` |
| Gestor nullable | `owner_id` y `user_id` ya lo eran | se mantiene |
| Idempotencia del mensaje | no | índice único en `external_ref` (varios NULL permitidos) |
| Contacto sin gestor en `/contactos` | la lista no mostraba próxima acción ni fuente de catálogo | `/contactos` muestra fuente, próxima acción, fecha y «Sin gestor» |
| Timeline con canal y texto | el historial no mostraba canal ni cuerpo | la ficha muestra canal, fecha/hora y `body` |
| Leads sin asignar | no | filtro para rol `direccion` y `admin`, con asignación manual |
| Oportunidad manual | ya existía «Nuevo deal» | en la ficha, «Crear oportunidad» |
| Indicador de respuesta | no | «Por responder» si `next_action_at` es hoy o anterior y hay un WhatsApp entrante sin completar |
| Anónimo sin acceso | rutas web con `auth` y API con Sanctum | se mantiene y queda cubierto por pruebas |

## Migraciones

- `2026_09_28_220000_add_whatsapp_message_ingestion.php`
- `2026_09_28_221000_generate_phone_normalized_on_sqlite.php`

En MySQL, `phone_normalized` es `STORED` y quita todo lo que no sea dígito con `REGEXP_REPLACE`. En SQLite no se puede agregar con `ALTER` una columna `STORED`, así que queda generada `VIRTUAL`: quita `+`, espacios, guiones, paréntesis, puntos y barras, y aplica la misma regla de Ecuador (`099…`, `593…`, `+593 …`).

`+593 99 123 4567`, `0991234567` y `593991234567` resuelven a `593991234567`.

## Supuestos

- El rol Director Comercial es `direccion`. `admin` también ve el filtro y puede asignar.
- La coincidencia por teléfono es global. El email se usa si no hay teléfono. Nombre y empresa solo si no hay teléfono ni email.
- Un reintento con el mismo `external_ref` no crea otra actividad ni otro contacto. El formato esperado es `chatwoot:msg:<id>`.
- No se crean oportunidades ni se asigna gestor al ingerir.
- El webhook de Chatwoot que ya existía se deja como está. Esta ingesta no lo usa ni abre un endpoint nuevo hacia Chatwoot o Meta.
- El token de n8n se crea en Integraciones y vive en n8n. No está en el frontend, en el repositorio ni en los logs de esta función.

## API para n8n

Autenticación: `Authorization: Bearer <token Sanctum>`.

`POST /api/v1/whatsapp/messages`

```json
{
  "phone": "+593 99 123 4567",
  "first_name": "Ana",
  "result": "Hola, quiero información",
  "external_ref": "chatwoot:msg:123"
}
```

Dentro de esa llamada se hace la búsqueda, el alta del contacto si no existe (fuente WhatsApp, próxima acción «Responder mensaje entrante», sin gestor) y el alta de la actividad. `GET /api/v1/channels?name=WhatsApp` y `GET /api/v1/lead-sources` exponen los catálogos. Sin token, la API responde 401.

## Checklist

| Criterio | Resultado |
|---|---|
| Número nuevo → contacto en `/contactos` con fuente WhatsApp, sin gestor | OK (`WhatsappIngestionTest`) |
| Segundo mensaje del mismo número → un contacto y otra actividad | OK |
| Mismo `external_ref` → sin actividad duplicada | OK |
| Actividad sin gestor en «Leads sin asignar», y asignación manual | OK |
| Anónimo no lee ni escribe contactos, actividades, canales ni fuentes | OK (401 con `Accept: application/json`; `/contactos` redirige a login) |
| Las tres variantes de teléfono resuelven al mismo contacto | OK, también en `scripts/probar_ingesta_whatsapp.php` contra la base local |
| `php artisan test` | 28 pruebas OK |

La ficha autenticada se comprobó por las pruebas que renderizan el HTML. El login automático en el navegador no se completó.
