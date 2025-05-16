# Imagen base de PHP con Apache
FROM php:8.1-apache

# Instalar mysqli
RUN docker-php-ext-install mysqli

# Activar mod_rewrite de Apache
RUN a2enmod rewrite

# Copiar todo el contenido del proyecto a /var/www/html
COPY . /var/www/html/

# Dar permisos al usuario www-data para que Apache pueda leer/escribir si es necesario
RUN chown -R www-data:www-data /var/www/html

# Habilitar errores para desarrollo (puedes quitar esto en producción)
RUN echo "display_errors=On" >> /usr/local/etc/php/conf.d/docker-php.ini && \
    echo "error_reporting=E_ALL" >> /usr/local/etc/php/conf.d/docker-php.ini

# Exponer puerto 80
EXPOSE 80

