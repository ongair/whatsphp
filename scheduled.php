<?php

  require_once('util.php');

  Dotenv::load(__DIR__);

  $services = list_services('tmp/services');

  foreach ($services as $service) {    
    if ($service != "")
    {
      // echo 'Service is '.$service.PHP_EOL;
      // $copied = copy($service, service_name($target).'.conf');
      $target = getenv('UPSTART_DIR').'/'.service_name($target).'.conf';
      echo 'Copying to '.$target.PHP_EOL;
    }
  }