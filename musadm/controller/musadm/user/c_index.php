<?php
/**
 * Created by PhpStorm.
 * User: Kopzurev Egor
 * Date: 11.04.2018
 * Time: 22:17
 */

$oProperty = Core::factory("Property");

$groupId = $this->oStructureItem->getId();
$groupId == 5
    ?   $xsl = "musadm/users/clients.xsl"
    :   $xsl = "musadm/users/teachers.xsl";

$aoUsers = Core::factory("User")
    ->where("group_id", "=", $groupId)
    ->where("active", "=", 1)
    ->orderBy("id", "DESC")
    ->findAll();

$oUserGroup = Core::factory("User_Group", $groupId);

foreach ($aoUsers as $user)
{
    $aoPropertiesList = $oProperty->getPropertiesList($oUserGroup);
    foreach ($aoPropertiesList as $prop)
    {
        $user->addEntities($prop->getPropertyValues($user), "property_value");
    }
}

$output = Core::factory("Core_Entity")
    ->xsl($xsl)
    ->addEntity(
        Core::factory("Core_Entity")
            ->name("table_type")
            ->value("active")
    )
    ->addEntities($aoUsers)
    ->show();