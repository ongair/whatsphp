<?php
  require 'vendor/autoload.php';
  require_once('lib/activerecord/ActiveRecord.php');
  require_once('models/Account.php'); 
  require_once('util.php');

  Dotenv::load(__DIR__);

  $services = list_services(getenv('CWD').'tmp/services');
  _init_db();

  foreach ($services as $service) {    
    if ($service != "" || endsWith($service, "No such file or directory"))
    {
      $line = shell_exec('grep ongair.php '.$service);
      preg_match("/\d+/", $line, $numbers);
      
      if (count($numbers) > 0) {
        $phone_number = $numbers[0];

        $account = Account::find_by_phone_number($phone_number);
        if ($account != NULL) {          
          $name = service_name($service);
          $target = getenv('UPSTART_DIR').'/'.$name.'.conf';
          rename($service, $target);

          if (is_production()) {
            #shell_exec('service '.$name.' start');
          }
        }        
      }      
    }
  }

  echo date("H:i:s")." Done.".PHP_EOL;

  