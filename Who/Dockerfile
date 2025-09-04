# Dockerfile — PHP on Render
FROM php:8.2-cli
WORKDIR /app
COPY . /app
EXPOSE 8080
CMD ["sh","-lc","php -S 0.0.0.0:${PORT:-8080} -t ."]
