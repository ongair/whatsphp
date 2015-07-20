<?php
  require 'vendor/autoload.php';
  require_once('lib/activerecord/ActiveRecord.php');
  require_once('models/Account.php'); 
  require_once('util.php');

  Dotenv::load(__DIR__);

  _init_db();

  $accounts = Account::all(array('setup' => true));
  foreach ($accounts as $account) {
    echo "Account: ".$account->name." - ".$account->phone_number.PHP_EOL;
  }