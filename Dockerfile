FROM dcycle/drupal:9

# Make sure opcache is disabled during development so that our changes
# to PHP are reflected immediately.
RUN echo 'opcache.enable=0' >> /usr/local/etc/php/php.ini

# Download contrib modules
RUN export COMPOSER_MEMORY_LIMIT=-1 && composer require \
  drupal/geofield_map \
  drupal/geofield

EXPOSE 80
