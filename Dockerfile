# Imagen base de PHP con Apache
FROM php:8.1-apache

# Instalar la extensión mysqli
RUN docker-php-ext-install mysqli

# Activar mod_rewrite de Apache
RUN a2enmod rewrite

# Copiar todo el contenido del proyecto al contenedor
COPY . /var/www/html/

# Asignar permisos al usuario de Apache
RUN chown -R www-data:www-data /var/www/html && \
    chmod -R 755 /var/www/html

# Asegurar que la carpeta de imágenes tiene permisos públicos de lectura
RUN chmod -R 755 /var/www/html/imagenes_bicicletas

# Configurar PHP para mostrar errores (solo en desarrollo)
RUN echo "display_errors=On" >> /usr/local/etc/php/conf.d/docker-php.ini && \
    echo "error_reporting=E_ALL" >> /usr/local/etc/php/conf.d/docker-php.ini

# Puerto de exposición
EXPOSE 80

