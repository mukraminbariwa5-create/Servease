#!/bin/sh
# Starts the Servease demo: MariaDB, a fresh database with demo data, then Apache.
set -e
cd /var/www/html
export PORT="${PORT:-10000}"
echo "Listen ${PORT}" > /etc/apache2/ports.conf

mkdir -p /run/mysqld && chown mysql:mysql /run/mysqld
mysqld_safe --user=mysql > /dev/null 2>&1 &
for i in $(seq 1 60); do
    mysqladmin ping --silent 2>/dev/null && break
    sleep 1
done
mysql -u root <<'SQL'
CREATE DATABASE IF NOT EXISTS servease CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS 'servease'@'127.0.0.1' IDENTIFIED BY 'servease-demo';
CREATE USER IF NOT EXISTS 'servease'@'localhost' IDENTIFIED BY 'servease-demo';
GRANT ALL PRIVILEGES ON servease.* TO 'servease'@'127.0.0.1';
GRANT ALL PRIVILEGES ON servease.* TO 'servease'@'localhost';
FLUSH PRIVILEGES;
SQL

php database/install.php
date +%F > storage/cache/demo-reset.txt
chown -R www-data:www-data storage uploads
exec apache2-foreground
