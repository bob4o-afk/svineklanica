# Kubernetes manifests (LATER — not the first step)

> **You do NOT need this for the hackathon or first launch.** Ship on one VM with
> `docker-compose.prod.yml` first (see `/DEPLOY.md`). Reach for k8s only when you
> need multi-node scale, self-healing, or zero-downtime rolling deploys.

## What's here
One `Deployment`/`Service` per app component, mirroring the compose services:
`api` (+ HPA), `queue`, `scheduler` (CronJob), `web`, `postgres` (StatefulSet),
`redis`, and an `Ingress` (the reverse proxy) with automatic TLS.

## Cluster prerequisites (install once)
- A cluster: managed (DigitalOcean Kubernetes / GKE / EKS) or **k3s** on a VM for a cheap single-node "k8s".
- **ingress-nginx** controller (the reverse proxy / edge).
- **cert-manager** + a `ClusterIssuer` named `letsencrypt-prod` (free auto-renewing TLS).
- **metrics-server** (so the HPA can read CPU).
- Image pull access to GHCR (public images need nothing; private images need an `imagePullSecret`).

## Deploy
```bash
# 1) point the images at your registry + tag
cd deploy/k8s
kustomize edit set image ghcr.io/OWNER/svineklanitsa-api=ghcr.io/<you>/svineklanitsa-api:v1.0.0
kustomize edit set image ghcr.io/OWNER/svineklanitsa-web=ghcr.io/<you>/svineklanitsa-web:v1.0.0

# 2) create real secrets OUT OF BAND (never commit them)
kubectl -n liberhack create secret generic app-secrets \
  --from-literal=APP_KEY="base64:..." \
  --from-literal=DB_USERNAME=liberhack \
  --from-literal=DB_PASSWORD='...' \
  --from-literal=MAIL_PASSWORD='...'

# 3) edit host names (your-domain.bg) in 00-config + 40-ingress, then apply
kubectl apply -k .

# 4) run migrations once
kubectl -n liberhack exec deploy/api -- php artisan migrate --force
```

## Notes
- Prefer a **managed Postgres** in real prod (delete `10-postgres.yaml`, point `DB_HOST` at it).
- Edge rate limiting is on the Ingress; app-level limiting + the honeypot/blacklist still run inside the API.
- Same images as the VM deploy — nothing about the app changes between Compose and k8s.
