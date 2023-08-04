<?php

namespace Model;

class Payonline
{
    const API_URL = 'https://secure.payonlinesystem.com/ru';
    const ACTION_REGISTER_ORDER = 'payment';

    const PARAM_PRIVATE_SECURE_KEY = 'PrivateSecurityKey';
    const PARAM_MERCHANT_ID = 'MerchantId';
    const PARAM_ORDER_ID = 'OrderId';
    const PARAM_AMOUNT = 'Amount';
    const PARAM_CURRENCY = 'Currency';
    const PARAM_ORDER_DESCRIPTION = 'OrderDescription';
    const PARAM_SECURITY_KEY = 'SecurityKey';
    const PARAM_SUCCESS_URL = 'ReturnUrl';
    const PARAM_FAIL_URL = 'FailUrl';

//    const PAYMENT_STATUS_PENDING = 'Pending';
//    const PAYMENT_STATUS_SUCCESS = 'Settled';
//    const PAYMENT_STATUS_DECLINE = 'Declined';
//    const PAYMENT_STATUS_VOIDED = 'Voided';

//    private static array $statuses = [
//        self::PAYMENT_STATUS_PENDING => 'Авторизация транзакции подтверждена, деньги в размере суммы транзакции заблокированы на счету держателя в ожидании списания.',
//        self::PAYMENT_STATUS_SUCCESS => 'Списание суммы транзакции со счета держателя карты подтверждено.',
//        self::PAYMENT_STATUS_DECLINE => 'Авторизация транзакции подтверждена, деньги в размере суммы транзакции заблокированы на счету держателя в ожидании списания.',
//        self::PAYMENT_STATUS_VOIDED => 'Авторизация транзакции подтверждена, деньги в размере суммы транзакции заблокированы на счету держателя в ожидании списания.',
//    ];

//    private static array $errors = [
//        1 => 'Возникла техническая ошибка, повторите оплату позже.',
//        2 => 'Провести платеж по банковской карте невозможно. Вам стоит воспользоваться другим способом оплаты;',
//        3 => 'Платеж отклоняется банком-эмитентом карты. Вам стоит связаться с банком, выяснить причину отказа и повторить попытку оплаты.'
//    ];

    /**
     * Авторизационный токен для платежного шлюза
     *
     * @var string
     */
    private string $privateKey;

    /**
     * id мерчанта в платежной системе
     *
     * @var int
     */
    private int $merchantId;

    /**
     * Валюта для платежей по умолчанию
     *
     * @var string
     */
    private string $currency = 'RUB';

    /**
     * id платежа из таблицы "payment"
     *
     * @var int|null
     */
    private int $orderId;

    /**
     * Сумма платежа
     *
     * @var int|null
     */
    private int $amount;

    /**
     * Примечание к платежу
     *
     * @var string|null
     */
    private ?string $description = null;

    /**
     * URL для редиректа после успешной оплаты
     *
     * @var string|null
     */
    private ?string $successUrl = null;

    /**
     * URL для редиректа, после неудачной оплаты
     *
     * @var string|null
     */
    private ?string $failUrl = null;

    /**
     * @param int $orderId
     * @param int $amount
     * @return self
     */
    public static function instance(int $orderId, int $amount): self
    {
        global $CFG;

        return new self(
            $CFG->payonline->secret_token,
            $CFG->payonline->merchant_id,
            $orderId,
            $amount
        );
    }

    /**
     * Payonline constructor.
     * @param string $privateKey
     * @param int $merchantId
     * @param int $orderId
     * @param int $amount
     */
    private function __construct(
        string $privateKey,
        int $merchantId,
        int $orderId,
        int $amount
    ) {
        $this->privateKey = $privateKey;
        $this->merchantId = $merchantId;
        $this->orderId = $orderId;
        $this->amount = $amount;
//        $urlParams = [
//            'token' => $userAuthToken,
//            'orderId' => $orderId
//        ];
//        $this->successUrl = mapping('deposit_success', $urlParams);
//        $this->failUrl = mapping('deposit_error', $urlParams);
    }

//    /**
//     * @param int $orderId
//     */
//    public function setorderId(int $orderId)
//    {
//        $this->orderId = $orderId;
//    }

//    /**
//     * @param int $amount
//     */
//    public function setAmount(int $amount)
//    {
//        $this->amount = $amount;
//    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    /**
     * @param string $successUrl
     * @return void
     */
    public function setSuccessUrl(string $successUrl): void
    {
        $this->successUrl = $successUrl;
    }

    /**
     * @param string $failUrl
     * @return void
     */
    public function setFailUrl(string $failUrl): void
    {
        $this->failUrl = $failUrl;
    }

//    /**
//     * @param int $status
//     * @return string
//     */
//    public static function getStatusName(int $status): string
//    {
//        return self::$statuses[$status] ?? 'Неизвестный статус';
//    }

    /**
     * @return string
     */
    public function getPaymentLink(): string
    {
        $params = [
            self::PARAM_PRIVATE_SECURE_KEY => $this->privateKey,
            self::PARAM_MERCHANT_ID => $this->merchantId,
            self::PARAM_AMOUNT => number_format($this->amount, 2, '.', ''),
            self::PARAM_CURRENCY => $this->currency,
            self::PARAM_ORDER_ID => $this->orderId,
            //TODO: добавить юы проверки на наличие этих полей для красоты
            self::PARAM_ORDER_DESCRIPTION => $this->description,
            self::PARAM_SUCCESS_URL => $this->successUrl,
            self::PARAM_FAIL_URL => $this->failUrl,
            self::PARAM_SECURITY_KEY => $this->getSecurityKey()
        ];

        return $this->getUrl(self::ACTION_REGISTER_ORDER) . '?' . http_build_query($params);
    }

//    /**
//     * @param Payment $payment
//     * @throws Exception
//     */
//    public function checkStatus(Payment $payment)
//    {
//        if (empty($payment->merchantOrderId())) {
//            throw new Exception('Отсутствует ID платежа в платежном шлюзе');
//        }
//        $params = [
//            self::PARAM_TOKEN => $this->token,
//            self::PARAM_ORDER_ID => $payment->merchantOrderId()
//        ];
//        $response = Api::getRequest($this->getUrl(self::ACTION_CHECK_STATUS), $params);
//
//        if ($payment->isStatusPending()) {
//            if (!empty($response->errorCode ?? null)) {
//                $payment->setStatusError();
//                $payment->appendComment($response->errorMessage);
//            } else {
//                if ($response->orderStatus == self::ORDER_STATUS_SUCCESS) {
//                    $payment->setStatusSuccess();
//                } elseif ($response->orderStatus == self::ORDER_STATUS_CANCELED) {
//                    $payment->setStatusCanceled();
//                }
//                $payment->appendComment('Статус платежа: ' . self::getStatusName($response->orderStatus) . '. ' . $response->actionCodeDescription);
//            }
//        }
//    }

    /**
     * @param string $action
     * @return string
     */
    public function getUrl(string $action) : string
    {
        return self::API_URL . '/' . $action;
    }

    /**
     * @return string
     */
    public function getSecurityKey(): string
    {
        $secureKeyParams = 'MerchantId='. $this->merchantId;
        $secureKeyParams .= '&OrderId='.$this->orderId;
        $secureKeyParams .= '&Amount='. number_format($this->amount, 2, '.', '');
        $secureKeyParams .= '&Currency='. $this->currency;
        //Авиа потом добавлю
        if (strlen($this->description) < 101 && strlen($this->description) > 1) {
            $secureKeyParams .= '&OrderDescription=' . ($this->description);
        }
        $secureKeyParams .= '&PrivateSecurityKey=' . $this->privateKey;

        return md5($secureKeyParams);
    }
}