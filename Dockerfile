FROM php:8.2-apache

# Instalar extensiones necesarias
RUN docker-php-ext-install mysqli

# Copiar todo el código al directorio por defecto de apache
COPY . /var/www/html/

# Exponer puerto 80 para Apache
EXPOSE 80

# El comando CMD se omite porque la imagen ya inicia Apache
