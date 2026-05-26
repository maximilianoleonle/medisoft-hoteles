-- Inicialización básica de MySQL para Medisoft Hoteles
-- Este archivo NO importa todavía la base real del sistema.
-- Solo asegura que la base exista con codificación correcta.

CREATE DATABASE IF NOT EXISTS medisoft_hoteles
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE medisoft_hoteles;

SET NAMES utf8mb4;
