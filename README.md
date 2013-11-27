# Hull PHP client

## Installation

If you are using composer, just add `hull/hull` to your `composer.json` file :

    {
      "name" : "my-org/my-awesome-hull-project",
      "version" : "0.1.0",
      "require" : {
        "hull/hull": "dev-master"
      }
    }

Please checkout [Composer's documentation here](http://getcomposer.org/).

## Usage

### Configuration

      <?php
      require 'vendor/autoload.php';
      $hull = new Hull_Client(array( 'hull' => array(
        'host' => 'your-org.hullapp.io',
        'appId' => 'your-app-id',
        'appSecret' => 'yout-app-secret'
      )));


### Making API Calls

`get`, `put`, `post` and `delete` methods are directly available on your instance of Hull_Client.

#### examples

    <?php
    # To get the current app
    $hull->get('app');

    # To get the a list of comments on the current app (with pagination)
    $hull->get('app/comments', array('limit' => 10, 'page' => 2));

    # To update an existing object
    $hull->put('app', array('name' => 'My Super App'));

##### with Hull entities

    $hull->get('entity', array('uid' => 'http://example.com'));
    $hull->put('entity', array('uid' => 'http://example.com', 'name' => 'My super Page'));
    $hull->delete('entity', array('uid' => 'http://example.com'));

##### API Calls authenticated as a User

    // As a Specific user
    $hull->asUser('xxxx')->get('me');

    // If there is a current user
    $hull->asCurrentUser()->get('me');

`asUser` and `asCurrentUser` return new instances of Hull_Client, so you can also do :

    $hullForCurrentUser = $hull->asCurrentUser();
    $hullForCurrentUser->get('me');


### Getting the current user connected via hull.js

You can get the current user connected via Hull.js via the `currentUserId` method.

*Example:*

    $userId = $hull->currentUserId();

And fetch the current user infos from Hull's API:

    $currentUserId = $hull->currentUserId();
    $currentUser   = $hull->get($currentUserId);


### Bring your own users

In addition to providing multiple social login options, Hull allows you to create and authenticate users that are registered within your own app.

To use this feature, you just have to add a `userHash` key at the initialization of hull.js :

In you view :

    <?php $user = array('id' => '123', 'email' => 'bill@hullapp.io', 'name' => 'Bill Evans'); ?>
    <script>
      Hull.init({
        appId: '<?php echo $hull->appId ?>',
        orgUrl: 'http://<?php echo $hull->host ?>',
        userHash: '<?php echo $hull->userHash($user) ?>',
        debug: true // optional
      })
    </script>


To give / remove "admin" access to those users you can add the "is_admin" flag to your userHash.

example:

    <?php $user = array('id' => '123', 'email' => 'bill@hullapp.io', 'name' => 'Bill Evans', 'is_admin' => true); ?>
    <script>
      Hull.init({
        appId: '<?php echo $hull->appId ?>',
        orgUrl: 'http://<?php echo $hull->host ?>',
        userHash: '<?php echo $hull->userHash($user) ?>',
        debug: true // optional
      })
    </script>

### Hooks

[Hooks](hull.io/docs/libraries/#hooks) allow you to be notified every time an
object in your app is created, updated or deleted.

```php
<?php

$event = $hull->getEvent();

if ($event->valid) {
  // Do something with $event->payload
  // To guard against replay-attacks you can use $event->nonce
} else {
  // $event->error
}

```

### Windows users

If you're a Windows user, you may encounter an issue that prevents hull from making requests.
This is due to the default implementation of SSL. If you're running into any kind of trouble under Windows,
activate debug mode as indicated above. Doing so will allow you to perform requests as expected.

Please note that you should not keep this activated in production, as it must be fixed system-wide for security reasons.

We do not mitigate on security. Period.

## Contributing

1. Fork it
2. Create your feature branch (`git checkout -b my-new-feature`)
3. Commit your changes (`git commit -am 'Added some feature'`)
4. Push to the branch (`git push origin my-new-feature`)
5. Create new Pull Request
