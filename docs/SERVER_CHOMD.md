mkdir -p /var/www/html/dashboard/storage/logs
touch /var/www/html/dashboard/storage/logs/web_audit.log

chown -R root:apache /var/www/html/dashboard/storage
chmod 2750 /var/www/html/dashboard/storage
chmod 2770 /var/www/html/dashboard/storage/logs
chmod 0660 /var/www/html/dashboard/storage/logs/web_audit.log