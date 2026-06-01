#!/usr/bin/env bash
# DYNOVA bootstrap: makes the workspace self-healing across container restarts.
#
# Idempotent. Safe to run on every backend startup.
#   1. Installs PHP + MariaDB packages if missing (apt cache)
#   2. Ensures mariadbd is running on 127.0.0.1:3306
#   3. Ensures the dynova_network DB + user exist and schema is loaded
#
# Exits 0 on success, non-zero on fatal error.

set -u
LOG="/var/log/dynova-bootstrap.log"
exec >>"$LOG" 2>&1
echo "==== bootstrap $(date -Iseconds) ===="

DB_NAME="dynova_network"
DB_USER="dynova"
DB_PASS="dynova_pass_2026"
SQL_FILE="/app/dynova/sql/dynova_full_install.sql"

need_install=0
command -v php       >/dev/null 2>&1 || need_install=1
command -v mariadbd  >/dev/null 2>&1 || need_install=1
command -v mariadb   >/dev/null 2>&1 || need_install=1

if [ "$need_install" = "1" ]; then
    echo "[bootstrap] installing php + mariadb"
    DEBIAN_FRONTEND=noninteractive apt-get update -qq
    DEBIAN_FRONTEND=noninteractive apt-get install -y -qq \
        php-cli php-mysql php-mbstring php-curl php-xml php-gd php-zip \
        mariadb-server
fi

mkdir -p /var/run/mysqld /var/lib/mysql
chown -R mysql:mysql /var/run/mysqld /var/lib/mysql

# Initialise data dir if empty (first boot after container rebuild)
if [ ! -d /var/lib/mysql/mysql ]; then
    echo "[bootstrap] initialising mariadb data dir"
    mariadb-install-db --user=mysql --datadir=/var/lib/mysql --auth-root-authentication-method=normal >/dev/null
fi

# Start mariadbd if not running
if ! pgrep -x mariadbd >/dev/null; then
    echo "[bootstrap] starting mariadbd"
    nohup mariadbd --user=mysql \
        --datadir=/var/lib/mysql \
        --socket=/var/run/mysqld/mysqld.sock \
        --pid-file=/var/run/mysqld/mariadb.pid \
        --bind-address=127.0.0.1 \
        >/var/log/mariadb.log 2>&1 &
    disown
fi

# Wait until it's ready (max 30s)
for i in $(seq 1 60); do
    if mariadb -uroot -e "SELECT 1" >/dev/null 2>&1; then
        break
    fi
    sleep 0.5
done

# Ensure DB + user exist (idempotent)
mariadb -uroot <<SQL
CREATE DATABASE IF NOT EXISTS ${DB_NAME}
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';
CREATE USER IF NOT EXISTS '${DB_USER}'@'127.0.0.1' IDENTIFIED BY '${DB_PASS}';
GRANT ALL PRIVILEGES ON ${DB_NAME}.* TO '${DB_USER}'@'localhost';
GRANT ALL PRIVILEGES ON ${DB_NAME}.* TO '${DB_USER}'@'127.0.0.1';
FLUSH PRIVILEGES;
SQL

# Load schema only if the DB is empty (count tables)
TABLE_COUNT=$(mariadb -uroot -N -B -e \
    "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='${DB_NAME}'")
echo "[bootstrap] existing tables in ${DB_NAME}: ${TABLE_COUNT}"

if [ "${TABLE_COUNT:-0}" -lt 5 ] && [ -f "$SQL_FILE" ]; then
    echo "[bootstrap] loading schema from $SQL_FILE"
    mariadb -uroot "$DB_NAME" < "$SQL_FILE"
fi

# Make sure /app/dynova/logs is writable for the PHP app
mkdir -p /app/dynova/logs
chmod -R 777 /app/dynova/logs /app/dynova/public/uploads 2>/dev/null || true

echo "[bootstrap] done $(date -Iseconds)"
