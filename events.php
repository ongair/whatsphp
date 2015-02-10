<?php
  require 'lib/whatsapp/events/AllEvents.php';
  require 'models/Message.php';  

  class Events extends AllEvents
  {
    public $acount;
    private $client;

    public function __construct($client, $wa) {
      parent::__construct($wa);
      $this->client = $client;
      $this->url = getenv('URL');
    }

    public $activeEvents = array(
      'onConnect',
      'onDisconnect',
      'onGetMessage',
      'onGetReceipt'
    );

    public function onGetMessage( $me, $from, $id, $type, $time, $name, $body )
    {
      l("Message from $name: $body");

      # check if the message exists in the db
      if (!$this->exists($id)) {
                

        $url = $this->url.'/messages';
        $data = array('account' => $me, 'message' => array( 'text' => $body, 'phone_number' => get_phone_number($from), 'message_type' => 'Text', 'whatsapp_message_id' => $id, 'name' => $name) );
        
        $headers = array('Content-Type' => 'application/json', 'Accept' => 'application/json');
        Requests::post($url, $headers, json_encode($data));
      }      
    }

    public function onGetReceipt( $from, $id, $offline, $retry )
    {
      l("Got receipt ".$id);
      
      $job = JobLog::find_by_whatsapp_message_id_and_account_id($id, $this->client->get_account_id());
      if ($job->method == "sendMessage") {        

        $message = Message::find_by_id($job->message_id);
        $message->received = true;
        $message->receipt_timestamp = date('Y-m-d H:i:s');
        $message->save();

        // pubnub message delivered
      } 

    }

    public function onConnect($mynumber, $socket) {
      l("Connected");
      $this->client->toggleConnection(true);
    }

    public function onDisconnect($mynumber, $socket)
    {
      l("Disconnected");
      $this->client->toggleConnection(false);
    }

    private function exists($id) {      
      return Message::exists(array('whatsapp_message_id' => $id));
    }
  }
