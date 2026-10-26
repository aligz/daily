# Image app untuk dev. Node ditambahkan karena `npm run build` menjalankan
# `php artisan wayfinder:generate`, dan @laravel/vite-plugin-wayfinder juga
# spawn `php` saat build — jadi node-only container nggak cukup.
#
# UID/GID runtime TIDAK di-hardcode di sini: image shinsenter mendukung
# APP_UID/APP_GID (lihat compose.override.yml). Nilai mesin tetap di file
# yang gitignored, sesuai standar ~/apps/LOCAL.md.
#
# Node diambil dari image resmi Debian (node:24) supaya glibc-nya sama
# dengan base shinsenter (Debian bookworm) — bukan dari alpine/musl.
FROM node:24 AS node

FROM shinsenter/laravel:php8.4

COPY --from=node /usr/local/bin/node /usr/local/bin/node
COPY --from=node /usr/local/lib/node_modules /usr/local/lib/node_modules

RUN ln -s /usr/local/lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm \
    && ln -s /usr/local/lib/node_modules/npm/bin/npx-cli.js /usr/local/bin/npx \
    && node --version \
    && npm --version
