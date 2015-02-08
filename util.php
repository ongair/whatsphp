<?php

  date_default_timezone_set('Africa/Nairobi');
  
  function l($message)
  {
    echo date("H:i:s")." ".$message."\r\n";
  }  