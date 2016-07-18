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
  $count = 1;
  
  while($run) {
    $client = new Client($account);
    $run = $client->run();

    info("Finished execution ".$count.". Re-run ".($run == true));

    if ($run) {
      sleep($wait);  
    }      
    $count++;
  }
  
  exit(0);