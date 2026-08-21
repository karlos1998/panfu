# Production deployment

Pushes to `main` deploy Panfu to `/opt/apps/panfu` through GitHub Actions. The
workflow downloads the runtime `.env` from the `panfu` project in Envly, builds
the Laravel and Spring Boot images, transfers them over SSH, runs migrations
and seeders, and waits for all production health checks.

The GitHub `production` environment must contain these Actions secrets:

- `ENVLY_TOKEN`
- `SERVER_HOST`
- `SERVER_USER`
- `SERVER_SSH_KEY`
- `SERVER_SSH_KNOWN_HOSTS`

## Routing engines without extra public ports

The host-level `nginx-proxy` sends `VIRTUAL_HOST` to the Panfu `gateway` on
port 80. The gateway keeps Laravel at `/` and exposes the first Spring Boot
engine at `/engine/main/`. For example, Ruffle connects to:

```text
wss://plemionka.letscode.it/engine/main/game
```

To add an engine later:

1. Duplicate `gameserver-main` in `compose.production.yaml`, assign a unique
   `GAME_SERVER_ID`, and keep ports exposed only on the internal network.
2. Add a named upstream and `/engine/<slug>/` location to
   `docker/production/nginx.conf`.
3. Add the engine to the `gameservers` data shown to the Flash client.
4. Extend `PANFU_GAME_SOCKET_PROXIES` in Envly with the engine's public route,
   for example `wss://plemionka.letscode.it/engine/secondary/game`.

No additional public port or subdomain is required. Cloudflare terminates HTTPS
and forwards WebSocket traffic to the existing port 80 origin.

The deploy script refuses to start the gateway when another Compose project
already advertises the same `VIRTUAL_HOST`. This prevents an accidental domain
takeover during migration.
