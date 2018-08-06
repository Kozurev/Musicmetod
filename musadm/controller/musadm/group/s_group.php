<?php
/**
 * Created by PhpStorm.
 * User: Egor
 * Date: 24.04.2018
 * Time: 19:46
 */

$oUser = Core::factory("User")->getCurrent();

$accessRules = array(
    "groups"    => array(1, 2)
);

if($oUser == false || !User::checkUserAccess($accessRules, $oUser))
{
    $this->error404();
//    $host  = $_SERVER['HTTP_HOST'];
//    $uri   = rtrim(dirname($_SERVER['PHP_SELF']), '/\\');
//    $extra = $_SERVER["REQUEST_URI"];
//    header("Location: http://$host$uri/authorize?back=$host$uri"."$extra");
    exit;
}


$breadcumbs[0] = new stdClass();
$breadcumbs[0]->title = "Список групп";
$breadcumbs[0]->active = 1;

$this->setParam( "body-class", "body-blue" );
$this->setParam( "title-first", "СПИСОК" );
$this->setParam( "title-second", "ГРУПП" );
$this->setParam( "breadcumbs", $breadcumbs );


$action = Core_Array::getValue($_GET, "action", null);


if($action == "refreshGroupTable")
{
    $this->execute();
    exit;
}

if($action == "updateForm")
{
    $popupData =    Core::factory("Core_Entity");
    $modelId =      Core_Array::getValue($_GET, "groupid", 0);

    if($modelId != 0)
    {
        $oGroup = Core::factory("Schedule_Group", $modelId);
        $oGroup->addEntity($oGroup->getTeacher());
        $oGroup->addEntities($oGroup->getClientList());
    }
    else
    {
        $oGroup = Core::factory("Schedule_Group");
    }

    $aoUsers = Core::factory("User")
        ->where("group_id", "=", 4)
        ->where("group_id", "=", 5, "or")
        ->where("active", "=", 1)
        ->orderBy("surname")
        ->findAll();

    $popupData
        ->addEntity($oGroup)
        ->addEntities($aoUsers)
        ->xsl("musadm/groups/edit_group_popup.xsl")
        ->show();

    exit;
}

if($action == "saveGroup")
{
    $modelId =      Core_Array::getValue($_GET, "id", 0);
    $teacherId =    Core_Array::getValue($_GET, "teacher_id", 0);
    $duration =     Core_Array::getValue($_GET, "duration", "00:00");
    $aClientIds =   Core_Array::getValue($_GET, "clients", null);
    $title =        Core_Array::getValue($_GET, "title", null);

    if($modelId != 0)
    {
        $oGroup = Core::factory("Schedule_Group", $modelId);
        $oGroup->clearClientList();
    }
    else
    {
        $oGroup = Core::factory("Schedule_Group");
    }

    $oGroup
        ->title($title)
        ->duration($duration)
        ->teacherId($teacherId);
    $oGroup->save();

    if(!is_null($aClientIds))
    foreach ($aClientIds as $clientid)  $oGroup->appendClient($clientid);
    exit;
}