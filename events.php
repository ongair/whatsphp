<?php
  require 'lib/whatsapp/events/AllEvents.php';
  require 'models/Message.php';  

  class Events extends AllEvents
  {
    public $acount;
    public $activeEvents = array(
      'onConnect',
      'onDisconnect',
      'onGetMessage'
    );

    public function onGetMessage( $me, $from, $id, $type, $time, $name, $body )
    {
      l("Message from $name: $body");

      # check if the message exists in the db
      if (!$this->exists($id)) {
                

        $url = 'http://0.0.0.0:3000/messages';
        $data = array('account' => $me, 'message' => array( 'text' => $body, 'phone_number' => get_phone_number($from), 'message_type' => 'Text', 'whatsapp_message_id' => $id, 'name' => $name) );
        
        $headers = array('Content-Type' => 'application/json', 'Accept' => 'application/json');
        Requests::post($url, $headers, json_encode($data));
      }      
    }

    public function onConnect($mynumber, $socket) {
      l("Connected");
    }

    public function onDisconnect($mynumber, $socket)
    {
      l("Disconnected");
    }

    private function exists($id) {      
      return Message::exists(array('whatsapp_message_id' => $id));
    }
  }
