<?php
/**
 *
 *
 * @author Bad Wolf
 * @date 03.06.2018 12:46
 * @version 20190221
 */


$accessRules = ['groups' => [ROLE_ADMIN, ROLE_DIRECTOR]];

if ( !User::checkUserAccess( $accessRules ) )
{
    Core_Page_Show::instance()->error( 403 );
}


$breadcumbs[0] = new stdClass();
$breadcumbs[0]->title = $this->Structure->title();
$breadcumbs[0]->active = 1;

Core_Page_Show::instance()->setParam( 'body-class', 'body-orange' );
Core_Page_Show::instance()->setParam( 'title-first', 'СТАТИСТИКА' );
Core_Page_Show::instance()->setParam( 'title-second', '' );
Core_Page_Show::instance()->setParam( 'breadcumbs', $breadcumbs );

$action = Core_Array::Get( 'action', '', PARAM_STRING );

if ( $action === 'refresh' )
{
    Core_Page_Show::instance()->execute();
    exit;
}