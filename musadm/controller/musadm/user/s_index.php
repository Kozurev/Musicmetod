<?php
/**
 * Created by PhpStorm.
 * User: Kozurev Egor
 * Date: 11.04.2018
 * Time: 22:16
 */

if( $this->oStructureItem->getId() == 5 )
{
    $title2 = "КЛИЕНТОВ";
    $breadcumb = "клиентов";
}
else 
{
    $title2 = "ПРЕПОДАВАТЕЛЕЙ";
    $breadcumb = "преподавателей";
}

$breadcumbs[0] = new stdClass();
$breadcumbs[0]->title = "Список " . $breadcumb;
$breadcumbs[0]->active = 1;

$this->setParam( "body-class", "body-primary" );
$this->setParam( "title-first", "СПИСОК" );
$this->setParam( "title-second", $title2 );
$this->setParam( "breadcumbs", $breadcumbs );


/*
*	Блок проверки авторизации
*/
$oUser = Core::factory("User")->getCurrent();

$accessRules = array(
    "groups"    => array(1, 2)
);


$action = Core_Array::getValue($_GET, "action", null);

/**
 * Форма редактирования клиента
 */
if($action == "updateFormClient")
{
    $userid =           Core_Array::getValue($_GET, "userid", 0);
    $output = Core::factory("Core_Entity");

    if($userid)
    {
        $oUser =            Core::factory("User", $userid);
        $aoProperties[] =   Core::factory("Property", 16)->getPropertyValues($oUser)[0];    //Доп. телефон
        $aoProperties[] =   Core::factory("Property", 9)->getPropertyValues($oUser)[0];     //Ссылка вк
        $aoProperties[] =   Core::factory("Property", 17)->getPropertyValues($oUser)[0];    //Длительность урока
        $aoProperties[] =   Core::factory("Property", 15)->getPropertyValues($oUser)[0];    //Студия
        $aoProperties[] =   Core::factory("Property", 18)->getPropertyValues($oUser)[0];    //Соглашение подписано
        $aoProperties =   array_merge($aoProperties, Core::factory("Property", 21)->getPropertyValues($oUser));   //Учителя
    }
    else
    {
        $oUser = Core::factory("User");
        $aoProperties[] =   Core::factory("Property_Int")
            ->value(Core::factory("Property", 17)->defaultValue());
    }

    $aoPropertyLists = Core::factory("Property_List_Values")
        ->where("property_id", "=", 21)
        ->orderBy("sorting")
        ->findAll();

    $aoPropertyLists = array_merge($aoPropertyLists,
        Core::factory("Property_List_Values")
            ->where("property_id", "=", 15)
            ->orderBy("sorting")
            ->findAll()
    );

    $output
        ->addEntity($oUser)
        ->addEntities($aoProperties,    "property_value")
        ->addEntities($aoPropertyLists, "property_list")
        ->xsl("musadm/users/edit_client_popup.xsl")
        ->show();

    exit;
}


/**
 * Форма редактирования учителя
 */
if($action == "updateFormTeacher")
{
    $userid =           Core_Array::getValue($_GET, "userid", 0);
    $output = Core::factory("Core_Entity");

    if($userid)
    {
        $oUser =            Core::factory("User", $userid);
        $aoProperties[] =   Core::factory("Property", 20)->getPropertyValues($oUser)[0];    //Инструмент

        $output
            ->addEntities($aoProperties,    "property_value");
    }
    else
    {
        $oUser = Core::factory("User");
    }

    $aoPropertyLists =  Core::factory("Property_List_Values")
        ->where("property_id", "=", 20)
        ->orderBy("sorting")
        ->findAll();

    $output
        ->addEntity($oUser)
        ->addEntities($aoPropertyLists, "property_list")
        ->xsl("musadm/users/edit_teacher_popup.xsl")
        ->show();

    exit;
}


/**
 * Обновление таблиц
 */
if($action == "refreshTableUsers")
{
    $this->execute();
    exit;
}


if($action == "getPaymentPopup")
{
    $userId =   Core_Array::getValue($_GET, "userid", 0);
    $oUser =    Core::factory("User", $userId);

    Core::factory("Core_Entity")
        ->addEntity($oUser)
        ->xsl("musadm/users/edit_payment_popup.xsl")
        ->show();

    exit;
}


if($action == "savePayment")
{
    $userid =       Core_Array::getValue($_GET, "userid", 0);
    $value  =       Core_Array::getValue($_GET, "value", 0);
    $description =  Core_Array::getValue($_GET, "description", "");
    $type =         Core_Array::getValue($_GET, "type", 0);

    $payment = Core::factory("Payment")
        ->user($userid)
        ->type($type)
        ->value($value)
        ->description($description)
        ->save();

    /**
     * Корректировка баланса ученика
     */
    $oUser =        Core::factory("User", $userid);
    $oUserBalance = Core::factory("Property", 12);
    $oUserBalance = $oUserBalance->getPropertyValues($oUser)[0];
    $balanceOld =   intval($oUserBalance->value());

    $type == 1
        ?   $balanceNew =   $balanceOld + intval($value)
        :   $balanceNew =   $balanceOld - intval($value);
    $oUserBalance->value($balanceNew);
    $oUserBalance->save();

    echo 0;
    exit;
}


if( $action == "checkLoginExists" )
{
    $userid = Core_Array::getValue( $_GET, "userid", 0 );
    $login = Core_Array::getValue( $_GET, "login", "" );

    if( $login == "" )  die("Логин не может быть пустым");

    $oUser = Core::factory( "User" )
        ->where( "id", "<>", $userid )
        ->where( "login", "=", $login )
        ->find();

    if( $oUser != false )   die("Пользователь с таким логином уже существует");

    exit;
}



$aTitle[] = $this->oStructure->title();

if(get_class($this->oStructureItem) == "User_Group")
    $aTitle[] = $this->oStructureItem->title();

if(get_class($this->oStructureItem) == "User")
    $aTitle[] = $this->oStructureItem->surname() . " " . $this->oStructureItem->name();

$this->title = array_pop($aTitle);