<?php

namespace Rest;

use \Bitrix\Main\Web\HttpClient as BitrixHttpClient;

final class HttpClient {

  private $http = null;

  private const ERROR_STATUS = "Ошибка ответа сервера: %s, заголовки: %s, Описание: %s";

  public function __construct() {

    $this->http = new BitrixHttpClient();
      
  }

  public function post(string $url, array $data, ?array $headers = []) : array { 

    $this->setHeaders($headers);

    $this->http->setHeader("Content-Type", "application/x-www-form-urlencoded");

    $response = $this->http->post($url, $data);

    if($this->http->getStatus() == 200) {

       return json_decode($response ,1);

    }

    throw new \Error(sprintf(
      self::ERROR_STATUS, 
      $this->http->getStatus(), 
      $this->http->getHeaders()->toString(),
      implode("\n",$this->http->getError()))
    );

  }

  public function get(string $url, ?array $data = [], ?array $headers = []) : array { 

    $this->setHeaders($headers);

    $response = $this->http->get($url, $data);

    if($this->http->getStatus() == 200 && $response['data']) {

       return json_decode($response ,1);

    }

    throw new \Error(sprintf(
      self::ERROR_STATUS, 
      $this->http->getStatus(), 
      $this->http->getHeaders()->toString(),
      implode("\n",$this->http->getError()))
    );

  }

  public function setToken(string $token) : void {

    $this->http->setHeader("Authorization","Bearer $token");

  }

  private function setHeaders(?array $headers) : void {

    if($headers) {

      foreach($headers as $name=>$value) {

        echo $name,' ',$value;
        $this->http->setHeader($name, $value);

      }

    }
  }
}