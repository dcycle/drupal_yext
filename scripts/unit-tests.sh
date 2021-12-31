#!/bin/bash
#
# Run unit tests.
#

docker run --rm -v "$(pwd)":/app dcycle/phpunit:1 \
  --group myproject
