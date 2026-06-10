FROM docker.io/node:24.15.0-alpine3.23

WORKDIR /app

COPY package.json /app/package.json

# Append `/app/node_modules/.bin` to the path, so we can call `esbuild` (etc.) directly
ENV PATH=/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin:/app/node_modules/.bin

# No need to generate package-lock.json (yarn.lock) in the container.
# We're not committing this file anyway.
RUN yarn install --non-interactive --no-lockfile
