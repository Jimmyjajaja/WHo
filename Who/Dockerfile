# Dockerfile — works on Render (uses $PORT)
FROM php:8.2-cli
WORKDIR /app
COPY . /app
EXPOSE 8080
# Render provides $PORT; default to 8080 when running locally
CMD ["sh", "-lc", "php -S 0.0.0.0:${PORT:-8080} -t ."]