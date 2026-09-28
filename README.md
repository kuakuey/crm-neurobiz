# CRM Neurobiz

CRM interno de Neurobiz para unir **pipeline comercial**, **Chatwoot** (WhatsApp/web) y **n8n** (automatizaciones). El CRM es la fuente de verdad de personas, empresas, deals y tareas. Chatwoot guarda las conversaciones. n8n orquesta recordatorios y handoffs.

## Requisitos

- PHP 8.2+, Composer, Node 18+
- MySQL (XAMPP) o SQLite
- Chatwoot y n8n (self-hosted o cloud), accesibles por HTTPS para webhooks

## Instalación (XAMPP)

```bash
cd "/Applications/XAMPP/xamppfiles/htdocs/CRM Neurobiz"
cp .env.example .env
php artisan key:generate
```

Crea la base `crm_neurobiz` en phpMyAdmin o:

```bash
/Applications/XAMPP/xamppfiles/bin/mysql -u root -e "CREATE DATABASE IF NOT EXISTS crm_neurobiz CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

```bash
php artisan migrate --seed
npm install && npm run build
php artisan storage:link
```

Usuarios semilla: `admin@neurobiz.local`, `comercial@neurobiz.local`, `direccion@neurobiz.local`, contraseña `password`.

### VirtualHost

Copia [docs/apache-vhost.conf](docs/apache-vhost.conf) a `httpd-vhosts.conf`, añade `127.0.0.1 crm.neurobiz.test` en `/etc/hosts` y reinicia Apache. `APP_URL` debe coincidir.

Sin VirtualHost puedes servir `public/` con `php artisan serve`.

### Cola y scheduler

```bash
php artisan queue:work
php artisan schedule:work
```

## Superficies

| Superficie | Ruta |
|---|---|
| CRM | `/dashboard` |
| Contactos | `/contactos` |
| Dashboard App Chatwoot | `/embed/chatwoot` |
| Webhook Chatwoot | `POST /webhooks/chatwoot` |
| Diagnóstico gratuito | `POST /webhooks/diagnostico` |
| API n8n | `/api/v1/*` (Bearer Sanctum) |

Configura Chatwoot y n8n en **Integraciones**. Genera un token API ahí. Plantillas n8n en [`n8n/`](n8n/README.md). Contrato HTTP en [`docs/openapi.yaml`](docs/openapi.yaml).

En Chatwoot: Settings → Integrations → Dashboard Apps → URL `https://tu-crm/embed/chatwoot`. Webhook con los eventos de contacto, conversación y mensaje, firmado con el mismo secret.

Túnel local (ngrok / Cloudflare) hacia Apache: Chatwoot no puede pegarle a `localhost`.

## Modelo comercial

Pipelines semilla: NeuroBusiness B2B, Programas B2C, Workshops. Ofertas: diagnóstico gratuito, alto impacto, sprint, mentoring retainer, certificación, workshop.

El teléfono se normaliza a E.164 (`0980…` → `+593980…`). La ingesta de WhatsApp que dispara n8n está en `POST /api/v1/whatsapp/messages`; el detalle del esquema está en [docs/auditoria-ingesta-whatsapp.md](docs/auditoria-ingesta-whatsapp.md).
