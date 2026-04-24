#!/bin/bash
echo ":::::: set permissions on .../storage/"
chmod -R 777 /var/www/html/storage/
# chmod -R 555 /var/www/html
# chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

echo ":::::::::::::::::::::::::::::::::::::::::::::"
echo ":::::: main app started on the port 9000"
echo ":::::::::::::::::::::::::::::::::::::::::::::"
php-fpm &

echo ":::::::::::::::::::::::::::::::::::::::::::::"
echo ":::::: nginx started"
echo ":::::::::::::::::::::::::::::::::::::::::::::"
nginx -g 'daemon off;'
