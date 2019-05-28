FROM dcycle/drupal:8drush9

# Make sure opcache is disabled during development so that our changes
# to PHP are reflected immediately.
RUN echo 'opcache.enable=0' >> /usr/local/etc/php/php.ini

# Download contrib modules
RUN composer require drupal/devel \
  drupal/geofield_map \
  drupal/geofield

EXPOSE 80
