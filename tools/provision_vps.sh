#!/bin/bash
# Aprovisionamiento de VPS Ubuntu 24.04 recien creado para Medisoft Hoteles.
# Correr como root en el servidor:  bash provision_vps.sh
# Deja: Docker + compose, firewall (22/80/443), fail2ban, swap y zona horaria.
set -euo pipefail

echo "== [1/6] Actualizando sistema =="
export DEBIAN_FRONTEND=noninteractive
apt-get update -y && apt-get upgrade -y

echo "== [2/6] Instalando Docker + compose plugin =="
if ! command -v docker >/dev/null; then
  curl -fsSL https://get.docker.com | sh
fi
docker --version && docker compose version

echo "== [3/6] Firewall (ufw): solo SSH, HTTP y HTTPS =="
apt-get install -y ufw
ufw default deny incoming
ufw default allow outgoing
ufw allow 22/tcp
ufw allow 80/tcp
ufw allow 443/tcp
ufw --force enable
ufw status

echo "== [4/6] fail2ban (protege SSH de fuerza bruta) =="
apt-get install -y fail2ban
systemctl enable --now fail2ban

echo "== [5/6] Swap de 2G (colchon para MySQL en 2GB RAM) =="
if [ ! -f /swapfile ]; then
  fallocate -l 2G /swapfile && chmod 600 /swapfile
  mkswap /swapfile && swapon /swapfile
  echo '/swapfile none swap sw 0 0' >> /etc/fstab
fi

echo "== [6/6] Zona horaria Mexico =="
timedatectl set-timezone America/Mexico_City

echo
echo "== LISTO. Siguientes pasos =="
echo "1. Clonar o subir el proyecto a /opt/medisoft-hoteles"
echo "2. Crear /opt/medisoft-hoteles/.env desde .env.example (passwords fuertes, APP_DOMAIN)"
echo "3. cd /opt/medisoft-hoteles && docker compose -f docker-compose.prod.yml up -d --build"
echo "4. Correr migraciones y el checklist: docs/checklist_deploy_produccion.md"
