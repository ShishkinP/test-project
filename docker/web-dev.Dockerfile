FROM nginx:stable-alpine

COPY config/default.conf /etc/nginx/conf.d/default.conf

WORKDIR /var/www/app