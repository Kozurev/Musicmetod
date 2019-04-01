<?php
/**
 * Файл формирующий контент раздела лидов
 *
 * @author BadWolf
 * @date 16.05.2018 17:07
 * @version 20190401
 */

$today = date('Y-m-d');
$from = Core_Array::Get('date_from', null, PARAM_STRING);
$to =   Core_Array::Get('date_to', null, PARAM_STRING);

$Director = User::current()->getDirector();
$subordinated = $Director->getId();

Core::factory('Task_Controller');
$TaskController = new Task_Controller(User::current());
$TaskController
    ->periodFrom($from)
    ->periodTo($to)
    ->isShowPeriods(true)
    ->isSubordinate(true)
    ->isLimitedAreasAccess(true)
    ->addSimpleEntity('taskAfterAction', 'tasks')
    ->show();