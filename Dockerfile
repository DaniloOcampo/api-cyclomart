FROM php:8.2-apache

# Copiar todos los archivos de tu proyecto al directorio web
COPY . /var/www/html/

# Habilitar mod_rewrite para usar RewriteEngine en .htaccess
RUN a2enmod rewrite

# Opcional: dar permisos correctos (ajusta según necesidad)
RUN chown -R www-data:www-data /var/www/html

# Exponer el puerto 10000 (igual que usabas antes)
EXPOSE 10000

# Cambiar configuración Apache para permitir .htaccess
RUN sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

# Iniciar Apache en modo foreground (esto es lo que Render usará para levantar el contenedor)
CMD ["apache2-foreground"]
