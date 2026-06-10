#!/bin/sh
set -e

KEY_DIR=/app/config/jwt
PRIVATE_KEY="$KEY_DIR/private.pem"
PUBLIC_KEY="$KEY_DIR/public.pem"

echo "Creating directories..."
mkdir -p "$KEY_DIR" /app/var

if [ ! -f "$PRIVATE_KEY" ] || [ ! -f "$PUBLIC_KEY" ]; then
    echo "Generating JWT keys..."
    if [ -z "${JWT_PASSPHRASE:-}" ]; then
        openssl genrsa -out "$PRIVATE_KEY" 4096
        openssl rsa -pubout -in "$PRIVATE_KEY" -out "$PUBLIC_KEY"
    else
        openssl genpkey \
            -algorithm RSA \
            -aes-256-cbc \
            -pass pass:"$JWT_PASSPHRASE" \
            -out "$PRIVATE_KEY" \
            -pkeyopt rsa_keygen_bits:4096
        openssl rsa -pubout -in "$PRIVATE_KEY" -passin pass:"$JWT_PASSPHRASE" -out "$PUBLIC_KEY"
    fi
    echo "JWT keys generated successfully"
fi

echo "Setting permissions..."
chown -R www-data:www-data /app/var "$KEY_DIR" || true

echo "Clearing cache..."
php /app/bin/console cache:clear --no-warmup || true

echo "Starting PHP-FPM..."
exec "$@"
