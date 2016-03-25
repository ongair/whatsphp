<?php

  require 'vendor/autoload.php';
  require_once('ongair/client.php');

  # load the account from the cli arguments
  $account = $argv[1];

  # Load environment variables
  // Dotenv::load(__DIR__);
  Requests::register_autoloader();

  # create the client
  $client = new Client($account);
  $client->run();  