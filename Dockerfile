FROM php:8.4-cli-bookworm

RUN apt-get update \
    && apt-get install -y --no-install-recommends ca-certificates libcurl4-openssl-dev \
    && docker-php-ext-install curl \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /app
COPY . /app

# This deployment is READ-ONLY. Runtime boot also forces the gate closed.
ENV KIMIA_WRITE_VERIFY_ENABLE=0

RUN php -m | grep -qi '^curl$' \
    && find backend/app/Integrations/Kimia/Verify -name '*.php' -print0 | xargs -0 -n1 php -l \
    && php -l backend/bin/kimia_verify_runner.php \
    && sh -n ops/chabokan-kimia-runner/preflight.sh \
    && sh -n ops/chabokan-kimia-runner/boot.sh

CMD ["sh", "ops/chabokan-kimia-runner/boot.sh"]
