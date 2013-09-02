<?php 
class Hull_Cache {
  
  function Hull_Cache($host="localhost", $port=11211, $expiration=120) {
    $this->client = new Memcached();
    $this->client->addServer($host, $port);
    $this->defaultExpiration = $expiration;
  }
  
  public function flush($delay=0) {
    $this->client->flush($delay);
  }
  
  public function get($key) {
    return $this->client->get($key);
  }
  
  public function set($key, $value, $expiration=-1) {
    if ($expiration == -1) {
      $expiration = $this->defaultExpiration;
    }
    $this->client->set($key, $value, $expiration);
  }
  
}
