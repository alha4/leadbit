<?php

namespace Rest\Contracts;

interface Entity {

  public static function exists(...$params) : bool;

  public static function update(int $id, array &$data) : bool;

  public static function create(array &$data) : bool; 

  public static function getErrors();

}