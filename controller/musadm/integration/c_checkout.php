<?php

use Model\Checkout\Helper;
use Model\Checkout\Model;

$models = Model::query()
    ->orderBy('id', 'desc')
    ->get();
$stdModels = $models->map(function(Model $checkoutModel) : stdClass {
    return $checkoutModel->toStd();
})->toArray();

(new Core_Entity())
    ->addEntities($stdModels, 'checkouts')
    ->addEntities(Helper::getCheckoutTypesListStd(), 'types')
    ->xsl('musadm/integration/checkouts/index.xsl')
    ->show();