<?php
  require 'lib/whatsapp/events/AllEvents.php';

  class CokeEvents extends AllEvents
  {
    public $activeEvents = array(
      'onGetMessage',
      'onConnect',
      'onDisconnect',
    );

    public function onGetMessage( $me, $from, $id, $type, $time, $name, $body )
    {
      if (strlen($body) > 10) {
        $reply = "Why not try a nickname? It'll be cute 😄";
        $this->reply($from, $reply);
      }
    }

    public function onConnect($mynumber, $socket) {
      l("Connected");
    }

    public function onDisconnect($mynumber, $socket)
    {
      l("Disconnected");
    }

    private function reply($to, $message) {
      $this->whatsProt->sendMessage($from, $reply);
    }

  }