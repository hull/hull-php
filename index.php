<?php
  require 'vendor/autoload.php';
  $hull = new Hull_Client();
  $app = $hull->getApp();
?>

<h1>Welcome, this is a Hull app : <?php echo $app->name ?></h1>
