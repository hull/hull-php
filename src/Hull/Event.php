<?php 

class Hull_Event {

  public $payload;
  public $valid;
  public $nonce;
  public $timestamp;
  public $error;

  function Hull_Event($secret) {
    try {
      $appId      = $_SERVER['HULL_APP_ID'];
      $sig        = explode('.', $_SERVER['HULL_SIGNATURE']);
      $this->timestamp  = $sig[0];
      $this->nonce      = $sig[1];
      $hash             = $sig[2];
      $this->payload    = json_decode(file_get_contents("php://input"));
      $data = implode("-", array($this->timestamp, $this->nonce, $this->payload));
      $this->valid      = $signature == hash_hmac("sha1", $data, $secret)
    } catch (Exception $e) {
      $this->error      = $e;
      $this->valid      = false;
    }
  }
}