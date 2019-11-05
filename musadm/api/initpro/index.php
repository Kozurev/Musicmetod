<?php
/**
 * Created by PhpStorm.
 * User: Egor
 * Date: 02.09.2019
 * Time: 13:27
 */

Core::requireClass('Rest_Initpro');
Core::requireClass('Payment');
Core::requireClass('Property_Controller');

$action = Core_Array::Request('action', null, PARAM_STRING);


if ($action === 'sendCheck') {
    $paymentId =    Core_Array::Post('paymentId', 0, PARAM_INT);
    $userId =       Core_Array::Post('userId', 0, PARAM_INT);
    $userEmail =    Core_Array::Post('userEmail', '', PARAM_STRING);
    $description =  Core_Array::Post('description', '', PARAM_STRING);
    $sum =          Core_Array::Post('sum', 0.0, PARAM_FLOAT);

    $isAuth = Rest_Initpro::makeAuth();
    if (!$isAuth) {
        exit(json_encode(['error' => Rest_Initpro::$authError]));
    }

    $checkInfo = new stdClass();
    $checkInfo->id = $paymentId;
    $checkInfo->client = new stdClass();
    $checkInfo->client->email = $userEmail;
    $checkInfo->description = $description;
    $checkInfo->sum = $sum;
    $result = Rest_Initpro::sendCheck($checkInfo);

    $decodeResponse = json_decode($result);

    //Начисление кэшбэка
    if (is_null($decodeResponse->error)) {
        $CashBack = Property_Controller::factoryByTag('payment_cashback');
        $Director = User::current()->getDirector();
        $cashBack = $CashBack->getValues($Director)[0]->value();

        if ($cashBack > 0) {
            $bonuses = intval($sum * ($cashBack / 100));
            if ($bonuses > 0) {
                $Payment = Core::factory('Payment');
                $Payment->description('Начисление бонусов');
                $Payment->value($bonuses);
                $Payment->type(Payment::TYPE_CASHBACK);
                $Payment->user($userId);
                $Payment->save();
            }
        }
    }

    exit($result);
}


/**
 * Ответ при регистрации чека
 */
if ($action === 'checkCallback') {
    $log = fopen(ROOT . '/log.txt', 'w');
    fwrite($log, json_encode($_POST));
    fclose($log);
    exit;
}