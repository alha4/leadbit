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
      'UF_CRM_1554398581' => enumID(self::LANGUAGES[$data['defaultLanguage']],'UF_CRM_1554398581'),
      'UF_CRM_1566286583' => enumID($data['levelId'],'UF_CRM_1566286583'),
      'ASSIGNED_BY_ID'    => self::getOwner((int)$data['managerId']),  // ответственный
      'UF_CRM_1554398800' => self::getOwner((int)$data['loungeManagerId']), // доп. менеджер
      'UF_CRM_1566286821' => enumID($data['macroregion'],'UF_CRM_1566286821'),
      'UF_CRM_1566286956' => enumID(self::ACTIVESTATUS[$data['activityStatus']],'UF_CRM_1566286956'),
      'UF_CRM_1566287011' => date("d.m.Y", strtotime($data['createdAt'])),
      'UF_CRM_1554398918' => $data['moderated'], // апрув регистрации
      'UF_CRM_1554398931' => $data['isUnwanted'],
      'UF_CRM_1554398945' => $data['locked'],
      'UF_CRM_1566286483' => enumID(self::ROLES[$role],'UF_CRM_1566286483'),
      'UF_CRM_1566309162' => self::getTeamLead($data['teamLeadId']), // тимлид
      'UF_CRM_1566287339' => enumID($data['affiliateType'],'UF_CRM_1566287339'),
      'UF_CRM_1566287648' => implode(", ", $data['affiliateTypeCategory']),
      'UF_CRM_1554398975' => implode(", ", $data['affiliateTypeSource']),
      'UF_CRM_1566288628' => $data['affiliateTypeVertical'],
      'UF_CRM_1566288708' => self::getTypeMacroregion($data['affiliateTypeMacroregion'])//enumID работает со перечнем значений?
  ];


  $logger = \Log\Logger::instance();
  $logger->setPath("/local/logs/Contacts.txt");
  $logger->info([$arFields]);


  //[roles] => Array ( [0] => ROLE_WM ) [teamLeadId] => [affiliateType] => Media Buyer [affiliateTypeCategory] => Array ( [0] => Adult [1] => WhiteHat [2] => Health [3] => Beauty [4] => Weightloss ) [affiliateTypeSource] => Array ( [0] => Adult Ad Networks [1] => Native Ad Networks ) [affiliateTypeVertical] => Array ( ) [affiliateTypeMacroregion] => Array ( [0] => Europe [1] => CIS )

   $id = self::getById($data['id']);

   if(!$id) {

    $contact = new \CCrmContact(false);

    if(!$contact->Add($arFields)) {

     self::$error = $contact->LAST_ERROR;

     return false;

    }

    return true;

   } else {
    
     if(!self::update($id, $data)) {

       return true;

     }

     return true;
    
   }

  }

  private static function getTypeMacroregion(array &$data) : array {

    $enums = [];

    foreach($data as $value) {
    
      $enums[] = enumID($value,'UF_CRM_1566288708');

    }

    return $enums;

  }

  private static function getTeamLead(string $id) : int {

    return \CCrmContact::GetList(['UF_CRM_1566286257' => 'DESC'],['UF_CRM_1566286257' => $id])->Fetch()['UF_CRM_1566286257'] ? : self::DEFAULT_TEAMLEAD;

  }

  private static function getOwner(int $user_id) : int {
 
    $filter = ['UF_ORIGIN_ID' => $user_id];

    $user = \CUser::GetList($sort = 'id', $order = 'desc', $filter, ['SELECT' => ['UF_ORIGIN_ID']])->Fetch();
    
    /*$logger = \Log\Logger::instance();
    $logger->setPath("/local/logs/Contacts.txt");
    $logger->info([$user_id]);*/
    
    if($user_id == 0 || !$user['UF_ORIGIN_ID']) {
      
       return self::DEFAULT_USER;

    }

    return $user['UF_ORIGIN_ID'];

  }

  public static function getById(...$params)  {

    [$id] = $params;

    $result = \CCrmContact::GetList(['UF_CRM_1566286257' => 'DESC'],['UF_CRM_1566286257' => $id])->Fetch();

    if($result['UF_CRM_1566286257']) {

       return $result['UF_CRM_1566286257'];

    }

    return false;

  }


  public static function update(int $id, array &$data) : bool {

    $contact = new \CCrmContact(false);
    
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