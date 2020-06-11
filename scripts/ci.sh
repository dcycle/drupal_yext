#!/bin/bash
#
# Run tests on Circle CI.
#
set -e

echo "Running fast tests"
./scripts/test.sh
echo "Deploying on Drupal 9"
./scripts/deploy.sh
echo "Running self tests on Drupal 9"
./scripts/self-test-running-environment.sh
echo "Killing Drupal 9"
docker-compose down -v
echo "Deploying on Drupal 8"
./scripts/deploy.sh 8
echo "Running self tests on Drupal 8"
./scripts/self-test-running-environment.sh
echo "Killing Drupal 8"
docker-compose down -v
