<?php
  
  // Ongair Exception base class
  class OngairException extends Exception {

    const INACTIVE_ACCOUNT = 0;
    const CONNECTION_ERROR = 0;
    const BLOCKED_ERROR = 1;

    public function __construct($message, $code, $previous = null) {
      parent::__construct($message, $code, $previous);
    }

    public function exitCode() {
      return $this->code;
    }

    public function canRestart() {
      return $this->code == 0 ? 'Yes' : 'No';
    }
  }

  // Exception when an inactive account is run
  class InactiveAccountException extends OngairException {

    public function __construct($account, Exception $previous = null) {
      $message = "The account $account is not active";
      parent::__construct($message, OngairException::INACTIVE_ACCOUNT, $previous);
    }
  }

  // Connection exception
  class OngairConnectionException extends OngairException {
    public function __construct($account, $message, Exception $previous) {
      parent::__construct($message, OngairException::CONNECTION_ERROR, $previous);
    }
  }

  // Blocked account exception
  class BlockedException extends OngairException {
    public function __construct($account) {
      parent::__construct("$account is blocked", OngairException::BLOCKED_ERROR);
    }
  }