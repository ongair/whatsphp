<?php

  require 'models/Account.php';  
  
  class Client
  {
    private $account;
    private $password;
    private $nickname;
    private $wa;
    private $connected;

    function __construct($account, $password, $nickname) {
      $this->account = $account;
      $this->password = $password;
      $this->nickname = $nickname;
      $this->connected = false;
      $this->identity = "";

      $this->_init_db();
      $debug = (bool) getenv('DEBUG');
      

      if ($this->is_active()) {
        $this->wa = new WhatsProt($this->account, $this->identity, $this->nickname, $debug);
        $events = new Events($this, $this->wa);
        $events->setEventsToListenFor($events->activeEvents);        
      }       
    }

    public function loop() {
      if (!$this->connected) {
        $this->wa->connect();
        $this->wa->loginWithPassword($this->password);          

        $start = microtime(true);
        $secs = 0;
        $timeout = intval(getenv('TIMEOUT'));
        
        while($secs < $timeout) {
          
          $this->wa->pollMessage(true);

          $mid = microtime(true);
          $secs = intval($mid - $start);
          l('Disconnect in: '.($timeout - $secs));
        }

        $end = microtime(true);
        $timediff = intval($end - $start);
        $this->wa->disconnect();
      }      
    }

    public function toggleConnection($status) {
      $this->connected = $status;
    }

    private function is_active() {
      return Account::exists(array('phone_number' => $this->account, 'setup' => true));
    }

    private function _init_db() {
      $env = getenv('ENV');
      $db = getenv('DB');

      $cfg = ActiveRecord\Config::instance();      
      $cfg->set_default_connection($env);
      $cfg->set_model_directory('models');

      $cfg->set_connections(
        array(
          $env => $db
        )
      );
    }
  }