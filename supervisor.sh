#! /bin/sh
echo "starting supervisor Started"
supervisord -c /etc/supervisor/conf.d/laravel-worker.conf
echo "Starting from script"
php -S localhost:9000 -t /usr/src/phpapp/public
echo "PHP server started"
