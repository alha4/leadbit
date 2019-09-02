<?php
namespace Rest;

use \Rest\Contracts\Entity,
    \Bitrix\Main\Loader;

class Contact implements Entity {

  private static $error;

  private const ROLES = [

    'ROLE_WM' => 'Вебмастер',
    'ROLE_ADVERT' => 'Рекламодатель',
    '' => ''

  ];

  private const LANGUAGES = [

    'en' => 'Английский',
    'ru' => 'Русский',
    'de' => 'Немецкий',
    '' => ''

  ];

  private const ACTIVESTATUS = [

    'unknown' => 'Неизвестен',
    'active' => 'Активный',
    'sleeping' => 'Спящий',
    'cold' => 'Холодный',
    'non_activated' => 'Неактивирован',
    'dead' => 'Мёртвый',
    '' => ''

  ];

  private const DEFAULT_USER = 6;

  private const DEFAULT_TEAMLEAD = 0;

  public static function create(array &$data) : bool {

    [$name, $last_name] = explode(' ', $data['displayName']);

    $role = implode(", ", $data['roles']); 

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
      'COMMENTS' => $data['adminComment'],
      'UF_CRM_1554398581' => enumID(self::LANGUAGES[$data['defaultLanguage']],'UF_CRM_1554398581','CRM_CONTACT'), //
      'UF_CRM_1566286583' => enumID($data['levelId'],'UF_CRM_1566286583','CRM_CONTACT'),
      'ASSIGNED_BY_ID'    => self::getOwner((int)$data['managerId']),  // ответственный
      'UF_CRM_1554398800' => self::getOwner((int)$data['loungeManagerId']), // доп. менеджер
      'UF_CRM_1566286821' => enumID($data['macroregion'],'UF_CRM_1566286821','CRM_CONTACT'),
      'UF_CRM_1566286956' => enumID(self::ACTIVESTATUS[$data['activityStatus']],'UF_CRM_1566286956','CRM_CONTACT'),
      'UF_CRM_1566287011' => date("d.m.Y", strtotime($data['createdAt'])),
      'UF_CRM_1554398918' => $data['moderated'], // апрув регистрации
      'UF_CRM_1554398931' => $data['isUnwanted'],
      'UF_CRM_1554398945' => $data['locked'],
      'UF_CRM_1566286483' => enumID(self::ROLES[$role],'UF_CRM_1566286483'),
      'UF_CRM_1566287339' => enumID($data['affiliateType'],'UF_CRM_1566287339'),
      'UF_CRM_1566287648' => implode(", ", $data['affiliateTypeCategory']),
      'UF_CRM_1554398975' => implode(", ", $data['affiliateTypeSource']),
      'UF_CRM_1566288628' => $data['affiliateTypeVertical'],
      'UF_CRM_1566805799550' => $data['teamLeadId'],  //поле Ид тимлида для временного хранения id тимлида из лидбита
      'UF_CRM_1566288708' => self::getTypeMacroregion($data['affiliateTypeMacroregion'])
  ];

  /*$logger = \Log\Logger::instance();
  $logger->setPath("/local/logs/Contacts.txt");
  $logger->info([$arFields]);*/

   $id = self::getById($data['id']);

   if(!$id) {

    $contact = new \CCrmContact(false);

    if(!$contact->Add($arFields)) {

     self::$error = $contact->LAST_ERROR;

     return false;

    }

    return true;

   } else {

     if(!self::update($id, $arFields)) {

       $logger = \Log\Logger::instance();
       $logger->setPath("/local/logs/Contacts.txt");
       $logger->info(['ошибка обновления',$id, $data, self::$error]);

       return false;

     }

     return true;
    
   }

  }

  private static function getTypeMacroregion(array &$data) : array {

    $enums = [];

    foreach($data as $value) {
    
      $enums[] = enumID($value,'UF_CRM_1566288708','CRM_CONTACT');

    }

    return $enums;

  }

  private static function getTeamLead(string $id) : int {

    return \CCrmContact::GetList(['UF_CRM_1566286257' => 'DESC'],['UF_CRM_1566286257' => $id])->Fetch()['UF_CRM_1566286257'] ? : self::DEFAULT_TEAMLEAD;

  }

  private static function getOwner(int $user_id) : int {
 
    $filter = ['UF_ORIGIN_ID' => $user_id];

    $user = \CUser::GetList($sort = 'id', $order = 'desc', $filter, ['SELECT' => ['UF_ORIGIN_ID']])->Fetch();
    
    if($user_id == 0 || !$user['UF_ORIGIN_ID']) {
      
       return self::DEFAULT_USER;

    }

    return $user['UF_ORIGIN_ID'];

  }

  private static function getMultiField($id, $type = 'EMAIL', $value_type = '') {

    $filter = ["ENTITY_ID"=>"CONTACT","TYPE_ID" => "$type", "ELEMENT_ID" => $id];

    if($value_type) {

      $filter['VALUE_TYPE'] = $value_type;

    }

    $rs = \CCrmFieldMulti::GetList(array(), $filter); 
 
    $fields = $rs->Fetch();
 
    return $fields['ID'] ? :  "n0";
 
  }

  public static function getById(...$params)  {

    [$id] = $params;

    $result = \CCrmContact::GetList(['UF_CRM_1566286257' => 'DESC'],['UF_CRM_1566286257' => $id])->Fetch();

    if($result['UF_CRM_1566286257']) {

       return $result['ID'];

    }

    return false;

  }


  public static function update(int $id, array &$data) : bool {

    $contact = new \CCrmContact(false);

    $data['FM']["EMAIL"][self::getMultiField($id, 'EMAIL')] = array("VALUE" => $data['FM']["EMAIL"]["n0"]["VALUE"], "VALUE_TYPE" => "WORK");
    $data['FM']["PHONE"][self::getMultiField($id, 'PHONE')] = array("VALUE" => $data['FM']["PHONE"]["n0"]["VALUE"], "VALUE_TYPE" => "WORK");
    
    $data['FM']["IM"][self::getMultiField($id, 'IM','SKYPE')]    = array("VALUE" => $data['FM']["IM"]["n0"]["VALUE"], "VALUE_TYPE" => "SKYPE");
    $data['FM']["IM"][self::getMultiField($id, 'IM','TELEGRAM')] = array("VALUE" => $data['FM']["IM"]["n1"]["VALUE"], "VALUE_TYPE" => "TELEGRAM");
    $data['FM']["IM"][self::getMultiField($id, 'IM','OTHER')]    = array("VALUE" => $data['FM']["IM"]["n2"]["VALUE"], "VALUE_TYPE" => "OTHER");

    if(!$contact->Update($id, $data)) {

      self::$error = $contact->LAST_ERROR;

      return false;

    }

    return true;

  }

  public static function getErrors() {

     return self::$error;

  }
}