#!/bin/bash
#
# Developers can update the starter database.
#
set -e

docker-compose exec drupal /bin/bash -c "echo 'show tables' | drush sqlc | grep cache_ | xargs -I {} echo 'truncate {};' | drush sqlc"
docker-compose exec drupal /bin/bash -c 'drush sql-dump' > ./docker-resources/initial-db-for-development.sql
