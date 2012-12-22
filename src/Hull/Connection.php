<?php

  if (!function_exists('http_parse_headers')) {
    function http_parse_headers($header) {
      $retVal = array();
      $fields = explode("\r\n", preg_replace('/\x0D\x0A[\x09\x20]+/', ' ', $header));
      foreach( $fields as $field ) {
        if( preg_match('/([^:]+): (.+)/m', $field, $match) ) {
          $match[1] = preg_replace('/(?<=^|[\x09\x20\x2D])./e', 'strtoupper("\0")', strtolower(trim($match[1])));
          if( isset($retVal[$match[1]]) ) {
            $retVal[$match[1]] = array($retVal[$match[1]], $match[2]);
          } else {
            $retVal[$match[1]] = trim($match[2]);
          }
        }
      }
      return $retVal;
    }
  }

  class Hull_Connection {
    
    public  $host;
    public  $appId;
    public  $appSecret;
    public  $noHttpCache;
    private $cache;
    public  $debug;
    
    function Hull_Connection($config=array()) {

      $this->host        = $config['host'];
      
      $this->appId       = $config['appId'];
      $this->appSecret   = $config['appSecret'];

      if(isset($config['noHttpCache'])){
        $this->noHttpCache = $config['noHttpCache'];
      }

      if (isset($config['debug']) && $config['debug']==='true') {
        $this->debug_options = array("httpHits" => 0, "cacheHits" => 0, "cacheMisses" => 0);
      }
      if (isset($config['cache']) && $config['cache']==='true') {
        $this->cache = new Hull_Cache($config['cacheHost'], $config['cachePort'], $config['cacheExpiration']);
      }
    }
    
    
    public function flushCache($delay=0) {
      if ($this->cache) {
        $this->cache->flush($delay);
      }
      return true;
    }
    
    public function exec($type, $path, $params = array(), $headers = array()) {
      $params["format"] = "json";
      $headers[] = "User-Agent: HullPHPClient-" . Hull_Client::$version;
      $headers[] = "Content-Type: application/json";
      $headers[] = "Hull-App-Id: "  . $this->appId;
      $headers[] = "Hull-Acess-Token: " . $this->appSecret;
      
      $url = $this->host . "/api/v1/" . $path;
      
      if ($this->noHttpCache) {
        $headers[] = "Cache-Control: no-cache";
      }

      if ($this->cache && $type == "GET") {
        $res = $this->_cache_exec($url, $params, $headers);
      } else {
        $res = $this->_http_exec($type, $url, $params, $headers);
      }
      return (array)$res;
    }
    
    private function _cache_exec($url, $params, $headers) {
      $ident = $url . "?" . http_build_query($params) . "|" . implode(",", $headers);
      $ident = md5($ident);
      
      $res = $this->cache->get($ident);
      if ($res) {
        if ($this->debug_options) {
          $this->debug_options['cache_hits']++;
        }
        $res = (array)json_decode($res);
      } else {
        if ($this->debug_options) {
          $this->debug_options['cache_misses']++;
        }
        $res = $this->_http_exec("GET", $url, $params, $headers);
        $this->cache->set($ident, json_encode($res));
      }
      return $res;
    }
    
    private function _http_exec($type, $url, $params, $headers) {
      if (isset($this->debug_options)) {
        $this->debug_options['http_hits']++;
      }

      $s = curl_init();

      switch ($type) {
          case "GET":
            curl_setopt($s, CURLOPT_URL, $url . "?" . http_build_query($params));
            break;
          case "PUT":
            curl_setopt($s, CURLOPT_URL, $url);
            curl_setopt($s, CURLOPT_CUSTOMREQUEST, "PUT");
            curl_setopt($s, CURLOPT_POSTFIELDS, json_encode($params));
            break;            
          case "POST":
            curl_setopt($s, CURLOPT_URL, $url);
            curl_setopt($s, CURLOPT_POST, true);
            curl_setopt($s, CURLOPT_POSTFIELDS, json_encode($params));
            break;
      }

      curl_setopt($s, CURLOPT_HEADER, true);
      curl_setopt($s, CURLINFO_HEADER_OUT, 1);
      curl_setopt($s, CURLOPT_TIMEOUT, 60);
      curl_setopt($s, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($s, CURLOPT_HTTPHEADER, $headers);

      $out = curl_exec($s);
      $status = curl_getinfo($s, CURLINFO_HTTP_CODE);
      $response = curl_getinfo($s);
      curl_close($s);
      $response_headers = http_parse_headers(substr($out, 0, $response['header_size']));
      $response_body = substr($out, $response['header_size']);
      $body = json_decode($response_body);
      if (!$body) {
        $body = array();
      }

      $res = array("status" => $status, "body" => $body);

      if ($status >= 400) {
        throw new Exception(json_encode($res));
      } else {
        return $res;
      }
    }
    
  }

?>
