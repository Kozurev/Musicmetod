<?php

namespace Model\Sms;

/**
 * Class Template
 * @package Model\Sms
 */
class Sms_Template
{
    const TAG_LIDS_BEFORE_CONSULT_DAY = 'lids_before_consult_day';
    const TAG_LIDS_BEFORE_CONSULT_HOUR = 'lids_before_consult_hour';

    /**
     * @var array|string[]
     */
    protected static array $messages = [
        self::TAG_LIDS_BEFORE_CONSULT_DAY => 'Завтра в школу музыки, всё по плану) Накладки? Сообщите 89092012550',
        self::TAG_LIDS_BEFORE_CONSULT_HOUR => 'До встречи в школе музыки осталось совсем чуть-чуть, контрольное напоминание;)'
    ];

    /**
     * @var string|null
     */
    protected ?string $tag = null;

    /**
     * @var string|null
     */
    protected ?string $text = null;

    /**
     * @param string|null $tag
     * @return $this|string
     */
    public function tag(string $tag = null)
    {
        if (is_null($tag)) {
            return strval($this->tag);
        } else {
            $this->tag = $tag;
            return $this;
        }
    }

    /**
     * @param string|null $text
     * @return $this|string
     */
    public function text(string $text = null)
    {
        if (is_null($text)) {
            return strval($this->text);
        } else {
            $this->text = $text;
            return $this;
        }
    }

    /**
     * @param string $tag
     * @return static
     */
    public static function getByTag(string $tag) : self
    {
        return (new self())->tag($tag)->text(self::$messages[$tag]);
    }
}