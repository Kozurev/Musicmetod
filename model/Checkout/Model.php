<?php


namespace Model\Checkout;

/**
 * @property string|null $groupCode
 * @property string|null $inn
 * @property string|null $email
 * @property string|null $sno
 *
 * Class Model
 * @package Model\Checkout
 */
class Model extends \Core_Entity
{
    /**
     * @var string|null
     */
    public ?string $title = null;

    /**
     * @var int|null
     */
    public ?int $type = null;

    /**
     * @var string
     */
    public string $login = '';

    /**
     * @var string
     */
    public string $password = '';

    /**
     * @var string|null
     */
    public ?string $data = null;

    /**
     * @return string
     */
    public function getTableName(): string
    {
        return 'Checkouts';
    }

    /**
     * @return string
     */
    public function getLogin() : string
    {
        return $this->login;
    }

    /**
     * @return string
     */
    public function getPassword() : string
    {
        return $this->password;
    }

    /**
     * @return mixed
     */
    public function getData() : \stdClass
    {
        $decodedData = json_decode($this->data);
        return $decodedData instanceof \stdClass ? $decodedData : new \stdClass();
    }

    /**
     * @param $data
     */
    public function setData($data)
    {
        $this->data = json_encode($data);
    }

    /**
     * @param string $name
     * @return mixed|null
     */
    public function __get(string $name)
    {
        return isset($this->$name) ? $this->$name : $this->getData()->$name ?? null;
    }

    /**
     * @param $name
     * @param $value
     */
    public function __set($name, $value)
    {
        if (isset($this->$name)) {
            $this->$name = $value;
        } else {
            $data = $this->getData();
            $data->$name = $value;
            $this->setData($data);
        }
    }

    /**
     * @param array $forbiddenProps
     * @return \stdClass
     */
    public function toStd(array $forbiddenProps = []) : \stdClass
    {
        $std = parent::toStd($forbiddenProps);
        $std->data = $this->getData();
        // $std->data->_customTag = 'data';
        return $std;
    }
}