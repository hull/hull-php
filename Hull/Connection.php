<?php

  if (!function_exists('http_parse_headers')) {
    function http_parse_headers($header) {
      $retVal = array();
      $fields = explode("\r\n", preg_replace('/\x0D\x0A[\x09\x20]+/', ' ', $header));
      foreach( $fields as $field ) {
        if( preg_match('/([^:]+): (.+)/m', $field, $match) ) {
          $match[1] = preg_replace_callback('/(?<=^|[\x09\x20\x2D])./',
            function($matches){
              return strtoupper($matches[0]);
            },
            strtolower(trim($match[1])));

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
    public  $userId;
    public  $appSecret;
    public  $accessToken;
    public  $noHttpCache;
    private $cache;
    public  $debug;
    public  $debug_options;
    private $preventSSLVerifyPeer = false; //Thanks for nothing, Windows.

    function Hull_Connection($config=array()) {

      $this->host        = $config['host'];

      $this->appId       = $config['appId'];
      $this->appSecret   = $config['appSecret'];

      if (isset($config['accessToken'])) {
        $this->accessToken = $config['accessToken'];
      } else {
        $this->accessToken = $this->appSecret;
      }

      if (isset($config['userId'])) {
        $this->userId      = $config['userId'];
      }

      if(isset($config['noHttpCache'])){
        $this->noHttpCache = $config['noHttpCache'];
      }

      if (isset($config['debug']) && $config['debug']) {
        $this->debug_options = array("httpHits" => 0, "cacheHits" => 0, "cacheMisses" => 0);
      }
      if (isset($config['cache']) && $config['cache']) {
        $cacheConfig = array('cacheHost' => 'localhost', 'cachePort' => 11211, 'cacheExpiration' => 120);
        foreach ($cacheConfig as $k => $v) {
          if (isset($config[$k])) {
            $cacheConfig[$k] = $v;
          }
        }
        $this->cache = new Hull_Cache($cacheConfig['cacheHost'], $cacheConfig['cachePort'], $cacheConfig['cacheExpiration']);
      }

      if (isset($config['debug']) && $config['debug'] && strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') { //Running under windows in debug mode
        $this->preventSSLVerifyPeer = true;
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
      $headers[] = "Hull-Access-Token: " . $this->accessToken;
      if ($this->userId) {
        $headers[] = "Hull-User-Id: " . $this->userId;
      }

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
          $this->debug_options['cacheHits']++;
        }
        $res = (array)json_decode($res);
      } else {
        if ($this->debug_options) {
          $this->debug_options['cacheMisses']++;
        }
        $res = $this->_http_exec("GET", $url, $params, $headers);
        $this->cache->set($ident, json_encode($res));
      }
      return $res;
    }

    private function _http_exec($type, $url, $params, $headers) {
      if (isset($this->debug_options)) {
        $this->debug_options['httpHits']++;
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
          case "DELETE":
            curl_setopt($s, CURLOPT_URL, $url);
            curl_setopt($s, CURLOPT_CUSTOMREQUEST, "DELETE");
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

      if ($this->preventSSLVerifyPeer) {
        echo "Warning: SSL is not verified in debug mode under Windows.";
        curl_setopt($s, CURLOPT_SSL_VERIFYPEER, false);
      }

      $out = curl_exec($s);
      if ($out === false) {
        $error = curl_error($s);
        curl_close($s);
        throw new Exception("cURL Error while fetching Hull data: " . $error);
      }
      $status = curl_getinfo($s, CURLINFO_HTTP_CODE);
      $response = curl_getinfo($s);
      curl_close($s);
      $response_headers = http_parse_headers(substr($out, 0, $response['header_size']));
      $response_body = substr($out, $response['header_size']);
      $body = json_decode($response_body);
      if (!$body) {
        $body = array();
      }

      $res = array("status" => $status, "body" => $body, "headers" => $response_headers);

      if ($status >= 400) {
        throw new Exception(json_encode($res));
      } else {
        return $res;
      }
    }

  }

?>
