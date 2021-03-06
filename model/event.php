<?php
/**
 * Класс реализующий методы для работы с событиями ('Журнал событий')
 *
 * @author Kozurev Egor
 * @date 26.11.2018 14:59
 *
 * @version 20190328
 * @version 20190412
 * @version 20190811 - доработан метод getTemplateString для случаев добавления комментариев к лидам и пользователям
 *
 * Class Event
 */
class Event extends Event_Model
{
    /**
     * Тип формирования шаблона строки
     */
    const STRING_FULL =     'full';     //Строка начинается с фамилии и имени клиента/преподавателя
    const STRING_SHORT =    'short';    //Строка НЕ начинается с фамилии и имени клиента/преподавателя

    /**
     * @return string
     */
    public function getTypeName() : string
    {
        if ($this->typeId() > 0) {
            $EventType = Core::factory('Event_Type', $this->typeId());
            if (is_null($EventType)) {
                return 'неизвестно';
            } else {
                return lcfirst($EventType->title());
            }
        } else {
            return 'неизвестно';
        }
    }

    /**
     * Формирование строки текста события
     * Вероятно придется разделять формирование строки для клиета и менеджера
     *
     * @param string $type - принимает значение одной из констант с префиксом STRING_
     * @return string
     */
    public function getTemplateString(string $type = self::STRING_FULL) : string
    {
        if (!in_array($type, [self::STRING_SHORT, self::STRING_FULL])) {
            return 'Неверно указан тип формирования шаблона строки - ' . $type;
        }

        //Пока что всего 2 типа формирования шаблона и обходится конструкцией if/elseif
        //но на будущее лучше использовать тут конструкцию switch
        if ($type === self::STRING_FULL) {
            $str = $this->user_assignment_fio . '. ';
        } else {
            $str = '';
        }

        if (is_null($this->getData())) {
            return 'При сохранении события типа \'' . $this->getTypeName() . '\' произошла неизвестная ошибка';
        }

        switch ($this->typeId())
        {
            case self::SCHEDULE_APPEND_USER:
                $this->getData()->lesson->lesson_type == Schedule_Lesson::SCHEDULE_MAIN
                    ?   $str .= 'Основной график с '
                    :   $str .= 'Актуальный график на ' ;
                $insertDate = refactorDateFormat($this->getData()->lesson->insert_date);
                $timeStart = $this->getData()->lesson->time_from;
                return $str . $insertDate . ' в ' . substr($timeStart, 0, 5);

            case self::SCHEDULE_REMOVE_USER:
                $date = refactorDateFormat($this->getData()->removeDate);
                $this->getData()->lesson->lesson_type == Schedule_Lesson::SCHEDULE_MAIN
                    ?   $str .= 'Удален(а) из основного графика с ' . $date
                    :   $str .= 'Удален(а) из актуального графика ' . $date;
                return $str;

            case self::SCHEDULE_SET_ABSENT:
                $date = refactorDateFormat($this->getData()->absentDate);
                return 'Отсутствует ' . $date;

            case self::SCHEDULE_CREATE_ABSENT_PERIOD:
                $dateFrom = refactorDateFormat($this->getData()->period->date_from);
                $dateTo =   refactorDateFormat($this->getData()->period->date_to);
                return $str . 'Отсутствует с ' . $dateFrom . ' по ' . $dateTo;

            case self::SCHEDULE_CHANGE_TIME:
                $date =         refactorDateFormat($this->getData()->date);
                $oldTimeFrom =  refactorTimeFormat($this->getData()->lesson->time_from);
                $newTimeFrom =  refactorTimeFormat($this->getData()->new_time_from);
                return $str . ' Актуальный график изменился на ' . $date
                            . '; старое время ' . $oldTimeFrom
                            . ', новое время ' . $newTimeFrom . '.';

            case self::SCHEDULE_EDIT_ABSENT_PERIOD:
                $oldDateFrom =  refactorDateFormat($this->getData()->old_period->date_from);
                $oldDateTo =    refactorDateFormat($this->getData()->old_period->date_to);
                $newDateFrom =  refactorDateFormat($this->getData()->new_period->date_from);
                $newDateTo =    refactorDateFormat($this->getData()->new_period->date_to);
                return $str . 'Период отсутствия изменен с: ' . $oldDateFrom . ' - ' . $oldDateTo
                                                    . ' на: ' . $newDateFrom . ' - ' . $newDateTo;

            case self::SCHEDULE_APPEND_CONSULT:
                $Lesson =   $this->getData()->lesson;
                $timeFrom = refactorTimeFormat($Lesson->time_from);
                $timeTo =   refactorTimeFormat($Lesson->time_to);
                $str = 'Добавил(а) консультацию c ' . $timeFrom . ' по ' . $timeTo . '. ';
                if ($Lesson->client_id) {
                    $lidId = $this->getData()->lid->id;
                    $str .= "Лид <a href='#' class='info-by-id' data-model='Lid' data-id='".$lidId."'>№".$lidId."</a>";
                }
                return $str;

            case self::SCHEDULE_APPEND_PRIVATE:
                $lesson =   $this->getData()->lesson;
                $timeFrom = refactorTimeFormat($lesson->time_from);
                // $timeTo =   refactorTimeFormat($lesson->time_to);
                $str .= 'Частное занятие. ';
                $str .= $this->getData()->lesson->lesson_type == Schedule_Lesson::SCHEDULE_MAIN
                    ?   'Основной график с'
                    :   'Актуальный график на';
                $str .= ' ' . refactorDateFormat($this->getData()->lesson->insert_date) . ' в ' . refactorTimeFormat($timeFrom);
                return $str;

            case self::CLIENT_ARCHIVE:
                return $str . 'Добавлен(а) в архив';

            case self::CLIENT_UNARCHIVE:
                return $str . 'Восстановлен(а) из архива';

            case self::CLIENT_APPEND_COMMENT:
                return $str . 'Добавлен комментарий с текстом: ' . $this->getData()->comment->text;

            case self::PAYMENT_CHANGE_BALANCE:
                return $str . 'Внесение оплаты пользователю на сумму ' . $this->getData()->payment->value . ' руб.';

            case self::PAYMENT_HOST_COSTS:
                return 'Внесение хозрасходов на сумму ' . $this->getData()->payment->value . ' руб.';

            case self::PAYMENT_TEACHER_PAYMENT:
                return 'преп. ' . $this->user_assignment_fio . '. Выплата на сумму ' . $this->getData()->payment->value . ' руб.';

            case self::PAYMENT_APPEND_COMMENT:
                return 'Добавил(а) комментарий к платежу с текстом: \''. $this->getData()->comment->value .'\'';

            case self::TASK_CREATE:
                $id =   $this->getData()->note->task_id;
                $text = $this->getData()->note->text;
                return "Добавил(а) задачу 
                        <a href='#' class='info-by-id' data-model='Task' data-id='" . $id . "'>№" . $id . "</a> 
                        с комментарием: '" . $text . "'";

            case self::TASK_DONE:
                $id = $this->getData()->task_id;
                return "Закрыл(а) задачу 
                        <a href='#' class='info-by-id' data-model='Task' data-id='" . $id ."'>№" . $id . "</a>";

            case self::TASK_APPEND_COMMENT:
                $id =   $this->getData()->note->task_id;
                $text = $this->getData()->note->text;
                return "Добавил(а) комментарий к задаче 
                        <a href='#' class='info-by-id' data-model='Task' data-id='" . $id . "'>№" . $id . "</a> 
                        с текстом: '" . $text . "'";

            case self::TASK_CHANGE_DATE:
                $id =       $this->getData()->task_id;
                $newDate =  refactorDateFormat($this->getData()->new_date);
                $oldDate =  refactorDateFormat($this->getData()->old_date);
                return "Задача 
                        <a href='#' class='info-by-id' data-model='Task' data-id='" . $id . "'>№" . $id . "</a>. 
                        Изменение даты с " . $oldDate . " на " . $newDate;

            case self::LID_CREATE:
                $id =           $this->getData()->lid->id;
                $lidSurname =   $this->getData()->lid->surname;
                $lidName =      $this->getData()->lid->name;
                return "Добавил(а) лида 
                        <a href='#' class='info-by-id' data-model='Lid' data-id='" . $id . "'>№$id</a> $lidSurname $lidName";

            case self::LID_APPEND_COMMENT:
                $id = $this->getData()->lid->id;
                return "Добавил(а) комментарий к лиду 
                <a href='#' class='info-by-id' data-model='Lid' data-id='" . $id . "'>№$id</a> 
                с текстом '" . $this->getData()->comment->text . "'";

            case self::LID_CHANGE_DATE:
                $id =       $this->getData()->lid->id;
                $oldDate =  refactorDateFormat($this->getData()->old_date);
                $newDate =  refactorDateFormat($this->getData()->new_date);
                return "Лид 
                        <a href='#' class='info-by-id' data-model='Lid' data-id='" . $id . "'>№$id</a>. 
                        Изменение даты с " . $oldDate . " на " . $newDate;

            case self::CERTIFICATE_CREATE:
                $id =   $this->getData()->certificate->id;
                $num =  $this->getData()->certificate->number;
                return "Добавил(а) сертификат 
                        <a href='#' class='info-by-id' data-model='Certificate' data-id='" . $id . "'>№" . $num . "</a>";

            case self::CERTIFICATE_APPEND_COMMENT:
                $id =   $this->getData()->note->certificate_id;
                $num =  Core::factory('Certificate', $id)->number();
                return "Сертификат 
                        <a href='#' class='info-by-id' data-model='Certificate' data-id='" . $id . "'>№$num</a>. "
                        . $this->getData()->note->text;

            default: return 'Шаблон формирования сообщения для события типа ' . $this->type_id . ' отсутствует.';
        }
    }

    /**
     * @param null $obj
     * @return null|$this
     */
    public function save($obj = null)
    {
        Core::notify([&$this], 'before.Event.save');

        //Задание значение времени события
        if ($this->time === 0) {
            $this->time = time();
        }

        //Задание значений связанных с автором события - author_id & author_fio
        if ($this->authorId() === 0) {
            $CurrentUser = User_Auth::parentAuth();

            if (!is_null($CurrentUser)) {
                $this->author_id =  $CurrentUser->getId();
                $this->author_fio = $CurrentUser->surname() . ' ' . $CurrentUser->name();
                if($CurrentUser->patronymic() != '') {
                    $this->author_fio .= ' ' . $CurrentUser->patronymic();
                }
            }
        }

        //Конвертация дополнительных данных события в строку
        if (is_array($this->data) || is_object($this->data)) {
            try {
                $this->data = json_encode($this->data);
            } catch (Exception $e) {
                echo "<h2>Ошибка во время сохранения события: " . $e->getMessage() . "</h2>";
                return null;
            }
        }

        if (empty(parent::save())) {
            return null;
        }

        Core::notify([&$this], 'after.Event.save');

        return $this;
    }

    /**
     * @param null $obj
     * @return void
     */
    public function delete($obj = null)
    {
        Core::notify([&$this], 'beforeEventDelete');
        parent::delete();
        Core::notify([&$this], 'afterEventDelete');
    }

}