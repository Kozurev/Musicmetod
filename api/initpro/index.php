<?php
/**
 * Created by PhpStorm.
 * User: Egor
 * Date: 02.09.2019
 * Time: 13:27
 */

use Model\Checkout\Checkout;
use Model\Checkout\Model;

$action = Core_Array::Request('action', null, PARAM_STRING);

if ($action === 'sendCheck') {
    $paymentId =    Core_Array::Post('paymentId', 0, PARAM_INT);
    //$userId =       Core_Array::Post('userId', 0, PARAM_INT);
    $userEmail =    Core_Array::Post('userEmail', '', PARAM_STRING);
    //$description =  Core_Array::Post('description', '', PARAM_STRING);
    //$sum =          Core_Array::Post('sum', 0.0, PARAM_FLOAT);

    $payment = Payment_Controller::factory($paymentId);
    $client = $payment->getUser();
    $client->email($userEmail)->save();

    try {
        $checkout = Checkout::makeForUser($client);
        $checkout->instance()->makeReceipt($payment);
    } catch (Exception $e) {
        exit(json_encode(['error' => $e->getMessage()]));
    }

    Core::notify(['payment' => &$payment], 'after.user.deposit');

    exit(json_encode(['success' => true]));
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