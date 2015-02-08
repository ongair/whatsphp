<?php
  require 'lib/whatsapp/events/AllEvents.php';

  class Events extends AllEvents
  {
    public $acount;
    public $activeEvents = array(
      'onConnect',
      'onDisconnect',
      'onGetMessage'
    );

    public function onGetMessage( $mynumber, $from, $id, $type, $time, $name, $body )
    {
      l("Message from $name: $body");
    }

    public function onConnect($mynumber, $socket) {
      l("Connected");
    }

    public function onDisconnect($mynumber, $socket)
    {
      l("Disconnected");
    }

    # public
  }
