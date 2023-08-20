<?php
/**
 * Created by PhpStorm.
 * User: Kozurev Egor
 * Date: 23.07.2018
 * Time: 11:34
 */

class Schedule_Controller
{
    /**
     * id пользователя, для которого формируется расписание
     *
     * @var int|null
     */
    private ?int $userId = null;

    /**
     * Конкретная дата, для которой формируется расписание
     *
     * @var string|null
     */
    private ?string $date = null;

    /**
     * Начало временного периода расписания
     *
     * @var string|null
     */
    private ?string $periodFrom = null;

    /**
     * Конец временного периода расписания
     *
     * @var string|null
     */
    private ?string $periodTo = null;

    /**
     * Номер месяца для формирования расписания в виде календаря
     *
     * @var int|null
     */
    private ?int $calendarMonth = null;

    /**
     * Номер года для оформления расписания в виде календаря
     *
     * @var int|null
     */
    private ?int $calendarYear = null;

    /**
     * @param int $val
     * @return $this
     */
    public function userId(int $val) : self
    {
        $this->userId = $val;
        return $this;
    }

    /**
     * @param string $date
     * @return $this
     */
    public function setDate(string $date) : self
    {
        $this->date = $date;
        return $this;
    }

    public function unsetDate() : self
    {
        $this->date = null;
        return $this;
    }

    /**
     * @param string $from
     * @param string $to
     * @return $this
     */
    public function setPeriod(string $from, string $to) : self
    {
        $this->periodFrom = $from;
        $this->periodTo = $to;
        return $this;
    }

    /**
     * @return $this
     */
    public function unsetPeriod() : self
    {
        $this->periodFrom = null;
        $this->periodTo = null;
        return $this;
    }

    /**
     * Устанавливает временной промежуток на 6 недель
     * в формате календаря: все числа текущего месяца, а также 
     * конец прошлого месяца и начало следующего
     *
     * @param $month
     * @param $year
     * @return $this
     */
    public function setCalendarPeriod(int $month, int $year)
    {
        $this->calendarMonth = $month;
        $this->calendarYear = $year;

        if ($this->calendarMonth < 10) {
            $this->calendarMonth = '0' . $this->calendarMonth;
        }

        if ($this->calendarMonth == '01') {
            $prevYear = $this->calendarYear - 1;
            $prevMonth = 12;
        } else {
            $prevYear = $this->calendarYear;
            $prevMonth = $this->calendarMonth - 1;
            if ($prevMonth < 10) {
                $prevMonth = '0' . $prevMonth;
            }
        }

        $countPrevDays = date('t', strtotime($prevYear . '-' . $prevMonth . '-' . '01' ) );
        $dateStart = $this->calendarYear . '-' . $this->calendarMonth . '-01';
        $countDays = date('t', strtotime($dateStart));
        $firstDayNumber = date('N', strtotime($dateStart));
        $day = $countPrevDays - ($firstDayNumber - 2);
        $this->periodFrom = $prevYear . '-' . $prevMonth . '-' . $day;

        if (intval($this->calendarMonth) == 12) {
            $nextMonth = '01';
            $nextYear = $this->calendarYear + 1;
        } else {
            $nextMonth = intval($this->calendarMonth) + 1;
            if ($nextMonth < 10) {
                $nextMonth = '0' . $nextMonth;
            }
            $nextYear = $this->calendarYear;
        }

        $rest = 43 - $countDays - $firstDayNumber;
        if ($rest < 0) {
            $rest = 7 + $rest;
        } elseif($rest < 10) {
            $rest = '0' . $rest;
        }

        $this->periodTo = $nextYear . '-' . $nextMonth . '-' . $rest;
        return $this;
    }

    /**
     * @return array
     */
    public function getLessons() : array
    {
        $lessons = Schedule_Lesson::query();

        //Поиск по роли пользователя
        if ($this->userId) {
            $user = User_Controller::factory($this->userId);

            if ($user->groupId() == ROLE_TEACHER) {
                $lessons->where('teacher_id', '=', $this->userId);
            } elseif ($user->groupId() == ROLE_CLIENT) {
                $clientGroups = Schedule_Group_Assignment::query()
                    ->where('user_id', '=', $this->userId)
                    ->join('Schedule_Group as sg', 'sg.id = Schedule_Group_Assignment.group_id AND sg.type = ' . Schedule_Group::TYPE_CLIENTS)
                    ->findAll();

                $userGroups = [];
                foreach ($clientGroups as $group) {
                    $userGroups[] = $group->groupId();
                }

                $lessons
                    ->open()
                    ->open()
                        ->where('client_id', '=', $this->userId)
                        ->whereIn('type_id', [Schedule_Lesson::TYPE_INDIV, Schedule_Lesson::TYPE_PRIVATE])
                    ->close();

                if (count($userGroups) > 0) {
                    $lessons
                        ->open()
                            ->orWhereIn('client_id', $userGroups)
                            ->where('type_id', '=', Schedule_Lesson::TYPE_GROUP)
                        ->close();
                }

                $lessons->close();
            }
        }

        //Поиск по дате
        if ($this->date) {
            $this->unsetPeriod();
            $lessons
                ->open()
                    ->open()
                        ->where('insert_date', '=', $this->date)
                        ->where('lesson_type', '=', Schedule_Lesson::SCHEDULE_CURRENT)
                    ->close()
                    ->open()
                        ->orWhere('insert_date', '<=', $this->date)
                        ->where('lesson_type', '=', Schedule_Lesson::SCHEDULE_MAIN)
                        ->open()
                            ->where('delete_date', '>', $this->date)
                            ->orWhere('delete_date', 'IS', 'NULL')
                        ->close()
                    ->close()
                ->close();
        }

        //Поиск по заданному периоду
        if ($this->periodFrom && $this->periodTo) {
            $this->unsetDate();
            $lessons
                ->open()
                    ->open()
                        ->where('insert_date', '>=', $this->periodFrom)
                        ->where('insert_date', '<=', $this->periodTo )
                        ->where('lesson_type', '=', Schedule_Lesson::SCHEDULE_CURRENT)
                    ->close()
                    ->open()
                        ->orWhere('insert_date', '<=', $this->periodTo)
                        ->where('lesson_type', '=', Schedule_Lesson::SCHEDULE_MAIN)
                        ->open()
                            ->where('delete_date', '>', $this->periodFrom)
                            ->orWhere('delete_date', 'IS', 'NULL')
                        ->close()
                    ->close()
                ->close();
        }

        $lessons = $lessons->orderBy('time_from')->findAll();

        if ($this->date) {
            $lessons = $this->getLessonsFromArray($lessons, $this->date);
        }
        return $lessons;
    }

    /**
     * Отрисовка календаря на определенное кол-во недель
     *
     * @param int $weeksCount
     * @throws Exception
     */
    public function printCalendar2($weeksCount = 2)
    {
        $today = date('Y-m-d');
        $weeksStart = self::getNearestDateByName($this->date);
        $weeksEnd = date('Y-m-d', strtotime($weeksStart . ' +' . $weeksCount . ' week'));

        $user = User_Controller::factory($this->userId);
        $schedule = Schedule_Controller_Extended::getSchedule($user, $weeksStart, $weeksEnd);

        echo "<div class='table-responsive'><table class='table table-bordered' style='margin-top: 20px'>";
        echo "<tr class='header'>
                <th>Понедельник</th>
                <th>Вторник</th>
                <th>Среда</th>
                <th>Четверг</th>
                <th>Пятница</th>
                <th>Суббота</th>
                <th>Воскресенье</th>
            </tr>";

        $date = $weeksStart;
        while ($date != $weeksEnd) {
            $day = self::getDayFromSchedule($date, $schedule);

            $dayName = date('l', strtotime($date));
            if ($dayName == 'Monday') {
                echo '<tr>';
            }
            echo '<td>';
            echo "<span class='date'>" . refactorDateFormat($date, '.', 'short') . "</span>";

            if (!is_null($day)) {
                $prevAreaId = 0;
                foreach ($day->lessons as $i => $lesson) {
                    if ($lesson->areaId() !== $prevAreaId) {
                        echo '<hr/><b><span class="area">'.$lesson->getArea()->title().'</span></b><hr/>';
                        $prevAreaId = $lesson->areaId();
                    }
                    echo '<span class="time">'.refactorTimeFormat($lesson->timeFrom()). ' - ' . refactorTimeFormat($lesson->timeTo()) . '</span>';
                    if ($user->groupId() == ROLE_CLIENT) {
                        $teacher = $lesson->getTeacher();
                        $fio = $teacher->surname() . ' ' . $teacher->name();
                    } else {
                        $client = $lesson->getClient();
                        $fio = '';
                        if (!empty($client)) {
                            if ($client instanceof User) {
                                $fio = $client->surname();
                            } elseif ($client instanceof Schedule_Group) {
                                $fio = $client->title();
                            } elseif ($client instanceof Lid) {
                                $fio = 'Консультация ';
                                if (User_Auth::current()->isManagementStaff()) {
                                    $fio .= $client->number();
                                }
                            }
                        }
                    }
                    echo '<span class="teacher"> ' . $fio . '</span>';
                    if ($lesson->isOnline()) {
                        echo ' (Онлайн)';
                    }
                    if ($user->groupId() === ROLE_CLIENT
                        && Core_Access::instance()->hasCapability(Core_Access::SCHEDULE_EDIT)
                        && checkTimeForScheduleActions(User_Auth::current(), $date) && $date >= $today) {
                        echo
                            '<ul class="submenu">
                            <li>
                                <a href="#"></a>
                                <ul class="dropdown" data-date="'.$date.'" 
                                        data-id="'.$lesson->getId().'" data-type="'.$lesson->typeId().'">
                                    <li><a href="#" class="schedule_today_absent" data-page_type="client">Отсутствует сегодня</a></li>
                                </ul>
                            </li>
                        </ul>';
                    }
                    echo '<br/>';
                }
            }

            echo '</td>';
            if ($dayName == 'Sunday') {
                echo '</tr>';
            }

            $date = date('Y-m-d', strtotime($date . ' +1 day'));
        }

        echo '</table></div>';
    }

    /**
     * @param $date
     * @param $schedule
     * @return mixed|null
     */
    public static function getDayFromSchedule($date, $schedule)
    {
        foreach ($schedule as $day) {
            if ($day->date == $date) {
                return $day;
            }
        }
        return null;
    }

    /**
     * Поиск даты начала недели
     *
     * @param string $date
     * @param string $nearestDayName
     * @return false|string
     */
    public static function getNearestDateByName(string $date, string $nearestDayName = 'Monday')
    {
        while (date('l', strtotime($date)) != $nearestDayName) {
            $date = date('Y-m-d', strtotime($date . ' -1 day'));
        }
        return $date;
    }

    /**
     * Отрисовка календаря на полный текущий месяц
     */
    public function printCalendar()
    {
        $allLessons = $this->getLessons();

        echo "<div class='table-responsive'><table class='table table-bordered' style='margin-top: 20px'>";
        echo "<tr class='header'>
                <th>Понедельник</th>
                <th>Вторник</th>
                <th>Среда</th>
                <th>Четверг</th>
                <th>Пятница</th>
                <th>Суббота</th>
                <th>Воскресенье</th>
            </tr>";

        $dateStart = $this->calendarYear . $this->calendarMonth . '01';
        $firstDayNumber = date('N', strtotime($dateStart));
        $countDays = date('t', strtotime($dateStart));

        $index = 0;
        $table = [];

        //Дни предыдущего месяца
       if ($this->calendarMonth == '01') {
           $prevYear = $this->calendarYear - 1;
           $prevMonth = 12;
       } else {
           $prevYear = $this->calendarYear;
           $prevMonth = intval( $this->calendarMonth ) - 1;
           if ($prevMonth < 10) {
               $prevMonth = '0' . $prevMonth;
           }
       }

       $countPrevDays = date('t', strtotime($prevYear . '-' . $prevMonth . '-' . '01'));

       for ($i = 0; $i < $firstDayNumber - 1; $i++) {
           $day = $countPrevDays - ($firstDayNumber - $i - 2);
           $date = $prevYear . '-' . $prevMonth . '-' . $day;
           $lessons = $this->getLessonsFromArray($allLessons, $date);
           $table[$index]['date'] = $date;
           $table[$index]['lessons'] = $lessons;
           $index++;
       }

        //Дни текущего месяца
        $day = 0;
        for ($i = $firstDayNumber; $i < $countDays + $firstDayNumber; $i++) {
            $day = $day + 1;
            if ($day < 10) {
                $day = '0' . $day;
            }
            $date = $this->calendarYear . '-' . $this->calendarMonth . '-' . $day;
            $lessons = $this->getLessonsFromArray($allLessons, $date);

            $table[$index]['date'] = $date;
            $table[$index]['lessons'] = $lessons;
            $index++;
        }

        //Дни следующего месяца
        if (intval($this->calendarMonth) == 12) {
            $nextMonth = '01';
            $nextYear = $this->calendarYear + 1;
        } else {
            $nextMonth = intval($this->calendarMonth) + 1;
            if ($nextMonth < 10) {
                $nextMonth = '0' . $nextMonth;
            }
            $nextYear = $this->calendarYear;
        }

        $rest = 43 - $countDays - $firstDayNumber;
        if ($rest < 0) {
            $rest = 7 + $rest;
        }

        for ($i = 1; $i <= $rest; $i++) {
            if ($i < 10 ) {
                $day = '0' . $i;
            } else {
                $day = $i;
            }

            $date = $nextYear . '-' . $nextMonth . '-' . $day;
            $lessons = $this->getLessonsFromArray($allLessons, $date);

            $table[$i+$index-1]['date'] = $date;
            $table[$i+$index-1]['lessons'] = $lessons;
        }

        $today = date('Y-m-d');

        for ($i = 0; $i < 42; $i++) {
            if ($i + 1 % 7 == 1) {
                echo "<tr>";
            }

            if ($today === $table[$i]['date']) {
                echo "<td style='background-color: #75c181'>";
            } else {
                echo "<td>";
            }

            echo "<span class='date'>" . refactorDateFormat($table[$i]['date'], '.', 'short') . "</span>";

            //Поиск филиалов в которых проводяться занятия
            if (count($table[$i]['lessons']) > 0) {
                $areasIds = [];
                foreach ($table[$i]['lessons'] as $lesson) {
                    $areasIds[] = $lesson->areaId();
                }

                $areas = Schedule_Area::query()
                    ->whereIn('id', $areasIds)
                    ->findAll();

                foreach ($areas as $area) {
                    $areas[$area->getId()] = clone $area;
                }
            }

            if (count($table[$i]['lessons']) > 0) {
                $lastAreaId = $table[$i]['lessons'][0]->areaId();
                echo "<hr/><b><span class='area'>". Core_Array::getValue($areas ?? [], $lastAreaId, null)->title() ."</span></b><hr/>";

                /** @var Schedule_Lesson $lesson */
                foreach ($table[$i]['lessons'] as $lesson) {
                    if ($lesson->areaId() != $lastAreaId) {
                        echo "<hr/><b><span class='area'>". Core_Array::getValue($areas ?? [], $lesson->areaId(), null)->title() ."</span></b><hr/>";
                    }

                    if ($today === $table[$i]['date'] ) {
                        $teacherLessons[] = $lesson;
                    }

                    echo "<span class='time'>" . refactorTimeFormat($lesson->timeFrom()) . " - " . refactorTimeFormat($lesson->timeTo()) . "</span>";

                    if (User_Controller::factory($this->userId)->groupId() == ROLE_CLIENT) {
                        if ($lesson->typeId() == Schedule_Lesson::TYPE_GROUP) {
                            $teacherFIO = $lesson->getGroup()->title();
                        } else {
                            $teacher = $lesson->getTeacher();
                            $teacherFIO = $teacher->getFio();
                        }
                        echo "<span class=\"teacher\"> $teacherFIO</span>";
                    } else {
                        if ($lesson->typeId() == Schedule_Lesson::TYPE_INDIV) {
                            $clientFio = $lesson->getClient()->surname();
                        } elseif ($lesson->typeId() == Schedule_Lesson::TYPE_GROUP) {
                            $clientFio = $lesson->getClient()->title();
                        } else {
                            $clientFio = 'Консультация';
                        }
                        echo "<span class=\"teacher\"> $clientFio</span>";
                    }
                    if ($lesson->isOnline()) {
                        echo ' (Онлайн)';
                    }

                    //Кнопка отмены занятия
                    // $tomorrow = date('Y-m-d', strtotime(date('Y-m-d') . ' +1 day'));
                    // $endDayTime = Property_Controller::factoryByTag('schedule_edit_time_end')->getValues(User_Auth::current()->getDirector())[0]->value();
                    if (Core_Access::instance()->hasCapability(Core_Access::SCHEDULE_EDIT)
                        && checkTimeForScheduleActions(User_Auth::current(), $table[$i]['date'])) {
                        // && ($table[$i]['date'] > $tomorrow || $table[$i]['date'] == $tomorrow && date('H:i:s') < $endDayTime)) {
                        echo
                        '<ul class="submenu">
                            <li>
                                <a href="#"></a>
                                <ul class="dropdown" data-date="'.$table[$i]['date'].'" 
                                        data-id="'.$lesson->getId().'" data-type="'.$lesson->typeId().'">
                                    <li><a href="#" class="schedule_today_absent">Отсутствует сегодня</a></li>
                                </ul>
                            </li>
                        </ul>';
                    }

                    echo "<br/>";
                    $lastAreaId = $lesson->areaId();
                }
            }

            echo "</td>";
            if (($i + 1) % 7 == 0)   echo "</tr>";
        }
        echo "</table></div>";
    }

    /**
     * @param $arr
     * @param $date
     * @return array
     */
    private function getLessonsFromArray($arr, $date)
    {
        $output = [];
        /** @var Schedule_Lesson $lesson */
        foreach ($arr as $key => $lesson) {
            if ($lesson->lessonType() == Schedule_Lesson::SCHEDULE_CURRENT && $lesson->insertDate() == $date && !$lesson->isAbsent($date)) {
                $output[] = $arr[$key];
            } elseif ($lesson->lessonType() == Schedule_Lesson::SCHEDULE_MAIN && !$lesson->isAbsent($date)) {
                $dayName = date('l', strtotime($date));

                if (strtotime($lesson->insertDate()) <= strtotime($date) && ($lesson->deleteDate() == ''
                        || strtotime($lesson->deleteDate()) > strtotime($date)) && $lesson->dayName() == $dayName
                ) {
                    $temp = clone $lesson;
                    $temp->setRealTime($date);
                    $output[] = $temp;
                }
            }
        }

        sortByTime($output, 'timeFrom');
        return $output;
    }
}