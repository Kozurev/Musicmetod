<?php
/**
 * Класс, реализующий методы для преобразования объектов в XML,
 * парсинг сформированного XML документа XSL шаблоном и вывод результата
 *
 * Простой тэг имеет значение и не может иметь вложенных XML-сущьностей
 * Сложный тэг не имеет значнеия но может иметь вложенные XML-сущьности
 *
 * TODO: данный класс стал сборной солянкой. Надо бы придумать как разделить его на несколько отдельных логических составляющих
 * TODO: разделить работу с XML & XSL и методы, связанные с выполнением SQL-запросов & валидация
 *
 *
 * @author Bad Wolf
 * @date 02.08.2018
 * @version 20190218
 * Class Core_Entity
 */
class Core_Entity extends Core_Entity_Model
{
    const VALID_REQUIRED = 'required';
    const VALID_TYPE = 'type';
    const VALID_LENGTH = 'length';
    const VALID_MIN_LENGTH = 'minlength';
    const VALID_MAX_LENGTH = 'maxlength';
    const VALID_MIN_VALUE = 'minval';
    const VALID_MAX_VALUE = 'maxval';


    /**
     * Ошибки валидации сохраняемого объекта
     *
     * @var array
     */
    private array $_validateErrors = [];


    /**
     * Информация об ошибках валидации модели в формате строки
     *
     * @var array
     */
    private array $_validateErrorsStr = [];


    /**
     * Core_Entity constructor.
     * @param array $properties
     */
    public function __construct(array $properties = [])
    {
        foreach ($properties as $propertyName => $propertyValue) {
            $this->$propertyName = $propertyValue;
        }
    }


    /**
     * Валидация значнеий сохраняемого объекта
     * все ошибки валидации сохраняются в свойство $validateErrors
     *
     * @return bool
     */
    public function _validateModel() : bool
    {
        if (!method_exists($this, 'schema')) {
            return true;
        }

        foreach ($this->schema() as $propName => $rules) {
            $isRequired =   Core_Array::getValue($rules, 'required', false, PARAM_BOOL);
            $type =         Core_Array::getValue($rules, 'type', null, PARAM_STRING);
            $length =       Core_Array::getValue($rules, 'length', null, PARAM_INT);
            $minLength =    Core_Array::getValue($rules, 'minlength', null, PARAM_INT);
            $maxLength =    Core_Array::getValue($rules, 'maxlength', null, PARAM_INT);
            $minVal =       Core_Array::getValue($rules, 'minval', null, PARAM_FLOAT);
            $maxVal =       Core_Array::getValue($rules, 'maxval', null, PARAM_FLOAT);

            //check required
            if ($isRequired === true) {
                if (is_null($this->$propName)) {
                    $this->_setValidateError(
                        get_class($this) . '->' . $propName,
                        self::VALID_REQUIRED,
                        ['valid' => true, 'current' => false]
                    );
                }
            } elseif ($isRequired === false && is_null($this->$propName)) {
                continue;
            }

            //check type
            if (!is_null($type)) {
                if ($type == PARAM_STRING && (is_object($this->$propName) || is_array($this->$propName))) {
                    $this->_setValidateError(
                        get_class($this) . '->' . $propName,
                        self::VALID_TYPE,
                        ['valid' => PARAM_STRING, 'current' => gettype($this->$propName)]
                    );
                } elseif ($type == PARAM_INT && ($this->$propName != intval($this->$propName))) {
                    $this->_setValidateError(
                        get_class($this) . '->' . $propName,
                        self::VALID_TYPE,
                        ['valid' => PARAM_INT, 'current' => gettype($this->$propName)]
                    );
                } elseif ($type == PARAM_FLOAT && !is_numeric($this->$propName)) {
                    $this->_setValidateError(
                        get_class($this) . '->' . $propName,
                        self::VALID_TYPE,
                        ['valid' => PARAM_FLOAT, 'current' => gettype($this->$propName)]
                    );
                } elseif ($type == PARAM_BOOL && ($this->$propName != 0 && $this->$propName != 1)) {
                    $this->_setValidateError(
                        get_class($this) . '->' . $propName,
                        self::VALID_TYPE,
                        ['valid' => PARAM_BOOL, 'current' => $this->$propName]
                    );
                } elseif ($type == PARAM_DATE && !isDate($this->$propName)) {
                    $this->_setValidateError(
                        get_class($this) . '->' . $propName,
                        self::VALID_TYPE,
                        ['valid' => PARAM_DATE, 'current' => $this->$propName]
                    );
                } elseif ($type == PARAM_TIME && !isTime($this->$propName)) {
                    $this->_setValidateError(
                        get_class($this) . '->' . $propName,
                        self::VALID_TYPE,
                        ['valid' => PARAM_TIME, 'current' => $this->$propName]
                    );
                }
                elseif ($type == PARAM_DATETIME && !isDatetime($this->$propName)) {
                    $this->_setValidateError(
                        get_class($this) . '->' . $propName,
                        self::VALID_TYPE,
                        ['valid' => PARAM_DATETIME, 'current' => $this->$propName]
                    );
                }
            }

            //check length
            if (!is_null($length) && $type === PARAM_STRING) {
                if (mb_strlen($this->$propName) != $length) {
                    $this->_setValidateError(
                        get_class($this) . '->' . $propName,
                        self::VALID_LENGTH,
                        ['valid' => $length, 'current' => mb_strlen($this->$propName)]
                    );
                }
            }

            //check maxlength
            if (!is_null($maxLength) && $type === PARAM_STRING) {
                if (mb_strlen($this->$propName) > $maxLength) {
                    $this->_setValidateError(
                        get_class($this) . '->' . $propName,
                        self::VALID_MAX_LENGTH,
                        ['valid' => $maxLength, 'current' => mb_strlen($this->$propName)]
                    );
                }
            }

            //check minlength
            if (!is_null($minLength) && $type === PARAM_STRING) {
                if (mb_strlen($this->$propName) < $minLength) {
                    $this->_setValidateError(
                        get_class($this) . '->' . $propName,
                        self::VALID_MIN_LENGTH,
                        ['valid' => $minLength, 'current' => mb_strlen($this->$propName)]
                    );
                }
            }

            //check max val
            if (!is_null($maxVal) && in_array($type, [PARAM_INT, PARAM_FLOAT])) {
                if ($this->$propName > $maxVal) {
                    $this->_setValidateError(
                        get_class($this) . '->' . $propName,
                        self::VALID_MAX_VALUE,
                        ['valid' => $maxVal, 'current' => $this->$propName]
                    );
                }
            }

            //check min val
            if (!is_null($minVal) && in_array($type, [PARAM_INT, PARAM_FLOAT])) {
                if ($this->$propName < $minVal) {
                    $this->_setValidateError(
                        get_class($this) . '->' . $propName,
                        self::VALID_MIN_VALUE,
                        ['valid' => $minVal, 'current' => $this->$propName]
                    );
                }
            }
        } //end schema foreach

        return empty($this->_getValidateErrors()) && empty($this->_getValidateErrorsStr());
    }


    /**
     * @param string $property
     * @param string $errorType
     * @param array $errorData
     */
    public function _setValidateError(string $property, string $errorType, array $errorData)
    {
        $this->_validateErrors[$property][$errorType] = $errorData;
    }


    /**
     * @param string $string
     */
    public function _setValidateErrorStr(string $string)
    {
        $this->_validateErrorsStr[] = $string;
    }


    /**
     * @return array
     */
    public function _getValidateErrors() : array
    {
        return $this->_validateErrors;
    }


    /**
     * @return string
     */
    public function _getValidateErrorsStr()
    {
        $returnStr = '';
        foreach ($this->_validateErrorsStr as $error) {
            $returnStr .= $error . PHP_EOL;
        }
        return $returnStr;
    }

    /**
     * @return array
     */
    public function getForbiddenRows() : array
    {
        return [];
    }

    /**
     * @return Orm
     */
    public function queryBuilder() : Orm
    {
        if (is_null($this->orm())) {
            $this->orm(new Orm($this));
        }
        return $this->orm();
    }


    /**
     * @return Orm
     */
    public static function query() : Orm
    {
        $class = get_called_class();
        return new Orm((new $class));
    }


    /**
     * @return array
     */
    public function findAll() : array
    {
        return $this->queryBuilder()->findAll();
    }


    /**
     * @param int $id
     * @return self|null
     */
    public static function find(int $id) : ?self
    {
        return get_called_class()::query()
            ->where('id', '=', $id)
            ->find();
    }


    /**
     * Валидация и сохранение объекта в ЬД
     * при неудачной валидации объекта вернется NULL
     * а список ошибок можно посмотреть в $_validateErrors или $_validateErrorsStr
     *
     * @return $this|null
     */
    public function save()
    {
        if ($this->timestamps() === true) {
            $currentDatetime = date('Y-m-d H:i:s');
            $this->timemodified($currentDatetime);
            if (empty($this->id)) {
                $this->timecreated($currentDatetime);
            }
        }

        if ($this->_validateModel() === true) {
            $this->queryBuilder()->save($this);
            return $this;
        } else {
            foreach ($this->_getValidateErrors() as $propName => $errors) {
                foreach ($errors as $errorType => $errorData) {
                    switch ($errorType)
                    {
                        case self::VALID_REQUIRED:
                            $this->_validateErrorsStr[] .= 'Свойство ' . $propName . ' не может быть пустым';
                            break;

                        case self::VALID_TYPE:
                            $this->_validateErrorsStr[] .= 'Свойство ' . $propName . ' должно иметь тип: ' . $errorData['valid']
                                . '; текущий тип: ' . $errorData['current'];
                            break;

                        case self::VALID_LENGTH:
                            $this->_validateErrorsStr[] .= 'Свойство ' . $propName . ' должно иметь длинну ' . $errorData['valid']
                                . '; текущая длинна значения: ' . $errorData['current'];
                            break;

                        case self::VALID_MAX_LENGTH:
                            $this->_validateErrorsStr[] .= 'Длина значения свойства ' . $propName . ' не должна превышать ' . $errorData['valid']
                                . ' символов; текущая длина: ' . $errorData['current'];
                            break;

                        case self::VALID_MIN_LENGTH:
                            $this->_validateErrorsStr[] .= 'Длина значения свойства ' . $propName . ' должна быть более ' . $errorData['valid']
                                . ' символов; текущая длина: ' . $errorData['current'];
                            break;

                        case self::VALID_MAX_VALUE:
                            $this->_validateErrorsStr[] .= 'Значение свойства ' . $propName . ' не должно быть более ' . $errorData['valid']
                                . '; текущее значение: ' . $errorData['current'];
                            break;

                        case self::VALID_MIN_VALUE:
                            $this->_validateErrorsStr[] .= 'Значение свойства ' . $propName . ' должно быть более ' . $errorData['valid']
                                . '; текущее значение: ' . $errorData['current'];
                            break;

                        default:
                            $this->_validateErrorsStr[] .= 'Свойство ' > $propName . ' не прошло валидацию типа \'' . $errorType . '\'; должно быть: '
                                . $errorData['valid'] . '; текущее значение: ' . $errorData['current'];
                    }
                }
            }
            return null;
        }
    }


    /**
     * @return $this
     */
    public function delete()
    {
        if ($this->safeDelete() === false) {
            $this->queryBuilder()->delete($this);
        } else {
            $this->markAsDeleted();
        }
        return $this;
    }


    /**
     * @return $this
     */
    public function markAsDeleted()
    {
        if (!empty($this->id) && isset($this->deleted)) {
            $this->deleted(1);
            $this->save();
        }
        return $this;
    }


    /**
     * @return int
     */
    public function getCount() : int
    {
        return $this->queryBuilder()->getCount();
    }


    /**
     * Возвращает название таблицы для объекта
     *
     * @return string
     */
    public function getTableName() : string
    {
        if (method_exists($this, 'databaseTableName')) {
            return $this->databaseTableName();
        } else {
            return get_class($this);
        }
    }


    /**
     * Формирует из не пустых свойств объекта ассоциативный массив для конструктора запросов
     * а именно для метода save
     *
     * @return array
     */
    public function getObjectProperties() : array
    {
        $result = [];

        $Model = $this->getModel(); //Модель данного объекта если такая существует

        //ФОрмирование
        if (!is_null($Model)) {
            $modelProperties = collect(get_object_vars($Model));

            if ($modelProperties->has('id')) {
                $id = $modelProperties->get('id');
                $modelProperties->forget('id');
                $modelProperties = collect(['id' => $id] + $modelProperties->toArray());
            }

            foreach ($modelProperties as $propertyName => $propertyValue) {
                $value = $this->$propertyName;
                if (!is_object($value) && !is_array($value)) {
                    $result[$propertyName] = $value;
                }
            }
        } elseif (isset($this->tableRows) && is_array($this->tableRows)) {
            foreach ($this->tableRows as $propertyName) {
                if (!is_array($this->$propertyName) && !is_object($this->$propertyName)) {
                    $result[$propertyName] = $this->$propertyName;
                }
            }
        } else {
            $properties = collect(get_object_vars($this));

            if ($properties->has('id')) {
                $id = $properties->get('id');
                $properties->forget('id');
                $properties = collect(['id' => $id] + $properties->toArray());
            }

            foreach ($properties as $propertyName => $propertyValue) {
                if(!is_array($propertyValue) && !is_object($propertyValue)) {
                    $result[$propertyName] = $propertyValue;
                }
            }
        }

        foreach ($this->getForbiddenRows() as $row) {
            unset($result[$row]);
        }

        return $result;
    }


    /**
     * Поиск класса-модели для объекта
     * Данный метод адаптирован под немного другую систему но не стал удалять
     *
     * @return mixed | null
     */
    public function getModel()
    {
        $modelClassName = get_class($this) . '_Model';
        $Model = Core::factory($modelClassName);

        if (!is_null($Model)) {
            $properties = get_object_vars($Model);

            foreach ($properties as $propertyName => $propertyValue) {
                //Формирование названия сеттера для свойства
                if ($propertyName == 'id') {
                    $snakeCaseSetter = 'getId';
                    $camelCaseSetter = 'getId';
                } else {
                    $snakeCaseSetter = $propertyName;
                    $camelCaseSetter = toCamelCase($propertyName);
                }

                $value = $this->$propertyName;

                if (is_array($value) || is_object($value)) {
                    continue;
                }

                if (method_exists($Model, $snakeCaseSetter)) {
                    $Model->$snakeCaseSetter($value);
                } elseif (method_exists($Model, $camelCaseSetter)) {
                    $Model->$camelCaseSetter($value);
                }
            }
        }

        return $Model;
    }


    /**
     * Метод преобразования объекта в объект класса stdClass со значением всех свойств, объявленных в модели
     *
     * @param array $forbiddenProps
     * @return stdClass
     */
    public function toStd(array $forbiddenProps = [])
    {
        $std = new stdClass();
        $forbiddenProps = array_merge($forbiddenProps, ['childrenObjects', 'aEntityVars', '_validateErrors', '_validateErrorsStr']);
        foreach(get_object_vars($this) as $var => $value) {
            if (!in_array($var, $forbiddenProps)) {
                $std->$var = $value;
            }
        }
        return $std;
    }


    /**
     * Конвертирует, к примеру, "Structure_Item" в "structure_item"
     * Я знаю что этот метод реализован одной стандартной PHP-шной функцией mb_strtolower
     * но всё равно этот метод был создан с тем что в будущем формат преобразования немного изменится
     *
     * @param $inputName - название модели, которое необходимо отконвертировать
     * @return string - название модели без больших букв
     */
    protected function renameModelName($inputName) : string
    {
        $segments = explode('_', $inputName);
        $outputName = '';

        foreach ($segments as $segment) {
            if ($outputName == '') {
                $outputName .= lcfirst($segment);
            } else {
                $outputName .= '_' . lcfirst( $segment );
            }
        }

        return $outputName;
    }


    /**
     * Добавление дочерней сущьности в XML
     *
     * @param $obj
     * @param null $tag
     * @return $this
     */
    public function addEntity($obj, $tag = null)
    {
        if (is_object($obj)) {
            $entity = (clone $obj);
            if (!is_null($tag)) {
                if (method_exists($entity,  '_customTag')) {
                    $entity->_customTag($tag);
                } elseif (get_class($entity) == 'stdClass') {
                    $entity->_customTag = $tag;
                }
            }

            if ($this->_entityValue() == '') {
                $this->childrenObjects[] = $entity;
            }
        }

        return $this;
    }


    /**
     * Добавление массива дочерних сущьностей в XML
     *
     * @param $children
     * @param null $tags
     * @return $this
     */
    public function addEntities($children, $tags = null)
    {
        if (is_array($children) && count($children) > 0) {
            foreach ($children as $child) {
                if (is_object($child)) {
                    $this->addEntity($child, $tags);
                } elseif (!is_null($tags)) {
                    $this->addSimpleEntity($tags, $child);
                }
            }
        }

        return $this;
    }


    /**
     * Добавление простой дочерней сущьности в XML
     *
     * @param $name - название тэга
     * @param $value - значение
     * @return $this
     */
    public function addSimpleEntity($name, $value)
    {
        if (is_null($value)) {
            $value = '';
        }

        $this->addEntity(
            Core::factory('Core_Entity')
                ->_entityName($name)
                ->_entityValue($value)
        );

        return $this;
    }


    /**
     * Преобразование объекта в XML-сущьность
     * так же выполняется рекурсивное преобразование дочерних сущьностей
     *
     * @param $obj - объект, который необходимо преобразовать в XML-сущьность
     * @param $xmlObj - объект конечной XML-сущьности
     * @return DOMElement
     */
    public function createEntity($obj, DOMDocument $xmlObj)
    {
        $xml = $xmlObj;

        if (get_class($obj) == 'Core_Entity') {
            //Формирование простого тэга
            if (!is_null($obj->_entityValue())) {
                return $xml->createElement($obj->_entityName(), $obj->_entityValue());
            } else {
                $tagName = $obj->_entityName();
            }
        } else {
            if (method_exists($obj, '_customTag') && $obj->_customTag() != '') {
                $tagName = $obj->_customTag();
            } elseif (isset($obj->_customTag) && $obj->_customTag != '') {
                $tagName = $obj->_customTag;
            } else {
                $tagName = $this->renameModelName(get_class($obj));
            }
        }

        //Создание тэга
        try {
            $objTag = $xml->createElement($tagName);
            $objData = get_object_vars($obj);
        } catch (\Exception $exception) {
            dd($tagName);
        }

        //Преобразование объекта в XML сущьность
        foreach ($objData as $key => $val) {
            if (is_array($val) && $key != 'childrenObjects') {
                continue;
            }

            //Если совйство представляет из себя массив дочерних сущьностей
            if ($key == 'childrenObjects') {
                foreach ($val as $childObject) {
                    $objChildTag = $this->createEntity($childObject, $xml);
                    $objTag->appendChild($objChildTag);
                }
            } elseif (is_object($val)) {
                $objTag->appendChild($this->createEntity($val, $xml));
            } elseif ($val !== '' && !is_null($val)) {
                $objTag->appendChild($xml->createElement($key, htmlspecialchars(strval($val))));
            } elseif ($val === '' || is_null($val)) {
                $objTag->appendChild($xml->createElement($key, ''));
            }
        }

        return $objTag;
    }


    /**
     * Метод для парсинга сформированного XML файла указанным XSL шаблоном
     * и формирование/вывод конечного HTML-кода
     *
     * @param bool $isShowing - указатеь на то будет ли HTML код сразу выводиться на странице
     *                          либо метод вернет его в виде строки
     * @return string
     */
    public function show(bool $isShowing = true)
    {
        if ($this->xsl() == '') {
            exit ('Не указан путь к XSL шаблону');
        }

        $xmlText = '<?xml version="1.0" encoding="utf-8"?>
		<?xml-stylesheet type="text/xsl" href="' . $this->xsl() . '"?>';
        $xmlText .= '<' . $this->_entityName() . '></' . $this->_entityName() . '>';

        $xml = new DOMDocument();
        $xml->loadXML($xmlText);
        $rootTag = $xml->getElementsByTagName($this->_entityName())->item(0);

        foreach ($this->childrenObjects as $obj) {
            $rootTag->appendChild($this->createEntity($obj, $xml));
        }

        $logStorage = ROOT . '/log/xsl/';
        $xslRelativePath = explode('/xsl/musadm/', $this->xsl())[1] ?? '';
        $xslLogFileName = preg_replace('~/~', '_', $xslRelativePath);
        $xslLogFileName = preg_replace('"\.xsl$"', '.xml', $xslLogFileName);
        //$xml->save($logStorage . '/' . $xslLogFileName);

        // Объект стиля
        $xsl = new DOMDocument();
        $xsl->load($this->xsl());

        // Создание парсера
        $proc = new XSLTProcessor();

        // Подключение стиля к парсеру
        $proc->importStylesheet($xsl);

        // Обработка парсером исходного XML-документа
        $parsed = $proc->transformToXml($xml);

        // Вывод результирующего кода
        if ($isShowing == true) {
            echo $parsed;
        }

        return $parsed;
    }


    /**
     * Сеттер для идентификатора объекта
     * Данный метод желательно использовать лишь в крайних случаях
     *
     * @param int $id
     * @return $this
     */
    public function setId(int $id)
    {
        $this->id = $id;
        return $this;
    }


    /**
     * @return int
     */
    public function getId() : int
    {
        return intval($this->id);
    }


    /**
     * @param $id
     * @return $this|int
     */
    public function id($id = null)
    {
        if (!is_null($id)) {
            return $this->setId($id);
        } else {
            return $this->getId();
        }
    }


    /**
     * @param int $id
     * @return $this|null
     */
    public static function getById(int $id)
    {
        $calledClass = get_called_class();
        return (new $calledClass)
            ->queryBuilder()
            ->where('id', '=', $id)
            ->find();
    }


    /**
     * @return string|array
     */
    public function getPrimaryKeyName()
    {
        return 'id';
    }


    /**
     * @return int|array
     */
    public function getPrimaryKeyValue()
    {
        return $this->getId();
    }


    /**
     * @return bool
     */
    public function isExisting() : bool
    {
        if (is_string($this->getPrimaryKeyName())) {
            return $this->getId() > 0;
        } else {
            $query = self::query();
            foreach ($this->getPrimaryKeyValue() as $key => $value) {
                $query->where($key, '=', $value);
            }
            return $query->exists();
        }
    }


    /**
     * Указатель на наличие свойств $timecreated и $timemodified в таблице объекта
     * Если необходимо хранить значения даты создания и последнего сохранения объекта то данный метод необходимо
     * переопределить в модели объекта с возвращаемым логическим значнием - true
     *
     * @return bool
     */
    public function timestamps()
    {
        return false;
    }


    /**
     * Указатель на "мягкое"/полное удаление объекта из таблицы
     * при "мягком" удалении - объект сохраняется в таблице со значением deleted = 1
     * Для мягкого удаления объекта необходимо наличие столбца с названием deleted в таблице объекта, а также
     * переопределить данный метод в моделе объекта с возвращаемым логическим значением - true
     *
     * @return bool
     */
    public function safeDelete()
    {
        return false;
    }


    /**
     * Получение идентификатора модели
     *
     * @return int
     */
    public function _getModelId() : int
    {
        switch (get_class($this))
        {
            case 'User':        return MODEL_USER_ID;
            case 'Lid':         return MODEL_LID_ID;
            case 'Task':        return MODEL_TASK_ID;
            case 'Payment':     return MODEL_PAYMENT_ID;
            case 'Comment':     return MODEL_COMMENT_ID;
            case 'Certificate': return MODEL_CERTIFICATE_ID;
            default:            return MODEL_UNDEFINED;
        }
    }

}
