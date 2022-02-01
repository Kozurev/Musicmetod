<?php

namespace Model\Checkout\Facades;

use Model\Checkout\Model;

/**
 * Class Checkout_InitPro
 * @package Model\Checkout
 */
class InitPro extends A_Facade
{
    const METHOD_POST = 'post';
    const METHOD_GET = 'get';

    /**
     * Используемая версия API
     *
     * @var string
     */
    private static string $apiVersion = 'v1';

    /**
     * URL для API запросов
     *
     * @var string
     */
    private static string $apiUrl = 'https://kassa.initpro.ru/lk/api';

    /**
     * Авторизационный токен
     *
     * @var string|null
     */
    private ?string $authToken = null;

    /**
     * Адрес ответа регистрации чека
     *
     * @var string
     */
    private static string $callbackUrl = 'http://musicmetod.ru/musadm/api/initpro?action=checkCallback';

    /**
     * Список названий всевозможных команд для API
     *
     * @var array
     */
    private static array $actions = [
        'token' => 'getToken',  //Получение токена
        'pay' => 'sell'         //Формирование чека на приход средств
    ];

    /**
     * @var array|array[]
     */
    private static array $additionalFields = [
        [
            'required' => true,
            'type' => 'text',
            'tag' => 'groupCode',
            'title' => 'Код группы'
        ],
        [
            'required' => true,
            'type' => 'email',
            'tag' => 'email',
            'title' => 'Email'
        ],
        [
            'required' => true,
            'type' => 'list',
            'tag' => 'sno',
            'title' => 'Система налогооблажения',
            'items' => [
                'patent' => 'Патент'
            ]
        ],
        [
            'required' => true,
            'type' => 'text',
            'tag' => 'inn',
            'title' => 'ИНН'
        ]
    ];

    /**
     * @return array|array[]
     */
    public function getAdditionalFieldsList() : array
    {
        return self::$additionalFields;
    }

    /**
     * @return string
     */
    public function getValidateModelErrorsStr() : string
    {
        return 'Ошибка данных кассы инитпро';
    }

    /**
     * Авторизация в сервисе
     *
     * @param string $method
     * @return string
     * @throws \Exception
     */
    protected function makeAuthToken($method = self::METHOD_POST) : string
    {
        $params = [
            'login' => $this->getModel()->getLogin(),
            'pass' => $this->getModel()->getPassword()
        ];

        if ($method === self::METHOD_GET) {
            $url = self::makeUrl(self::$actions['token'], null, $params);
        } else {
            $url = self::makeUrl(self::$actions['token'], null, []);
        }

        if ($method === self::METHOD_POST) {
            $streamContext = stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'ignore_errors' => true,
                    'header' => 'Content-type: application/json; charset=utf-8',
                    'content' => json_encode($params)
                ]
            ]);
        } else {
            $streamContext = null;
        }

        $response = json_decode(@file_get_contents($url, false, $streamContext));

        if (empty($response)) {
            throw new \Exception('Инитпро: неизвестная ошибка при авторизации');
        } elseif (!empty($response->error)) {
            throw new \Exception($response->error->text ?? 'Неизвестная ошибка авторизации InitPro');
        } else {
            return strval($response->token);
        }
    }

    /**
     * @return string
     * @throws \Exception
     */
    protected function getAuthToken() : string
    {
        if (is_null($this->authToken)) {
            $this->authToken = $this->makeAuthToken();
        }
        return $this->authToken;
    }

    /**
     * @param \Payment $payment
     * @throws \Exception
     */
    public function makeReceipt(\Payment $payment)
    {
        $url = self::makeUrl(self::$actions['pay'], $this->getModel()->groupCode, ['token' => $this->getAuthToken()]);
        $streamContext = stream_context_create([
            'http' => [
                'method' => 'POST',
                'ignore_errors' => true,
                'header' => 'Content-type: application/json; charset=utf-8',
                'content' => json_encode($this->makeReceiptData($payment))
            ]
        ]);
        $response = json_decode(@file_get_contents($url, false, $streamContext));
        if (!is_null($response->error)) {
            throw new \Exception($response->error->text);
        } else {
            $payment->saveCheckoutUuid($response->uuid);
        }
    }

    /**
     * Формирование данных чека для регистрации
     *
     * @param \Payment $payment
     * @return \stdClass
     */
    protected function makeReceiptData(\Payment $payment): \stdClass
    {
        $check = new \stdClass();
        $check->external_id = strval($payment->getId());
        $check->timestamp = date('d.m.y H:i:s');
        $check->service = new \stdClass();
        $check->service->callback_url = self::$callbackUrl;
        $check->receipt = new \stdClass();

        $client = $payment->getUser();
        $check->receipt->client = new \stdClass();
        if (!empty($client->email())) {
            $check->receipt->client->email = $client->email();
        } else {
            $pattern = '~[^0-9]+~';
            $replacement = '';
            $number = preg_replace($pattern, $replacement, $client->phoneNumber());
            if (substr($number, 0, 1) == '7') {
                $number = substr($number, 1);
            }
            $check->receipt->client->phone = $number;
        }

        $checkCompany = new \stdClass();
        $checkCompany->email = $this->getModel()->email;
        $checkCompany->sno = $this->getModel()->sno;
        $checkCompany->inn = $this->getModel()->inn;
        $checkCompany->payment_address = 'https://musicmetod.ru'; //$_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'];
        $check->receipt->company = $checkCompany;

        $checkItem = new \stdClass();
        $checkItem->name = $payment->description();
        $checkItem->price = $payment->value();
        $checkItem->quantity = 1.0;
        $checkItem->sum = $payment->value();
        $checkItem->payment_method = 'full_prepayment';
        $checkItem->payment_object = 'payment';
        $checkItem->vat = new \stdClass();
        $checkItem->vat->type = 'none';
        $check->receipt->items = [$checkItem];

        $checkPayment = new \stdClass();
        $checkPayment->type = 1;
        $checkPayment->sum = $payment->value();
        $check->receipt->payments = [$checkPayment];

        $checkVat = new \stdClass();
        $checkVat->type = 'none';
        $checkVat->sum = 0.0;
        $check->receipt->vats = [$checkVat];

        $check->receipt->total = $payment->value();
        return $check;
    }

    /**
     * Формирование API URL касс инитпро
     *
     * @param string $action
     * @param string|null $groupsCode
     * @param array $data
     * @return string
     */
    protected static function makeUrl(string $action, string $groupsCode = null, $data = []) : string
    {
        $url = self::$apiUrl . '/' . self::$apiVersion;
        if (!is_null($groupsCode)) {
            $url .= '/' . $groupsCode;
        }
        $url .= '/' . $action;

        if (empty($data)) {
            return $url;
        } else {
            return $url . '?' . http_build_query($data);
        }
    }
}