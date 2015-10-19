<?php
  require 'vendor/autoload.php';
  require_once('lib/activerecord/ActiveRecord.php');
  require_once('models/Account.php'); 
  require_once('util.php');

  Dotenv::load(__DIR__);

  _init_db();
  $count = 0;
  $limit = getenv('BATCH_COUNT');
  $accounts = Account::all(array('setup' => true));
  foreach ($accounts as $account) {
    $number = $account->phone_number;
    if (service_running($number) && $count < $limit) {
      $service_name = service_from_phone_number($number);
      $service = service_name($service_name);

      if (!empty($service)) {
        echo 'Should shut down '.$account->phone_number.PHP_EOL;
        $running = shell_exec('sudo service '.$service.' stop');                
      }      
    }
  }