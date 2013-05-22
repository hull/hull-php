<?php
  require 'vendor/autoload.php';
  $hull = new Hull_Client(array( 'hull' => array(
    'host' => 'super.hullapp.dev',
    'appId' => '512b4a7cd45037759b000006',
    'appSecret' => '69c696bdfd925d838b376386974a242a'
  )));
  $app = $hull->get('app');
  $user = array('email' => 'contact@hull.io', 'name' => 'Contact Hull', 'id' => '42');
?>

<html>
<head>
  <link href="//netdna.bootstrapcdn.com/twitter-bootstrap/2.3.1/css/bootstrap-combined.min.css" rel="stylesheet">
  <script src="//ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script>
  <script src="http://js.hull.dev/dist/develop/hull.js"></script>
  <script>
  Hull.init({
    appId: '<?php echo $hull->appId ?>',
    orgUrl: 'http://<?php echo $hull->host ?>',
    userHash: '<?php echo $hull->userHash($user) ?>',
    debug: true
  })
  </script>
</head>

<body>
  <div class="container">
    <h1>Welcome, this is a Hull app (<?php echo $hull->host ?>) : <?php echo $app->name ?></h1>
    <div data-hull-widget='identity@hull'></div>  
  </div>
</body>

</html>