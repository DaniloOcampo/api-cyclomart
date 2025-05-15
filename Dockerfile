# Imagen base de PHP con Apache
FROM php:8.1-apache

# Instalar MySQLi y extensiones necesarias
RUN docker-php-ext-install mysqli

# Activar mod_rewrite de Apache
RUN a2enmod rewrite

# Copiar los archivos de tu proyecto al contenedor
COPY . /var/www/html/

# Dar permisos a los archivos
RUN chown -R www-data:www-data /var/www/html

# Habilitar errores en desarrollo
RUN echo "display_errors=On" >> /usr/local/etc/php/conf.d/docker-php.ini && \
    echo "error_reporting=E_ALL" >> /usr/local/etc/php/conf.d/docker-php.ini

# Puerto por defecto
EXPOSE 80
