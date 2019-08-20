<?php

namespace Rest;

use \Bitrix\Main\Context;

final class Controller {

  private function resolve() : void {

    $request = Context::getCurrent()->getRequest()->get('route');

    switch($request) {






    }

  }
}

