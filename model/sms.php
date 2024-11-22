<?php

namespace Model;

use Illuminate\Support\Collection;
use Model\Sms\Sms_Template as Template;

/**
 * Фассад для рассылки SMS сообщений
 *
 * @date 2020-10-28 19:00
 * @author BadWolf
 * Class Sms
 */
class sms extends Api
{
    const ACTION_SEND = 1;

    const CHANNEL_DIGIT = 'digit';
    const CHANNEL_CHAR = 'char';
    const CHANNEL_VIBER = 'viber';
    const CHANNEL_VK = 'vk';
    const CHANNEL_TELEGRAM = 'telegram';

    const PARAM_API_KEY = 'apiKey';
    const PARAM_SMS = 'sms';
    const PARAM_PHONE = 'phone';
    const PARAM_TEXT = 'text';
    const PARAM_CHANNEL = 'channel';
    const PARAM_SENDER = 'sender';

    /**
     * @var array|string[]
     */
    protected static array $channels = [
        self::CHANNEL_DIGIT,
        self::CHANNEL_CHAR,
        self::CHANNEL_VIBER,
        self::CHANNEL_VK,
        self::CHANNEL_TELEGRAM
    ];

    /**
     * @var self|null
     */
    protected static ?self $_instance = null;

    /**
     * API ключ для работы с сервисом
     *
     * @var string|null
     */
    protected ?string $apiKey = null;

    /**
     * @var string
     */
    protected static string $apiUrl = 'https://admin.p1sms.ru/apiSms';

    /**
     * @var array|string[]
     */
    protected static array $actions = [
        self::ACTION_SEND => 'create'
    ];

    /**
     * Шаблон текста сообщения
     *
     * @var template|null
     */
    protected ?template $template = null;

    /**
     * Текст сообщения
     *
     * @var string|null
     */
    protected ?string $messageText = null;

    /**
     *  Номера телефонов, на которые будет идти рассылка
     *
     * @var Collection
     */
    protected Collection $phoneNumbers;

    /**
     * @var string
     */
    protected string $defaultChannel = self::CHANNEL_CHAR;

    /**
     * @var string|null
     */
    protected string $defaultSenderName = 'OpenMusic';
    //protected string $defaultSenderName = 'VIRTA';

    /**
     * Sms constructor.
     * @param string $apiKey
     */
    protected function __construct(string $apiKey)
    {
        $this->apiKey = $apiKey;
        $this->phoneNumbers = collect([]);
    }

    /**
     * @return sms|static
     */
    public static function instance() : self
    {
        if (is_null(self::$_instance)) {
            global $CFG;
            self::$_instance = new self($CFG->sms->api_key);
        }
        return self::$_instance;
    }

    /**
     * @return string
     */
    public function getChannel() : string
    {
        return $this->defaultChannel;
    }

    /**
     * @param string $channel
     * @return $this
     */
    public function setChannel(string $channel) : self
    {
        $this->defaultChannel = $channel;
        return $this;
    }

    /**
     * @param string $phoneNumber
     * @param string|null $channel
     * @return $this
     */
    public function toNumber(string $phoneNumber, string $channel = null) : self
    {
        $this->clearPhoneNumbers();
        $this->appendNumber($phoneNumber, $channel);
        return $this;
    }

    /**
     * @param array $phoneNumbers
     * @return $this
     */
    public function toNumbers(array $phoneNumbers) : self
    {
        $this->clearPhoneNumbers();
        foreach ($phoneNumbers as $data) {
            if (is_string($data)) {
                $this->appendNumber($data);
            } elseif (is_array($data) && !empty($data[self::PARAM_PHONE] ?? null)) {
                $this->appendNumber($data[self::PARAM_PHONE], $data[self::PARAM_CHANNEL] ?? null);
            }
        }
        return $this;
    }

    /**
     * @param string $phoneNumber
     * @param string|null $channel
     * @return $this
     */
    public function appendNumber(string $phoneNumber, string $channel = null) : self
    {
        $this->phoneNumbers->add([
            self::PARAM_PHONE => $phoneNumber,
            self::PARAM_CHANNEL => !is_null($channel) ? $channel : self::getChannel()
        ]);
        return $this;
    }

    /**
     *
     */
    public function clearPhoneNumbers() : void
    {
        $this->phoneNumbers = collect([]);
    }

    /**
     * @return string
     */
    public function getMessage() : string
    {
        return strval($this->messageText);
    }

    /**
     * @param string $messageText
     * @return $this
     */
    public function setMessage(string $messageText) : self
    {
        $this->messageText = $messageText;
        return $this;
    }

    /**
     * @return string
     */
    public function getSender() : string
    {
        return $this->defaultSenderName;
    }

    /**
     * @param string $sender
     * @return $this
     */
    public function setSender(string $sender) : self
    {
        $this->defaultSenderName = $sender;
        return $this;
    }

    /**
     * @param template $template
     * @return $this
     */
    public function setTemplate(Template $template) : self
    {
        $this->template = $template;
        return $this;
    }

    /**
     * @param string $templateTag
     * @return $this
     */
    public function setTemplateByTag(string $templateTag)
    {
        $template = Template::getByTag($templateTag);
        $this->setMessage($template->text());
        return $this;
    }

    /**
     * @return array
     */
    protected function makeRequestData() : array
    {
        $data = [];
        $data[self::PARAM_API_KEY] = $this->apiKey;

        $sms = collect([]);
        foreach ($this->phoneNumbers as $numberData) {
            $smsData = [
                self::PARAM_PHONE => $numberData[self::PARAM_PHONE],
                self::PARAM_CHANNEL => $numberData[self::PARAM_CHANNEL],
                self::PARAM_TEXT => $this->getMessage()
            ];
            if ($smsData[self::PARAM_CHANNEL] == self::CHANNEL_CHAR) {
                $smsData[self::PARAM_SENDER] = $this->getSender();
            }
            $sms->add($smsData);
        }
        $data[self::PARAM_SMS] = $sms->all();
        return $data;
    }

    /**
     * @throws \Exception
     * @return \stdClass
     */
    public function send() : \stdClass
    {
        $response = json_decode(self::getJsonRequest(self::makeUrl(self::ACTION_SEND), $this->makeRequestData()));
        if (($response->status ?? 'error') === 'error') {
            throw new \Exception(($response->data->debugInfo ?? '') . ' ' . ($response->data->message ?? 'undefined error'));
        }
        return $response;
    }

    /**
     * @param int $action
     * @return string
     */
    public static function makeUrl(int $action) : string
    {
        return self::$apiUrl . '/' . self::$actions[$action] ?? '';
    }
}
