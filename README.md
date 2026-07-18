# Medisoft Hoteles - Entorno local Docker

Entorno local profesional para desarrollo de sistema hotelero en PHP puro + MySQL usando Docker.

## Servicios incluidos

- PHP 8.2 con Apache
- MySQL 8
- phpMyAdmin
- Docker Compose
- Volumen persistente para MySQL

## Puertos

- Sistema: http://localhost:8080
- phpMyAdmin: http://localhost:8081
- MySQL externo: localhost:3307

## Base de datos local

- Base de datos: la indicada por `DB_NAME` en `.env` (por ejemplo, `medisoft_hoteles_import` en el entorno local actual)
- Usuario: medisoft_user
- Contraseña local: medisoft_pass
- Root password local: root_pass

Estas contraseñas son únicamente para desarrollo local. En producción deben cambiarse.

## Comandos principales

Levantar el proyecto:

docker compose up -d

Ver contenedores:

docker compose ps

Apagar el entorno:

docker compose down

Ver logs:

docker compose logs -f

Instalar las herramientas de calidad:

composer install

Ejecutar el análisis estático de PHPStan:

composer analyse

La configuración inicial analiza `src/core` y `src/app/services` en nivel 0. El baseline permite adoptar reglas más estrictas progresivamente sin aceptar errores nuevos.

## Accesos

Sistema:

http://localhost:8080

phpMyAdmin:

http://localhost:8081

Datos de phpMyAdmin:

- Servidor: db
- Usuario: root
- Contraseña: root_pass

## Nota importante

Este entorno reemplaza XAMPP para desarrollo local. No está conectado con producción ni con Hostinger.
