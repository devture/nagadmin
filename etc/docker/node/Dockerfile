FROM docker.io/node:24.18.0-alpine3.23

WORKDIR /app

COPY package.json package-lock.json /app/

# Append `/app/node_modules/.bin` to the path, so we can call `esbuild` (etc.) directly
ENV PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin:/app/node_modules/.bin

RUN npm ci --no-audit --no-fund
