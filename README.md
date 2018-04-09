Drupal Yext
=====

[![CircleCI](https://circleci.com/gh/dcycle/drupal_yext.svg?style=svg)](https://circleci.com/gh/dcycle/drupal_yext)

A Drupal 8 module which allows you to import data from Yext.

Usage
-----

### Step 1: Install as you would any Drupal module:

    drush dl drupal_yext

### Step 2: Make sure you have a node in which to save Yext data:

Each Yext record will create a new node. Make sure you create a node type which will correspond to Yext records, and a field which will contain the Yext unique ID.

For the sake of this example, we will use "article" as a node type, and, as a field, we will create a new field of type "Text (plain)" in article with the machine name "field_yext_unique_id".

### Step 2: Configure Drupal Yext:

Go to /admin/config/yext/yext, and do the follwoing



### Step 3: Import data using:

    drush ev \'drupal_yext_import_some()\'"

Development
-----

The code is available on [GitHub](https://github.com/dcycle/drupal_yext) and [Drupal.org](https://www.drupal.org/project/drupal_yext).

Automated testing is on [CircleCI](https://circleci.com/gh/dcycle/drupal_yext).

To install a local version for development or evaluation, install Docker and run `./scripts/deploy.sh`.
