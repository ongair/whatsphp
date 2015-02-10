<?php

  require 'models/Account.php';  
  
  class Client
  {
    private $account;
    private $password;
    private $nickname;
    private $wa;

    function __construct($account, $password, $nickname) {
      $this->account = $account;
      $this->password = $password;
      $this->nickname = $nickname;

      $this->_init_db();

      if ($this->is_active()) {
        $this->wa = new WhatsProt($username, $identity, $nickname, $debug);
      }       
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