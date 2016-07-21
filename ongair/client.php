<?php

  require_once('util.php');
  require_once('exception.php');
  require_once('events.php');
  require_once('logger.php');

  class Client {

    private $phoneNumber;
    private $account;
    private $connected;
    private $waClient;

    function __construct($phoneNumber) {

      $this->phoneNumber = $phoneNumber;
      $this->connected = false;

      # set the timezone
      date_default_timezone_set('UTC');      
    }

    // Called from the service
    public function run() {
      try
      {
        dbg("About to run: $this->phoneNumber");

        # init the db
        $this->account = $this->_loadAccount();
        if ($this->account == null)  
          throw new InactiveAccountException($this->phoneNumber);

        dbg("Loaded the ".$this->account->name);

        $this->_loop();
        // exit(1);
        return true;
      }      
      catch(BlockedException $bEx) {
        err("Blocked ".$bEx->getMessage(), $bEx);

        notify_slack("Account ".$this->account->name."(".$this->account->phone_number.") failed authentication.");
        // exit($bEx->exitCode());
        return false;
      }
      catch(OngairException $oEx) {
        err("Ongair specific error: ".$oEx->getMessage(), $oEx, $oEx->canRestart());

        dbg("Can we restart? ".$oEx->canRestart());

        // Exit with the correct code
        // exit($oEx->exitCode());
        return $oEx->canRestart();
      }            
      catch(Exception $ex) {
        // exit(1);
        err("Error with running the application: ".$ex->getMessage(), $ex->getMessage());
        return true;
      }
    }


    // Program execution loop
    private function _loop() {
      if (!$this->is_active())
        throw new InactiveAccountException($this->phoneNumber);
       
      $this->waClient = new WhatsProt($this->account->phone_number, $this->account->name, false);
      $events = new Events($this);

      try 
      {
        // connect the client
        $this->getClient()->connect();

        // log in
        $this->getClient()->loginWithPassword($this->account->whatsapp_password);

        $start = microtime(true);
        $secs = 0;
        $timeout = intval(getenv('timeout'));

        while($secs < $timeout) {
          // Poll messages
          $this->getClient()->pollMessage(false);

          // perform any jobs
          $this->work(); 

          $now = microtime(true);
          $secs = intval($now - $start);
          
          // sleep
          usleep(1);          
        }

        // disconnect
        $this->getClient()->disconnect(); 
      }
      catch(ConnectionException $ce) {
        throw new OngairConnectionException($this->phoneNumber, "Unexpected connection exception", $ce);
      }

    }

    // loop through pending work
    private function work() {
      $jobs = JobLog::all(array('sent' => false, 'account_id' => $this->account->id, 'pending' => false));
      dbg("Number of jobs ".count($jobs));

      if(count($jobs) > 0) {
        foreach ($jobs as $job) {
          $this->perform($job);
        }
      }
    }

    // perform the actual job
    private function perform($job) {
      switch($job->method) {
        case "sendMessage":
          $this->sendMessage($job);
          break;
        case "sync":
          $this->sync($job);
          break;
        case "sendImage":
          $this->sendImage($job);
          break;
        case "profile_setStatus":
          $this->setProfileStatus($job);
          break;
        default:
          dbg("Not yet running jobs of type ".$job->method);
      }
    }

    // Set profile status
    private function setProfileStatus($job) {
      $this->getClient()->sendStatusUpdate($job->args);
      info("Set the status text to ".$job->args);
      $job->sent = true;
      $job->save();      
    }

    // Perform sync jobs
    private function sync($job) {
      $numbers = explode(',', $job->targets);      
      $this->getClient()->sendSync($numbers);
      $job->sent = true;
      $job->save();
    }

    // Send a text message
    private function sendMessage($job) {      
      $id = $this->getClient()->sendMessage($job->targets, $job->args);      
      info("Sent ".$job->args." to ".$job->targets);
      $job->whatsapp_message_id = $id;
      $job->sent = true;
      $job->save();
    }

    // Send an image message
    private function sendImage($job) {
      $message = Message::find_by_id($job->message_id);
      $caption = $message->text;

      $asset = Asset::find_by_id($message->asset_id);
      $url = $asset->url;

      $path = download($url);
      if ($path) {
        $id = $this->getClient()->sendMessageImage($job->targets, $path, false, 0, "", $caption);
        info("Sent image to ".$job->targets);
        $job->whatsapp_message_id = $id;
        $job->sent = true;
        $job->save();
        unlink($path);
      }
      else
        err("Was not able to download image from ".$url);
    }

    // client accessor
    public function getClient() {
      return $this->waClient;
    }

    // account model accessor
    public function getAccount() {
      return $this->account;
    }

    // modifier for the connected status
    public function toggleConnection($status) {
      $this->connected = $status;
    }

    // Is the account active
    private function is_active() {
      return $this->account->setup == true;
    }

    // Load the database connection
    private function _loadAccount() {
      $env = getenv('env');
      $db = getenv('db');

      $cfg = ActiveRecord\Config::instance();
      $cfg->set_default_connection($env);
      $cfg->set_model_directory(__DIR__.'/models');

      $cfg->set_connections(
        array(
          $env => $db
        )
      );
      return Account::find_by_phone_number($this->phoneNumber);
    }

    // post to the ongair url
    public function post($url, $data) {
      $headers = array('Content-Type' => 'application/json', 'Accept' => 'application/json');

      $url = getenv("url").$url;
      $data['account'] = $this->phoneNumber;

      Requests::post($url, $headers, json_encode($data), array('timeout' => 5000));

      dbg("Posted ".json_encode($data)." to the server : $url");
    }
  }