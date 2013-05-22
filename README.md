# Hull PHP client

## Installation

If you are using composer, just add `hull/hull-sdk`  this to your `composer.json` file : 

    {
      "name" : "my-org/my-awesome-hull-project",
      "version" : "0.1.0",
      "require" : {
        "hull/hull-sdk": "dev-master"
      }
    }


## Usage

### Configuration

      <?php 
      require 'vendor/autoload.php';
      $hull = new Hull_Client(array( 'hull' => array(
        'host' => 'your-org.hullapp.dev',
        'appId' => 'your-app-id',
        'appSecret' => 'yout-app-secret'
      )));


### Making API Calls

`get`, `put`, `post` and `delete` methods are directly available on your instance of Hull_Client.

examples: 
    
    <?php 
    # To get the current app
    $hull->get('app');

    # To get the a list of comments on the current app (with pagination)
    $hull->get('app/comments', array('limit' => 10, 'page' => 2));

    # To update an existing object
    $hull->put('app', array('name' => 'My Super App'));

with Hull entities :

    $hull->get('entity', array('uid' => 'http://example.com'));
    $hull->put('entity', array('uid' => 'http://example.com', 'name' => 'My super Page'));
    $hull->delete('entity', array('uid' => 'http://example.com'));


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


## Contributing

1. Fork it
2. Create your feature branch (`git checkout -b my-new-feature`)
3. Commit your changes (`git commit -am 'Added some feature'`)
4. Push to the branch (`git push origin my-new-feature`)
5. Create new Pull Request
