<?php

namespace Rest;

use \Rest\Contracts\Entity,
    \Bitrix\Main\UserTable,
    \Bitrix\Main\Type\RandomSequence;

class Users implements Entity {

  private static $error = '';

  private const GROUP_ID = 12;

  private const DEPARTMENT = 56;

  public static function exists(...$params) : bool {

    [$email] = $params;

    $filter = ['EMAIL' => $email];

    return UserTable::getList(['filter' => $filter])->fetch();

  }

  public static function create(array &$data) : bool {

    $user = new \CUser();

    [$name, $last_name] = explode(' ', $data['name']);

    $pass = (new RandomSequence())->randString(10);

    $arFields = [

      'LOGIN' => $data['email'],
      'NAME' => $name,
      'LAST_NAME' => $last_name,
      'GROUP_ID'  => [self::GROUP_ID],
      'UF_DEPARTMENT' => [self::DEPARTMENT],
      'UF_ORIGIN_ID' => $data['id'],
      'EMAIL' => $data['email'],
      'PASSWORD' => $pass,
      'CONFIRM_PASSWORD' => $pass

    ];

    if($USER_ID = $user->Add($arFields)) {

      #$user->SendUserInfo($USER_ID, SITE_ID, '  ');

      return true;

    }

    self::$error = $data['id'].' '.$user->LAST_ERROR;

    return false;

  }

  public static function update(int $id, array &$data) : bool {


  }

  public static function getErrors() {

    return self::$error;

  }

}