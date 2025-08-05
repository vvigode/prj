# Запуск проекта через Docker

1. Убедитесь, что установлен Docker (Desktop или Engine) и Docker Compose v2.
2. В терминале перейдите в каталог проекта (где лежит `docker-compose.yml`).
3. Запустите сервисы:

```bash
docker compose up -d --build
```

4. Откройте браузер и перейдите по адресу:

```
http://localhost:8080
```

Чтобы остановить и удалить контейнеры:

```bash
docker compose down        # остановить
docker compose down -v     # остановить и удалить тома (БД/Redis)
```