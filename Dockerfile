FROM php:8.4-cli-bookworm

RUN apt-get update \
    && apt-get install -y --no-install-recommends ca-certificates libcurl4-openssl-dev \
    && docker-php-ext-install curl \
    && rm -rf /var/lib/apt/lists/*

WORKDIR /app
COPY . /app

ENV KIMIA_WRITE_VERIFY_ENABLE=0

RUN php -m | grep -qi '^curl$' \
    && php -l backend/bin/kimia_preflight_readonly.php \
    && sh -n ops/chabokan-kimia-runner/preflight.sh \
    && sh -n ops/chabokan-kimia-runner/boot.sh

CMD ["sh", "ops/chabokan-kimia-runner/boot.sh"]
