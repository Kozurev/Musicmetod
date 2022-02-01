<?php
/**
 * Created by PhpStorm.
 * User: Egor
 * Date: 27.06.2020
 * Time: 18:21
 */


use Model\Checkout\Checkout;

$orderId = Core_Array::Get('orderId', null, PARAM_STRING);

if (!is_null($orderId)) {
    $orderData = Temp::getAndRemove($orderId);
    if (!is_null($orderData)) {
        /** @var Payment|null $payment */
        $payment = Payment::query()
            ->where('merchant_order_id', '=', $orderId)
            ->where('status', '=', Payment::STATUS_PENDING)
            ->where('user', '=', User_Auth::current()->getId())
            ->find();
        if (!is_null($payment)) {
            $payment->setStatusSuccess();

            //Наблюдатель для начисления кэшбэка
            Core::notify(['payment' => &$payment], 'after.user.deposit');

            try {
                $user = $payment->getUser();
                if (is_null($user)) {
                    throw new Exception('У платежа ' . $payment->getId() . ' отсутствует пользователь');
                }
                $checkout = Checkout::makeForUser($user);
                if (is_null($checkout)) {
                    throw new Exception('Отсутствуют настройки кассы для филиала пользователя ' . $user->getFio() . '. Платеж номер: ' . $payment->getId() . ' на сумму: ' . $payment->value());
                }
                $checkout->instance()->makeReceipt($payment);
                $payment->appendComment('ID в онлайн-кассе: ' . $payment->checkoutUuid() . '; ID в платежном шлюзе: ' . $payment->merchantOrderId());
            } catch (Exception $e) {
                Log::instance()->error(Log::TYPE_CHECKOUT, $e->getMessage());
            }

            if (!empty($orderData->successUrl ?? '')) {
                header('Location: ' . $orderData->successUrl);
                exit;
            }
        }
    }
}