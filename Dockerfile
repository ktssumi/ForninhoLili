FROM php:8.2-apache
RUN a2enmod rewrite headers \
 && sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf
COPY docker/apache-storage.conf /etc/apache2/conf-available/storage-protect.conf
RUN a2enconf storage-protect
COPY . /var/www/html/
RUN chmod +x /var/www/html/docker/entrypoint.sh
EXPOSE 80
CMD ["/var/www/html/docker/entrypoint.sh"]
