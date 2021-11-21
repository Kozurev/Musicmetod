<?php

use Model\CreditOrders\CreditServiceProvider;
use Model\User\User_Client;

$action = request()->get('action');

/**
 * Создание кредитной заявки
 */
if ($action === 'createOrder') {
    exit(REST::responseError(REST::ERROR_CODE_CUSTOM, 'Рассрочка недоступна на данный момент'));

//    $tariffId = request()->get('tariff_id');
//
//    if (empty($tariffId)) {
//        exit(REST::responseError(REST::ERROR_CODE_REQUIRED_PARAM, 'Отсутствует обязательный параметр tariff_id'));
//    }
//
//    $facade = new CreditServiceProvider();
//    $user = User_Client::find(User_Auth::current()->getId());
//    /** @var Payment_Tariff|null $tariff */
//    $tariff = Payment_Tariff::query()
//        ->where('id', '=', $tariffId)
//        ->where('subordinated', '=', User_Auth::current()->getDirector()->getId())
//        ->where('access', '=', 1)
//        ->find();
//
//    if (is_null($user)) {
//        exit(REST::responseError(REST::ERROR_CODE_AUTH, 'Покупка тарифов доступна только клиентам'));
//    }
//    if (is_null($tariff)) {
//        exit(REST::responseError(REST::ERROR_CODE_NOT_FOUND, 'Тариф с id ' . $tariffId . ' не найден'));
//    }
//
//    try {
//        $providerResponse = $facade->getProvider()->createOrder($user, $tariff);
//        if (isset($providerResponse->link)) {
//            $response = json_encode(['link' => $providerResponse->link]);
//        } else {
//            \Log::instance()->error('tinkoff', json_encode($providerResponse));
//            $response = REST::responseError(REST::ERROR_CODE_CUSTOM, 'Неизвестная ошибка');
//        }
//    } catch (\Throwable $throwable) {
//        $response = REST::responseError(REST::ERROR_CODE_CUSTOM, $throwable->getMessage());
//    }
//
//    exit($response);
}

/**
 *
 */
if ($action === 'redirect') {
    Log::instance()->debug('credit_redirect', request()->all()->toJson());
    header('Location: ' . mapping('rates', [], MAPPING_CLIENT_LC));
}

/**
 *
 */
if ($action === 'redirectSuccess') {
    Log::instance()->debug('credit_redirect_success', request()->all()->toJson());
    header('Location: ' . mapping('rates', [], MAPPING_CLIENT_LC));
}

/**
 *
 */
if ($action === 'redirectFail') {
    Log::instance()->debug('credit_redirect_fail', request()->all()->toJson());
    header('Location: ' . mapping('rates', [], MAPPING_CLIENT_LC));
}

/**
 * Обработчик для хуков при смене статуса заявки
 */
if ($action === 'changeStatus') {
    $facade = new CreditServiceProvider();
    $facade->getProvider()->changeStatusWebhook(request()->all());
    exit;
}

$facade = new CreditServiceProvider();
$facade->getProvider()->changeStatusWebhook(array_merge(request()->all(), ['withoutAction' => true]));