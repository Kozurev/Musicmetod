<?php
/**
 * Обработчик для формирования контента раздела "Группы"
 *
 * @author BadWolf
 * @date 24.04.2018 19:46
 * @version 20190526
 */

global $CFG;
$User = User::current();
$subordinated = $User->getDirector()->getId();

$groups = Core::factory('Schedule_Group')
    ->queryBuilder()
    ->where('active', '=', 1)
    ->where('subordinated', '=', $subordinated)
    ->findAll();

$output = Core::factory('Core_Entity');
foreach ($groups as $group) {
    $group->addEntity($group->getTeacher());
    $group->addEntities($group->getClientList());
}

$accessCreate = Core_Access::instance()->hasCapability(Core_Access::SCHEDULE_GROUP_CREATE);
$accessEdit =   Core_Access::instance()->hasCapability(Core_Access::SCHEDULE_GROUP_EDIT);
$accessDelete = Core_Access::instance()->hasCapability(Core_Access::SCHEDULE_GROUP_DELETE);

$output
    ->addEntities($groups)
    ->addSimpleEntity('wwwroot', $CFG->rootdir)
    ->addSimpleEntity('access_group_create', (int)$accessCreate)
    ->addSimpleEntity('access_group_edit', (int)$accessEdit)
    ->addSimpleEntity('access_group_delete', (int)$accessDelete)
    ->xsl('musadm/groups/groups.xsl')
    ->show();