<?php
  require 'vendor/autoload.php';
  require_once('ongair/client.php');
  
  // Dotenv::load(__DIR__);
  date_default_timezone_set('UTC');

  # load the account from the cli arguments
  $account = getenv('account');
  
  Requests::register_autoloader();

  # create the client
  $run = true;
  $debug = getenv('debug'); 
  $wait = intval(getenv('wait_timeout'));
  
  while($run) {
    $client = new Client($account);
    $run = $client->run();

    l("Finished. Re-run ".($run == true));

    if ($run) {
      l('Going to wait for '.$wait);
      sleep($wait);  
    }      
  }
  
  exit(0);