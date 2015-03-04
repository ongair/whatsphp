<?php

  require_once('lib/whatsapp/whatsprot.class.php');
  require_once('lib/activerecord/ActiveRecord.php');  
  require_once('lib/phpdotenv/Dotenv.php');
  require_once('models/Account.php');  
  require_once('util.php');

  Dotenv::load(__DIR__);
  l('Loaded environment');

  l(getenv('ENV'));
  l(getenv('DB'));

  _init_db();

  $count = Account::count(array('conditions' => 'setup = true'));
  l('Active '.$count);