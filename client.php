<?php

  require_once('models/Account.php');  
  require_once('models/JobLog.php');
  require_once('events.php');  
  // require_once('lib/pubnub/autoloader.php');
  
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
      $debug = (bool) getenv('DEBUG');

      $this->_init_db();
      $this->account_id = $this->get_account_id();          

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
          $this->get_jobs();

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

    private function get_jobs() {
      $jobs = JobLog::all(array('sent' => false, 'account_id' => $this->account_id, 'pending' => false));
      l("Num jobs ".count($jobs));

      foreach ($jobs as $job) {        
        $this->do_job($job);
      }
    }

    private function do_job($job) {
      if ($job->method == "sendMessage") {
        $this->send_message($job);
      }      
    }

    private function send_message($job) {
      $id = $this->wa->sendMessage($job->targets, $job->args);      
      $job->whatsapp_message_id = $id;
      $job->sent = true;
      $job->save();
    }

    public function get_account_id() {
      return Account::find_by_phone_number($this->account)->id;
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