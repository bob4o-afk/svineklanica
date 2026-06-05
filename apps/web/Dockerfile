# syntax=docker/dockerfile:1
# Multi-stage: `dev` (Vite dev server, source bind-mounted by compose),
# `build` (pnpm build), and `prod` (nginx serving the static PWA).

# ---- base: node + pnpm ----
FROM node:20-alpine AS base
RUN corepack enable
WORKDIR /app

# ---- dev: deps installed at runtime via `make install`; source bind-mounted ----
FROM base AS dev
ENV NODE_ENV=development
CMD ["pnpm", "dev", "--host", "0.0.0.0", "--port", "5173"]

# ---- build: produce the static PWA bundle ----
FROM base AS build
ARG VITE_MUI_X_LICENSE_KEY=""
ENV VITE_MUI_X_LICENSE_KEY=${VITE_MUI_X_LICENSE_KEY}
COPY package.json pnpm-lock.yaml* ./
RUN pnpm install --frozen-lockfile
COPY . .
RUN pnpm build

# ---- prod: nginx serves the bundle ----
FROM nginx:1.27-alpine AS prod
COPY nginx.conf /etc/nginx/conf.d/default.conf
COPY --from=build /app/dist /usr/share/nginx/html
EXPOSE 80
CMD ["nginx", "-g", "daemon off;"]
