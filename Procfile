web: vendor/bin/heroku-php-nginx -C nginx_app.conf public/

nginx_app.conf :
location / {
    try_files $uri /index.php$is_args$args;
}
location ~ ^/index\.php(/|$) {
    try_files @heroku-fcgi @heroku-fcgi;
    internal;
}
