# PhapBot

> "does php have a discord framework?" - steve

## Runtime security and release checks

The image runs a CLI Discord bot (`php /app/phap.php`), not a PHP web
server. It retains the official PHP ZTS configuration and extensions.

PHP 8.5.12 fixes GHSA-w8hw-w9fx-j79w and GHSA-6wmf-4rv5-p3rp.
At the time of this update, Docker Hub's 8.5.12 ZTS tags have empty image
indexes and cannot be pulled. The build therefore recompiles the official,
digest-pinned 8.5.11 ZTS image using the PHP 8.5.12 source archive and
[php.net's published SHA-256](https://www.php.net/releases/index.php?json&version=8.5.12).
`docker/rebuild-php.sh` reuses the official configure options and checks that
all previously loaded extensions remain available. Once official 8.5.12
platform images are published, this source-build workaround can be removed.

Alpine `ca-certificates` is upgraded to at least `20261020-r0` for the
[eMudhra compromise](https://emudhra.we-have-a-problem.com/).
PHP OpenSSL (used by React's HTTP/WebSocket connector), cURL and build-time
Composer explicitly use `/etc/ssl/certs/ca-certificates.crt`. No Composer
executable or its embedded fallback CA bundle is included in the final image;
the application dependencies do not include `composer/ca-bundle`.

Pull requests build and check the exact head revision. CI checks PHP syntax,
ZTS/version, extension availability, Composer validation/platform requirements
and security audit, autoloading, utility commands and assets. It also verifies
that the application's default React TLS connector and PHP cURL accept valid
HTTPS and reject both compromised test chains with certificate errors, not
merely DNS failures or timeouts. No Discord credentials are used and no bot is
started. This is not a live Discord integration test.

Main publishes the **same tested image** to `ghcr.io/jackharrhy/phapbot:latest`
and `sha-<full-commit>`, with the source revision in its OCI labels. The workflow
publishes an image only; deployment/restart is separate.
