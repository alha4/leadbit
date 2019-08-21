<?php

namespace Rest;

use \Rest\Contracts\Entity,
    \Bitrix\Crm\ContactTable;

class Contact implements Entity {

  private static $error;

  public static function create(array &$data) : bool {

    [$name, $last_name] = explode(' ', $data['displayName']);

    $arFields = [

      'NAME' => $name,
      'LAST_NAME' => $last_name,
      'UF_CRM_1566286257' => $data['id'],
      'FM' => [
        'EMAIL' => ['n0' => ['VALUE' => $data['email'],  'VALUE_TYPE' => 'WORK' ]],
        'PHONE' => ['n0' => ['VALUE' => $data['phone'],  'VALUE_TYPE' => 'WORK' ]],
        'IM'   =>  [
                    'n0' => ['VALUE' => $data['skype'],    'VALUE_TYPE' => 'SKYPE'],
                    'n1' => ['VALUE' => $data['telegram'], 'VALUE_TYPE' => 'TELEGRAM'],
                    'n2' => ['VALUE' => $data['whatsapp'], 'VALUE_TYPE' => 'OTHER'],
                   ],
      ],

      'COMMENTS' => $data['adminComment']

    ];

    $contact = new \CCrmContact(false);

    if(!$contact->Add($arFields)) {

       self::$error = $contact->LAST_ERROR;

       return false;

    }

    return true;

  }

  public static function exists(...$params) : bool {

    return true;

  }

  public static function update(int $id, array &$data) : bool {

    return true;

  }


  public static function getErrors() {

     return self::$error;

  }


}