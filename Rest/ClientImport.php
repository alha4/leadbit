<?php

namespace Rest;

use \Rest\{HttpClient,Users,Contact};

class ClientImport {

  private static $instance = null;

  private $token = '';

  private const STEP_OFFSET = 100;

  private const LOGIN_URL = 'http://wapi.dev.leadbit.com/api/v2/login_check';

  private const USERS_URL = "http://wapi.dev.leadbit.com/api/v2/users?offset=%s&limit=10";

  private const MANAGERS_URL = 'http://wapi.dev.leadbit.com/api/v2/managers';

  private const REFRESH_TOKEN_URL = 'http://wapi.dev.leadbit.com/api/v2/token/refresh';

  private const ERROR_TAKE_TOKEN = 'Ошибка получения токена авторизации';

  public static function getInstance() : ClientImport {

    if(!self::$instance) {

      self::$instance = new ClientImport();
      self::$instance->login();

    }

    return self::$instance;

  }

  public function run() : void {

     static $page;
     
     $data = $this->getContacts($page)['data'];

     foreach($data as $contact) {

       if(!Contact::create($contact)) {

         echo Contact::getErrors(),'<br>';

       }

     }   
   
     /*

     $meta = $data['meta'];

     $total = (int)$meta['total'] - self::STEP_OFFSET;

     $current = (int)$meta['offset'] + (int)$meta['limit'];

     $last = $total - $current;

     if($page < $total) {

       echo $total,' ', $last,' ',$page,'<br><pre>',print_r($data['data']);

       ob_end_flush();
       
       $page+=self::STEP_OFFSET;

       ob_flush();
       flush();
       sleep(1);

       #$this->run();

     }*/

  }

  private function importUsers() {

    $managers = $this->getManagers();

     #file_put_contents($_SERVER['DOCUMENT_ROOT'].'/local/logs/rest.txt', print_r($managers,1));

     /*print_r($managers);

     exit;*/

     foreach($managers as $data) {

       if(!Users::create($data)) {

          echo Users::getErrors();
 
       }

     }
  }

  private function getManagers() {

    return $this->httpClient()->get(self::MANAGERS_URL)['data'];

  }

  private function getContacts(?int $page = 0)  {

    return $this->httpClient()->get(sprintf(self::USERS_URL, $page));

  }

  private function login() : void {

    $response = $this->httpClient()->post(self::LOGIN_URL, [
      
        '_username' => \AUTH_LOGIN,
        '_password' => \AUTH_PASSWORD
      
      ]
    );

    if(!$response['token']) {

      throw new \Error(self::ERROR_TAKE_TOKEN);

    }

    $this->token = $response['token'];
    
  }

  private function httpClient() : HttpClient {

    $http = new HttpClient();
    $http->setToken($this->token);

    return $http;

  }

  private function __construct() {}
  private function __clone() {}

}