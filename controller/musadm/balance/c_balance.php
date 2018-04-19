<?php
/**
 * Created by PhpStorm.
 * User: Kozurev Egor
 * Date: 19.04.2018
 * Time: 23:18
 */

$oCurentUser = Core::factory("User")->getCurent();
$pageUserId = Core_Array::getValue($_GET, "userid", 0);
$oCurenUserGroup = Core::factory("User_Group", $oCurentUser->groupId());

if($pageUserId == 0)
{
    $oUser = $oCurentUser;
}
else
{
    $oUser = Core::factory("User", $pageUserId);
}

$oPropertyBalance           =   Core::factory("Property", 12);
$oPropertyPrivateLessons    =   Core::factory("Property", 13);
$oPropertyGroupLessons      =   Core::factory("Property", 14);

$balance        =   $oPropertyBalance->getPropertyValues($oUser)[0];
$privateLessons =   $oPropertyPrivateLessons->getPropertyValues($oUser)[0];
$groupLessons   =   $oPropertyGroupLessons->getPropertyValues($oUser)[0];

Core::factory("Core_Entity")
    ->addEntity($oUser)
    ->addEntity($oCurenUserGroup)
    ->addEntity($balance, "property")
    ->addEntity($privateLessons, "property")
    ->addEntity($groupLessons, "property")
    ->xsl("musadm/balance/balance.xsl")
    ->show();