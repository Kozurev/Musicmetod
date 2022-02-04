<?php

use Model\Checkout\Checkout;
use Model\Checkout\Model;

global $CFG;
authOrOut();

$accessIntegrationCheckouts = User_Auth::current()->isDirector();
if (!$accessIntegrationCheckouts) {
    Core_Page_Show::instance()->error(403);
}

$breadcumbs = [];
$breadcumbs[0] = new stdClass();
$breadcumbs[0]->title = Core_Page_Show::instance()->Structure->getParent()->title();
$breadcumbs[0]->href = $CFG->rootdir . '/' . Core_Page_Show::instance()->Structure->getParent()->path();
$breadcumbs[1] = new stdClass();
$breadcumbs[1]->title = Core_Page_Show::instance()->Structure->title();
$breadcumbs[1]->active = 1;

Core_Page_Show::instance()->setParam('body-class', 'body-green');
Core_Page_Show::instance()->setParam('title-first', 'ИНТЕГРАЦИЯ');
Core_Page_Show::instance()->setParam('title-second', 'ОНЛАЙН КАССЫ');
Core_Page_Show::instance()->setParam('breadcumbs', $breadcumbs);

$action = Core_Array::Request('action', '', PARAM_STRING);

//Обработчик обновления страницы
if ($action === 'refreshPage') {
    Core_Page_Show::instance()->execute();
    exit;
}

if ($action === 'getCheckoutModal') {
    if (!User_Auth::current()->isDirector()) {
        Core_Page_Show::instance()->error(404);
    }
    $id = Core_Array::Get('id', null, PARAM_INT);
    $checkoutModel = !empty($id)
        ?   Model::find($id)
        :   new Model();

    if (!empty($id) && !is_null($checkoutModel)) {
        try {
            $checkout = new Checkout($checkoutModel);
        } catch (\Throwable $throwable) {

        }

    }

    exit;
}