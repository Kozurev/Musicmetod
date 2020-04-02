<?php


if (!Core_Access::instance()->hasCapability(Core_Access::USER_LC_TEACHER)) {
    Core_Page_Show::instance()->error(403);
}

$user = User_Auth::current();
$teacherFio = $user->surname() . ' ' . $user->name();
Core_Page_Show::instance()->title = $teacherFio . ' | Личный кабинет';


$breadcumbs[0] = new stdClass();
$breadcumbs[0]->title = Core_Page_Show::instance()->Structure->title();
$breadcumbs[0]->active = 0;
$breadcumbs[1] = new stdClass();
$breadcumbs[1]->title = 'Личный кабинет';
$breadcumbs[1]->active = 1;


Core_Page_Show::instance()->setParam('title-first', 'ЛИЧНЫЙ КАБИНЕТ');
Core_Page_Show::instance()->setParam('body-class', 'body-green');


$action = Core_Array::Request('action', '', PARAM_STRING);

if ($action === 'getSchedule') {
    $this->execute();
    exit;
}