<?php
/**
 * Created by PhpStorm.
 * User: Egor
 * Date: 11.10.2019
 * Time: 10:07
 *
 * Class Core_Recaptcha
 */
class Core_Recaptcha
{
    /**
     * @var Core_Recaptcha|null
     */
    private static ?Core_Recaptcha $_instance = null;

    /**
     * Публичный HTML ключ
     *
     * @var string
     */
    private string $publicKey;

    /**
     * Секретный ключ
     *
     * @var string
     */
    private string $secretKey;

    /**
     * Расшифровка кодов ошибок ответа Google
     *
     * @var array
     */
    private static array $errorCodesLang = [
        'missing-input-secret' =>   'Отсутствует секретный ключ',
        'invalid-input-secret' =>   'Указанный секретный ключ не существует или искажен.',
        'missing-input-response' => 'Значение reCAPTCHA не было указано либо указано неверно.',
        'invalid-input-response' => 'Значение передаваемого из формы хэша недопустимо или искажено.',
        'bad-request' =>            'Невозможно распознать переданный хэш.',
        'timeout-or-duplicate' =>   'Ответ более недействителен: либо слишком старый либо уже использовался'
    ];

    /**
     * Список полученных кодов ошибок
     *
     * @var array
     */
    private array $errors = [];


    /**
     * Core_Recaptcha constructor.
     * @param string $secretKey
     * @param string $publicKey
     */
    private function __construct(string $secretKey, string $publicKey)
    {
        $this->publicKey = $publicKey;
        $this->secretKey = $secretKey;
    }

    /**
     * @return Core_Recaptcha
     */
    public static function instance(): Core_Recaptcha
    {
        if (empty(self::$_instance)) {
            global $CFG;
            self::$_instance = new Core_Recaptcha($CFG->recaptcha->secretKey, $CFG->recaptcha->publicKey);
        }
        return self::$_instance;
    }

    /**
     * @return string
     */
    public function getPublicKey(): string
    {
        return $this->publicKey;
    }

    /**
     * Получение текста ошибок
     *
     * @return string
     */
    public function getErrorsStr(): string
    {
        $errorsMsg = '';
        foreach ($this->errors as $error) {
            $errorsMsg .= self::$errorCodesLang[$error] ?? 'RECAPTCHA: неизвестная ошибка' . PHP_EOL;
        }
        $errorsMsg .= PHP_EOL . ' Попробуйте обновить страницу и авторизоваться повторно';
        return $errorsMsg;
    }

    /**
     * Проверка введения Google reCAPTCHA
     *
     * @return bool
     */
    public function checkRequest(): bool
    {
        global $CFG;
        if (empty($CFG->recaptcha->publicKey)) {
            return true;
        }
        $recaptchaRequest = Core_Array::Request('g-recaptcha-response', null, PARAM_STRING);
        return $this->isValid($recaptchaRequest);
    }

    /**
     * Отправка запроса в Google API и расшифровка ответа
     *
     * @param $request
     * @return bool
     */
    public function isValid($request): bool
    {
        $postData = http_build_query([
            'secret'    =>  $this->secretKey,
            'response'  =>  $request,
            'remoteip'  =>  Core_Array::Server('REMOTE_ADDR', '', PARAM_STRING)
        ]);

        $opts = [
            'http' => [
                'method'  => 'POST',
                'header'  => 'Content-type: application/x-www-form-urlencoded',
                'content' => $postData
            ]
        ];

        $context  = stream_context_create($opts);
        $response = file_get_contents(
            'https://www.google.com/recaptcha/api/siteverify',
            false,
            $context
        );
        $response = json_decode($response , true);

        if ($response !== null && $response['success'] == true) {
            return true;
        } else {
            $this->errors = $response['error-codes'];
            Log::instance()->error(Log::TYPE_RECAPRCHA, 'RECAPTCHA: ' . json_encode($response));
            return false;
        }
    }

}