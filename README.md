Drupal Yext
=====

[![CircleCI](https://circleci.com/gh/dcycle/drupal_yext.svg?style=svg)](https://circleci.com/gh/dcycle/drupal_yext)

A Drupal 8 module which allows you to import data from Yext using [its API](https://developer.yext.ca/docs/live-api).

Usage
-----

### Step 1: Install as you would any Drupal module:

    drush dl drupal_yext

### Step 2: Make sure you have a node type in which to save Yext data:

Each Yext record will create a new node. Make sure you create a node type which will correspond to Yext records, and a field which will contain the Yext unique ID.

For the sake of this example, we will use "article" as a node type, and, as a field, we will create a new field of type "Text (plain)" in article with the machine name "field_yext_unique_id".

Also create a field which will contain information about when the corresponding record was last updated on Yext; call this field something like "field_yext_last_updated".

### Step 2: Configure Drupal Yext:

Go to /admin/config/yext/yext, and add the following:

* Basic node information: target node type: **article**;
* Basic node information: target ID field: **field_yext_unique_id**;
* Basic node information: target last updated field: **field_yext_last_updated**;
* Yext base URL: https://api.yext.com for dev, or https://liveapi.yext.com for prod;
* Your account number: "me" seems to always work; you can also enter your actual account number;
* Yext API key: enter your API key from Yext;

Make sure that pressing the "Test the API key" results in success message.

**Save the form** for changes to take effect.

### Step 3: Import data:

You can use the Yext Import Status in /admin/config/yext/yext to import data day-by-day.

If that times out, you can also use:

    drush ev "drupal_yext_import_some()"

This will update the "Next check date for Yext" by one day, and import data for taht day. If there is no data which was updated on that day, nothing will be imported! **So you will need to run 30, 40 times** to make sure it works!

When you are sure it works, you can then add a cron job, say, every 15 mintues on your environment, with

    drush ev "drupal_yext_import_some()"

### Note that this module does not implement cron!

You will need to either add your own cron job or add:

    drupal_yext_import_some();

to your custom module's cron hook implementation.

Deleting Yext entities
-----

This can be tested by logging onto the Yext backend interface, and clicking "+ Add Entity" : "Add single entity".

Your new entity must contain:

* A first name
* A last name
* A dummy address (you will get "Address is not valid" and you can click "use anyway - not recommended")
* Once you save, the Entity ID field will be populated with something like "3052937228426523195".

You can now create a new Drupal node. Make its title "whatever" and fill in its Yext Unique ID field with the Entity ID from above (for example 3052937228426523195). When you save it, Yext will fetch the dummy data.

Now go back to Yext and, in "Select action", select "Delete entity".

Herein lies the problem: the next time we attempt to fetch changes from the Yext API, we are not informed that "3052937228426523195" has been deleted, so it can remain present in Drupal even if it is gone from Yext.

This is why we recommend not deleting Yext nodes, but rather having something like a custom "permenently closed" field. But, if you do delete Yext nodes like this, you can enable the drupal_yext_sync_deleted module which will:

* periodically check Yext for one nodes, thus eventually cycling through all nodes and starting again.
* unpublish them and set their IDs to "DELETED IN YEXT - ...", and unpublish them, if they have been deleted from Yext.

Yext will return a Page Not Found in case an item has been deleted. **Please be advised that if there is a problem with Yext when we are checking whether an entity is deleted, the entity will be marked as deleted nonetheless; therefore, the drupal_yext_sync_deleted should be considered a work in progress.**

Have Yext nodes you manually created prior to installing this module?
-----

Let's say you created a "Location" node "Dr. Jane Smith", and then you enable this module to automatically import items from Yext, if a "Dr. Jane Smith" exists in Yext, then a new node will be created. If you want to update an existing node if it has the same title as a Yext `locationName`, you can enable the included `drupal_yext_find_by_title` module.

Issue queue and pull requests
-----

Please use the [Drupal issue queue](https://www.drupal.org/project/issues/search/drupal_yext) for this project.

Please run tests by running `./scripts/test.sh` (you do not need to install or configure anything except Docker to run this) on your proposed changes before suggesting patches. Use [GitHub](https://github.com/dcycle/drupal_yext) for pull requests.

Adding new mapping once you already have data
-----

Go to /admin/config/yext/yext, in the "Basic Node Information" section, and:

* If you don't want to re-fetch data from Yext, deselect "Always update raw data on save, if possible". This might be the case, for example, if you have added new mapping to a Yext field which existed when the import first happened.
* If you do want to re-fetch data from Yext, select "Always update raw data on save, if possible". This might be the case, for example, if a new field was added to Yext since the last import.

If you add new mapping but already have nodes in your system, you can run:

    drush ev "drupal_yext()->resaveAllExisting('print_r', 100, 0)"

In the above example, we are using print_r as a logging function, and chunk sizes of 100 when saving collections of nodes. You can tweak that number if you are getting memory errors. The last parameter, 0, means we'll resave all nodes. If you have a very large data set, and your script crashes at node, say, 549221, you re-run the above script with 549221 as a third parameter.

Be careful as this will **overwrite** all fields in your target node type with data taken from the Yext raw API output in your node's "yext raw data" field. Back up your database before trying this, please.

If you want to delete all existing nodes of the target type, obviously back up your database, and run:

    drush ev "drupal_yext()->deleteAllExisting()"

This might be useful if you want to reset the importer and start from scratch.

Development
-----

The code is available on [GitHub](https://github.com/dcycle/drupal_yext) and [Drupal.org](https://www.drupal.org/project/drupal_yext).

Automated testing is on [CircleCI](https://circleci.com/gh/dcycle/drupal_yext).

To install a local version for development or evaluation, install Docker and run `./scripts/deploy.sh`.

Debugging
-----

If you know a Doctor's ID, and want to fetch it from Yext, you can go to /devel/php and run:

    $id = 123456;
    $y = drupal_yext();
    $url = $y->buildUrl('/v2/accounts/me/locations/' . $id);
    $body = (string) $y->httpGet($url)->getBody();
    dpm(json_decode($body, TRUE));

If you are not sure if a particular field (say, 12819) or a bio is actually importing, but you have lots of content which does not have these fields or a bio, you can create a view which filters by "yext raw data contains:" and set it to only display content which has your field or a description.

Getting an individual Yext record
-----

Yext locations have unique IDs which look like "0013800002eNtybAAC". To obtain the record from yext you can call:

    drupal_yext()->getRecordByUniqueId('0013800002eNtybAAC');
