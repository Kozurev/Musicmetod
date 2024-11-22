<?php

namespace Model;

/**
 * Class MyCalls
 */
class MyCalls extends Api
{
    /**
     * Стандартная версия API
     */
    const API_VERSION = 'v1';
    const PARAM_USER_NAME = 'user_name';
    const PARAM_API_TOKEN = 'api_key';
    const PARAM_ACTION = 'action';
    const PARAM_TO = 'to';
    const ACTION_MAKE_CALL = 0;
    const CALL_SUCCESS = 'Make call posted';

    /**
     * @var array
     */
    private static array $methods = [
        0 => 'calls.make_call'
    ];

    /**
     * @var string
     */
    private $email;

    /**
     * @var string
     */
    private $apiUrl;

    /**
     * @var string
     */
    private $apiToken;

    /**
     * MyCalls constructor.
     * @param \User $user
     * @throws \Exception
     */
    public function __construct(\User $user)
    {
        $this->email = $user->email();
        if (empty($this->email)) {
            throw new \Exception('Указание email для совершения звонка обязательно');
        }

        $director = $user->getDirector();
        if (is_null($director)) {
            throw new \Exception('Пользователь не принадлежит директору');
        }

        $this->apiToken = \Property_Controller::factoryByTag('my_calls_token')->getValues($director)[0]->value();
        if (empty($this->apiToken)) {
            throw new \Exception('Указание авторизацтонного токена для совершения звонка обязательно');
        }

        $this->apiUrl = \Property_Controller::factoryByTag('my_calls_url')->getValues($director)[0]->value();
        if (empty($this->apiUrl)) {
            throw new \Exception('У школы не настроена интеграция с сервисом "Мои звонки"');
        }
        $this->apiUrl = 'https://' . $this->apiUrl . '/api/' . self::API_VERSION;
    }

    /**
     * @param string $phoneNumber
     * @return mixed
     */
    public function makeCall(string $phoneNumber)
    {
        return self::getJsonRequest($this->apiUrl, [
            self::PARAM_USER_NAME   => $this->email,
            self::PARAM_API_TOKEN   => $this->apiToken,
            self::PARAM_ACTION      => self::$methods[self::ACTION_MAKE_CALL],
            self::PARAM_TO          => $phoneNumber
        ]);
    }
}
