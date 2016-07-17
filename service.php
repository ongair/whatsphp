<?php
  require 'vendor/autoload.php';
  require_once('ongair/client.php');
  
  // Dotenv::load(__DIR__);
  date_default_timezone_set('UTC');

  # load the account from the cli arguments
  $account = getenv('account');
  
  Requests::register_autoloader();

  # create the client
  $client = new Client($account);
  $client->run();

  l('Finished normally...');

  $wait = intval(getenv('wait_timeout'));
  l('Going to wait for '.$wait);

  sleep($wait);

  exit(2);