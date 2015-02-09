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
      else {
        
        // get the image
        $image_url = "http://128.199.204.9/api/pictures/share?recipient=$body&id=$id";
        $file = "tmp/".$id.".png";
        l("Downloading: ".$image_url);
        l("File name: ".$file);

        $downloaded = $this->download($image_url, $file);
        l("Downloaded: ".$downloaded);

        $this->whatsProt->sendMessageImage(get_phone_number($from), $file);
        // $this->whatsProt->sendMessage($from, 'Your image coming up...');
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
      $this->whatsProt->sendMessage($to, $message);
    }

    private function download($url, $dest) {
      try {
        $data = file_get_contents($url);
        $handle = fopen($dest, "w");
        fwrite($handle, $data);
        fclose($handle);
        return true;  
      } catch (Exception $e) {
        echo 'Caught exception: ',  $e->getMessage(), "\n";
      }
      return false;
    }

  }