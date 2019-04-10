<?php
/**
 * Файл обработчик контента разделов клиенты/штат
 *
 * @author Bad Wolf
 * @date 11.04.2018 22:17
 * @version 20190311
 * @version 20190405
 * @version 20190410
 */

Core::factory('User_Controller');
Core::factory('Schedule_Area_Controller');
$isDirector = intval(User::current()->groupId() == ROLE_DIRECTOR);
$groupId = Core_Page_Show::instance()->StructureItem->getId();

if ($groupId == ROLE_CLIENT) {
    $xsl = 'musadm/users/clients.xsl';
    $propertiesIds = [
        4,  //Примечание пользователя
        9,  //Ссылка вконтакте
        12, //Баланс
        13, //Кол-во индивидуальных занятий
        14, //Кол-во групповых занятий
        16, //Дополнительный телефон
        17, //Длительность занятия
        18, //Соглашение подписано
        19, //Примечание (статус)
        20, //Направление подготовки (инструмент)
        28  //Год рождения
    ];
} elseif ($groupId == ROLE_TEACHER) {
    $xsl = 'musadm/users/teachers.xsl';
    $propertiesIds = [
        20, //Инструмент
        28, //Год(дата) рождения
        31  //Расписание занятий
    ];
}

$ClientController = new User_Controller(User::current());
$ClientController
    ->properties($propertiesIds)
    ->tableType(User_Controller::TABLE_ACTIVE)
    ->groupId($groupId)
    ->isShowCount(true)
    ->addSimpleEntity('page-theme-color', 'primary')
    ->addSimpleEntity('is_director', $isDirector)
    ->xsl($xsl);
$ClientController->queryBuilder()->orderBy('id', 'DESC');
$ScheduleAssignment = Core::factory('Schedule_Area_Assignment');

foreach ($_GET as $paramName => $values) {
    if ($paramName === 'areas') {
        foreach ($_GET['areas'] as $areaId) {
            if ($areaId > 0
                && ($ScheduleAssignment->issetAssignment(User::current(), intval($areaId)) !== null)
                || User::checkUserAccess(['groups' => [ROLE_DIRECTOR]])
            ) {
                $Area = Schedule_Area_Controller::factory(intval($areaId));
                if ($Area !== null) {
                    $ClientController->forAreas([$Area]);
                }
            }
        }
        continue;
    }

    if (strpos($paramName, 'property_') !== false) {
        foreach ($_GET[$paramName] as $value) {
            $propId = explode('property_', $value)[0];
            $ClientController->appendFilter($paramName, $value);
        }
    }
}

$ClientController->show();

//Список менеджеров для директора
if ($groupId == ROLE_TEACHER && User::checkUserAccess(['groups' => [ROLE_DIRECTOR]])) {
    $TeacherController = new User_Controller(User::current());
    $TeacherController
        ->properties(true)
        ->groupId(2)
        ->addSimpleEntity('page-theme-color', 'primary')
        ->xsl('musadm/users/managers.xsl')
        ->show();
}