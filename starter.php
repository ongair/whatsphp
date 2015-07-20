<?php
  require 'vendor/autoload.php';
  require_once('lib/activerecord/ActiveRecord.php');
  require_once('models/Account.php'); 
  require_once('util.php');

  Dotenv::load(__DIR__);

  _init_db();

  $accounts = Account::all(array('setup' => true));
  foreach ($accounts as $account) {
    $number = $account->phone_number;
    if (!service_running($number)) {
     	$service_name = service_from_phone_number($number);
	    $service = service_name($service_name);

      echo "Account: ".$account->name." - ".$account->phone_number."  needs to start : ".$service.PHP_EOL;      
    }
  }
