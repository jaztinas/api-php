<?php
namespace Bookboon\Api;
/*
 *  Copyright 2014 Bookboon.com Ltd.
 *
 *  Licensed under the Apache License, Version 2.0 (the "License");
 *  you may not use this file except in compliance with the License.
 *  You may obtain a copy of the License at
 *
 *      http://www.apache.org/licenses/LICENSE-2.0
 *
 *  Unless required by applicable law or agreed to in writing, software
 *  distributed under the License is distributed on an "AS IS" BASIS,
 *  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 *  See the License for the specific language governing permissions and
 *  limitations under the License.
 * 
 */

use Exception;

if (!function_exists('curl_init')) {
   throw new Exception('Bookboon requires the curl PHP extension');
}
if (!function_exists('json_decode')) {
   throw new Exception('Bookboon requires the json PHP extension');
}

class Bookboon {

   const HEADER_BRANDING = 'X-Bookboon-Branding';
   const HEADER_ROTATION = 'X-Bookboon-Rotation';
   const HEADER_PREMIUM = 'X-Bookboon-PremiumLevel';
   const HEADER_CURRENCY = 'X-Bookboon-Currency';
   const HEADER_LANGUAGE = 'Accept-Language';
   const HEADER_XFF = 'X-Forwarded-For';


   private $authenticated = array();
   private $headers = array();
   private $url = "bookboon.com/api";
   private $cache = null;

   public static $CURL_REQUESTS = array();
   
   public static $CURL_OPTS = array(
       CURLOPT_CONNECTTIMEOUT => 10,
       CURLOPT_RETURNTRANSFER => true,
       CURLOPT_HEADER => true,
       CURLOPT_TIMEOUT => 60,
       CURLOPT_USERAGENT => 'bookboon-php-2.1',
       CURLOPT_SSL_VERIFYPEER => true,
       CURLOPT_SSL_VERIFYHOST => 2
   );

   function __construct($appid, $appkey, $headers = array()) {
      if (empty($appid) || empty($appkey)) {
          throw new Exception('Invalid appid or appkey');
      }
      
      $this->authenticated['appid'] = $appid;
      $this->authenticated['appkey'] = $appkey;
      $this->headers = array(self::HEADER_XFF => $this->getRemoteAddress());
   }

   public function setCache(Cache $cache) {
      $this->cache = $cache;
   }

   public function setHeader($header, $value) {
      $this->headers[$header] = $value;
   }

   private function getHeaders()
   {
      $headers = array();
      foreach ($this->headers as $h => $v) {
         $headers[] = $h . ': ' . $v;
      }

      return $headers;
   }

   /**
    * Prepares the call to the api and if enabled tries cache provider first for GET calls
    * 
    * @param string $relative_url The url relative to the address. Must begin with '/'
    * @param array $method_vars must contain subarray called either 'post' or 'get' depend on HTTP method
    * @param boolean $cache_query manually disable object cache for query
    * @return array results of call
    */
   public function api($relative_url, $method_vars = array(), $cache_query = true) {
      
      $result = array();
      $queryUrl = $this->url . $relative_url;
      
      if (!substr($relative_url, 1, 1) == '/') {
         throw new Exception('Location must begin with forward slash');
      }

      if (isset($method_vars['get']) || empty($method_vars)) {
         $queryUrl = $this->url . $relative_url;
         if (!empty($method_vars)) {
             $queryUrl .= "?" . http_build_query($method_vars['get']);
         }
       
         /* Use cache if provider succesfully initialized and only GET calls */
         if (is_object($this->cache) && count($method_vars) <= 1 && $cache_query) {
            $hashkey = sha1( $this->authenticated['appid'] . serialize($this->headers) . $queryUrl);
            $result = $this->cache->get($hashkey);
            if ($result === false) {
               $result = $this->query($queryUrl, $method_vars);
               $this->cache->save($hashkey, $result);
            } else {
               $this->reportDeveloperInfo(array(
                   "total_time" => 0,
                   "http_code" => 'memcache',
                   "size_download" => mb_strlen(json_encode($result)),
                   "url" => "https://" . $queryUrl
               ), array());
            }
            return $result;
         }
      }
      
      return $result = $this->query($queryUrl, $method_vars);
   }
   
   /**
    * Makes the actual query call to the remote api.
    * 
    * @param string $relative_url The url relative to the address. Must begin with '/'
    * @param array $vars must contain subarray called either 'post' or 'get' depend on HTTP method
    * @return array results of call
    */
   private function query($url, $vars = array()) {

      $http = curl_init();

      curl_setopt($http, CURLOPT_URL, "https://" . $url);
      curl_setopt($http, CURLOPT_USERPWD, $this->authenticated['appid'] . ":" . $this->authenticated['appkey']);
      curl_setopt($http, CURLOPT_HTTPHEADER, $this->getHeaders());

      if (isset($vars['post'])) {
         curl_setopt($http, CURLOPT_POST, count($vars['post']));
         curl_setopt($http, CURLOPT_POSTFIELDS, http_build_query($vars['post']));
      }

      foreach (self::$CURL_OPTS as $key => $val) {
         curl_setopt($http, $key, $val);
      }
      $response = curl_exec($http);

      $header_size = curl_getinfo($http, CURLINFO_HEADER_SIZE);
      $header = substr($response, 0, $header_size);
      $body = json_decode(substr($response, $header_size), true);

      $http_status = curl_getinfo($http, CURLINFO_HTTP_CODE);

      $this->reportDeveloperInfo(curl_getinfo($http), isset($vars['post']) ? $vars['post'] : array());

      curl_close($http);

      if ($http_status >= 400) {
         switch ($http_status) {
            case 400:
                 throw new ApiSyntaxException($body['message']);
            case 401:
            case 403:
               throw new AuthenticationException("Invalid credentials");
            case 404:
               throw new NotFoundException($url);
               break;
            default:
               throw new GeneralApiException($body['message']);
         }
      }
      
      if ($http_status >= 301 && $http_status <= 303) {
          $body['url'] = '';
          foreach (explode("\n", $header) as $h) {
              if (strpos($h, "Location") === 0) {
                  $body['url'] = trim(str_replace("Location: ", "", $h));
              }
          }
      }
      
      return $body;
   }

   /**
    * Useful GUID validator to validate input in scripts
    * 
    * @param string $guid GUID to validate
    * @return boolean true if valid, false if not
    */
   public static function isValidGUID($guid) {
      return preg_match("/^([0-9a-fA-F]){8}-([0-9a-fA-F]){4}-([0-9a-fA-F]){4}-([0-9a-fA-F]){4}-([0-9a-fA-F]){12}$/", $guid);
   }

   /**
    * Returns the remote address either directly or if set XFF header value
    * 
    * @return string The ip address
    */
   private function getRemoteAddress() {
      $hostname = filter_input(INPUT_SERVER, 'REMOTE_ADDR', FILTER_VALIDATE_IP);

      if (function_exists('apache_request_headers')) {
         $headers = apache_request_headers();
         foreach ($headers as $k => $v) {
            if (strcasecmp($k, "x-forwarded-for"))
               continue;

            $hostname = explode(",", $v);
            $hostname = trim($hostname[0]);
            break;
         }
      }

      return $hostname;
   }

   private function reportDeveloperInfo($request, $data) {
      self::$CURL_REQUESTS[] = array(
          "curl" => $request,
          "data" => $data
      );
   }

}

?>
