<?php
/**
 * Модель занятия (урока)
 *
 * @author BadWolf
 * @date 24.04.2018 19:58
 * @version 20190401
 * Class Schedule_Lesson_Model
 */
class Schedule_Lesson_Model extends Core_Entity
{
    const TYPE_INDIV = 1;
    const TYPE_GROUP = 2;
    const TYPE_CONSULT = 3;
    const TYPE_GROUP_CONSULT = 4;
    const TYPE_PRIVATE = 5;

    const SCHEDULE_MAIN = 1;
    const SCHEDULE_CURRENT = 2;

    public $id;

    /**
     * Дата первого появления занятия в расписании для основгного графика
     * Дата проведения (первого и последнего) занятия если это актуальный график (разовое занятие)
     *
     * @var string (date format: Y-m-d)
     */
    public $insert_date;

    /**
     * Дата удаления занятия из расписания для основного графика
     * пока занятие не удалено - значение NULL
     * Для актуального графика значение остается всегда NULL
     *
     * @var string
     */
    public $delete_date;

    /**
     * Время начала занятия
     *
     * @var string (time format: 00:00:00)
     */
    public $time_from;

    /**
     * Время окончания занятия
     *
     * @var string (time format: 00:00:00)
     */
    public $time_to;

    /**
     * Название дня недели формата:
     *  $Date =  new DateTime($date);
     *  $dayName =  $Date->format("l");
     *
     * @var string
     */
    public $day_name;

    /**
     * id филиала в котором проводиться занятие
     *
     * @var int
     */
    public $area_id;

    /**
     * id класса в котором проводиться занятие
     *
     * @var int
     */
    public $class_id;

    /**
     * id пользователя (преподавателя)
     *
     * @var int
     */
    public $teacher_id;

    /**
     * id пользователя (клиента)
     * по умолчанию значение равно нулю (в случае консультации)
     *
     * @var int
     */
    public $client_id = 0;

    /**
     * Тип занятия:
     *     - 1: индивидуальное
     *     - 2: групповое
     *     - 3: консультация
     *
     * @var int
     */
    public $type_id;

    /**
     * Тип графика:
     *     - 1: основной график (повторяющееся занятие)
     *     - 2: актуальный график (разовое занятие)
     *
     * @var int
     */
    public $lesson_type;

    /**
     * Указатель на то онлайн это занятие или нет, привет от короновируса)
     *
     * @var int
     */
    public $is_online = 0;

    /**
     * @param string|null $insertDate
     * @return $this|string
     */
    public function insertDate(string $insertDate = null)
    {
        if (is_null($insertDate)) {
            return $this->insert_date;
        } else {
            $this->insert_date = $insertDate;
            return $this;
        }
    }


    /**
     * @param string|null $deleteDate
     * @return $this|string
     */
    public function deleteDate(string $deleteDate = null )
    {
        if (is_null($deleteDate)) {
            return $this->delete_date;
        } else {
            $this->delete_date = $deleteDate;
            return $this;
        }
    }


    /**
     * @param int|null $lessonType
     * @return $this|int
     */
    public function lessonType(int $lessonType = null)
    {
        if (is_null($lessonType)) {
            return intval($this->lesson_type);
        } else {
            $this->lesson_type = $lessonType;
            return $this;
        }
    }


    /**
     * @param string|null $timeFrom
     * @return $this|string
     */
    public function timeFrom(string $timeFrom = null)
    {
        if (is_null($timeFrom)) {
            return $this->time_from;
        } else {
            $this->time_from = $timeFrom;
            return $this;
        }
    }


    /**
     * @param string|null $timeTo
     * @return $this|string
     */
    public function timeTo(string $timeTo = null)
    {
        if (is_null($timeTo)) {
            return $this->time_to;
        } else {
            $this->time_to = $timeTo;
            return $this;
        }
    }


    /**
     * @param string|null $dayName
     * @return $this|string
     */
    public function dayName(string $dayName = null)
    {
        if (is_null($dayName)) {
            return $this->day_name;
        } else {
            $this->day_name = $dayName;
            return $this;
        }
    }


    /**
     * @param int|null $areaId
     * @return $this|int
     */
    public function areaId(int $areaId = null)
    {
        if (is_null($areaId)) {
            return intval($this->area_id);
        } else {
            $this->area_id = $areaId;
            return $this;
        }
    }


    /**
     * @param int|null $classId
     * @return $this|int
     */
    public function classId(int $classId = null)
    {
        if (is_null($classId)) {
            return intval($this->class_id);
        } else {
            $this->class_id = $classId;
            return $this;
        }
    }


    /**
     * @param int|null $teacherId
     * @return $this|int
     */
    public function teacherId(int $teacherId = null)
    {
        if (is_null($teacherId)) {
            return intval($this->teacher_id);
        } else {
            $this->teacher_id = $teacherId;
            return $this;
        }
    }


    /**
     * @param int|null $clientId
     * @return $this|int
     */
    public function clientId($clientId = null)
    {
        if (is_null($clientId)) {
            return intval($this->client_id);
        } else {
            $this->client_id = intval($clientId);
            return $this;
        }
    }


    /**
     * @param int|null $typeId
     * @return $this|int
     */
    public function typeId(int $typeId = null)
    {
        if (is_null($typeId)) {
            return intval($this->type_id);
        } else {
            $this->type_id = $typeId;
            return $this;
        }
    }


    /**
     * @param int|null $isOnline
     * @return $this|int
     */
    public function isOnline(int $isOnline = null)
    {
        if (is_null($isOnline)) {
            return intval($this->is_online);
        } else {
            $this->is_online = $isOnline;
            return $this;
        }
    }


    /**
     * @return array
     */
    public function schema() : array
    {
        return [
            'id' => [
                'required' => false,
                'type' => PARAM_INT
            ],
            'insert_date' => [
                'required' => true,
                'type' => PARAM_STRING,
                'minlength' => 10,
                'maxlength' => 10
            ],
//            'delete_date' => [
//                'required' => false,
//                'type' => PARAM_STRING,
//                'minlength' => 10,
//                'maxlength' => 10
//            ],
            'time_from' => [
                'required' => true,
                'type' => PARAM_STRING,
                'minlength' => 8,
                'maxlength' => 8
            ],
            'time_to' => [
                'required' => true,
                'type' => PARAM_STRING,
                'minlength' => 8,
                'maxlength' => 8
            ],
            'day_name' => [
                'required' => true,
                'type' => PARAM_STRING,
                'maxlength' => 255
            ],
            'area_id' => [
                'required' => true,
                'type' => PARAM_INT,
                'minval' => 1
            ],
            'class_id' => [
                'required' => true,
                'type' => PARAM_INT,
                'minval' => 1
            ],
            'teacher_id' => [
                'required' => true,
                'type' => PARAM_INT,
                'minval' => 1
            ],
            'client_id' => [
                'required' => true,
                'type' => PARAM_INT,
                'minval' => 0
            ],
            'type_id' => [
                'required' => true,
                'type' => PARAM_INT,
                'minval' => 1
            ],
            'lesson_type' => [
                'required' => true,
                'type' => PARAM_INT,
                'minval' => 1
            ],
            'is_online' => [
                'required' => false,
                'type' => PARAM_BOOL
            ]
        ];
    }

}