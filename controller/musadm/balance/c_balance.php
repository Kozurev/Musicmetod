<?php
/**
 * Created by PhpStorm.
 *
 * @author BadWolf
 * @date 19.04.2018 23:18
 * @version 20190412
 * @version 20210406
 */

use Model\User\User_Client;

//Пользователь под которым происходила изначальная авторизация
$parentUser = User_Auth::parentAuth();

//id директора, которому принадлежит пользователь
$subordinated = User_Auth::current()->getDirector()->getId();

//Указатель на авторизацию "под именем" клиента
$isAdmin = intval($parentUser->isManagementStaff());
$isDirector = intval($parentUser->isDirector());

$director = User_Auth::current()->getDirector();

//id клиента под которым авторизован менеджер/директор
$pageClientId = Core_Array::Get('userid', null, PARAM_INT);

//Получение объекта пользователя клиента
$user = is_null($pageClientId)
    ?   User_Client::current()
    :   User_Client::find($pageClientId);

$accessAbsentPeriodRead =   Core_Access::instance()->hasCapability(Core_Access::SCHEDULE_ABSENT_READ);
$accessAbsentPeriodCreate = Core_Access::instance()->hasCapability(Core_Access::SCHEDULE_ABSENT_CREATE);
$accessAbsentPeriodEdit =   Core_Access::instance()->hasCapability(Core_Access::SCHEDULE_ABSENT_EDIT);
$accessAbsentPeriodDelete = Core_Access::instance()->hasCapability(Core_Access::SCHEDULE_ABSENT_DELETE);
$accessScheduleCreate =     Core_Access::instance()->hasCapability(Core_Access::SCHEDULE_CREATE);
$accessScheduleLessonTime = Core_Access::instance()->hasCapability(Core_Access::SCHEDULE_LESSON_TIME);
$accessScheduleEdit =       Core_Access::instance()->hasCapability(Core_Access::SCHEDULE_EDIT);

$outputXml = new Core_Entity();

//Пользовательские примечания и дата последней авторизации
if ($accessAbsentPeriodCreate || $accessAbsentPeriodEdit || $accessScheduleEdit || $accessScheduleLessonTime) {
    $today = date('Y-m-d');
    $todayTime = strtotime($today);

    //Дата последней авторизации
    $lastEntryDate = User_Auth_Log::getLastDate($user);

    //Поурочная оплата
    $perLesson = Property_Controller::factoryByTag('per_lesson')->getValues($user)[0]->value();

    //Стоп лист для занятия
    $stopList = Property_Controller::factoryByTag('teacher_stop_list')->getValues($user)[0]->value();

    //id лида, из которого был создан клиент
    $prevLid = Property_Controller::factoryByTag('lid_before_client')->getValues($user)[0]->value();

    //Ссылка ВК
    $vkLink = trim(Property_Controller::factoryByTag('vk')->getValues($user)[0]->value());
    if (!empty($vkLink) && substr($vkLink, 0, 5) !== 'https') {
        $vkLink = 'https://' . $vkLink;
    }

    $absentPeriods = Schedule_Absent::query()
        ->where('object_id', '=', $user->getId())
        ->where('type_id', '=', Schedule_Lesson::TYPE_INDIV)
        ->where('date_to', '>=', $today)
        ->orderBy('date_from')
        ->findAll();

    //Поиск "текущего" периода отсутствия
    if (count($absentPeriods) > 0) {
        foreach ($absentPeriods as $absentPeriod) {
            if (strtotime($absentPeriod->dateFrom()) <= $todayTime && strtotime($absentPeriod->dateTo()) >= $todayTime) {
                $absentPeriod->current = 1;
            }
            $absentPeriod->dateFrom(refactorDateFormat($absentPeriod->dateFrom()));
            $absentPeriod->dateTo(refactorDateFormat($absentPeriod->dateTo()));
        }
    }

    //Ближайший день занятия
    $nearestLessons = Schedule_Controller_Extended::getSchedule($user, date('Y-m-d'), null, 1);
    if (!empty($nearestLessons)) {
        $nearestLessonXml = (new Core_Entity())->_entityName('nearest_lesson');
        $nearestLessonXml->addSimpleEntity('date', $nearestLessons[0]->date);
        $nearestLessonXml->addSimpleEntity('refactoredDate', refactorDateFormat($nearestLessons[0]->date));
        $tomorrow = date('Y-m-d', strtotime(date('Y-m-d') . ' +1 day'));
        $isCancellable = (int)checkTimeForScheduleActions($user, $nearestLessons[0]->date);
        $nearestLessonXml->addSimpleEntity('is_cancellable', $isCancellable);
        /** @var Schedule_Lesson $lesson */
        foreach ($nearestLessons[0]->lessons as $lesson) {
            $lessonStd = new stdClass();
            $teacher = $lesson->getTeacher();
            $lessonStd->id = $lesson->getId();
            $lessonStd->teacher = $teacher->getFio();
            $lessonStd->time_from = refactorTimeFormat($lesson->timeFrom());
            $lessonStd->time_to = refactorTimeFormat($lesson->timeTo());
            $nearestLessonXml->addEntity($lessonStd, 'lesson');
        }
        $outputXml->addEntity($nearestLessonXml, 'nearest_lesson');
    }

    $teachers = (new User_Controller_Extended($user))->getClientTeachers();

    //API токен для сервиса "Мои звонки"
    $myCallsToken = Property_Controller::factoryByTag('my_calls_token')->getValues(User_Auth::current()->getDirector())[0]->value();

    $outputXml
        ->addEntity($user, 'client')
        ->addSimpleEntity('entry', $lastEntryDate)
        ->addSimpleEntity('vk', $vkLink)
        ->addSimpleEntity('per_lesson', $perLesson)
        ->addSimpleEntity('stop_list', $stopList)
        ->addSimpleEntity('prev_lid', $prevLid)
        ->addEntity(User_Auth::current(), 'current_user')
        ->addSimpleEntity('my_calls_token', $myCallsToken)
        ->addEntities($absentPeriods, 'absent')
        ->addEntities($teachers, 'teachers')
        ->addSimpleEntity('access_absent_read', intval($accessAbsentPeriodRead))
        ->addSimpleEntity('access_absent_create', intval($accessAbsentPeriodCreate))
        ->addSimpleEntity('access_absent_edit', intval($accessAbsentPeriodEdit))
        ->addSimpleEntity('access_absent_delete', intval($accessAbsentPeriodDelete))
        ->addSimpleEntity('access_schedule_lesson_time', intval($accessScheduleLessonTime))
        ->addSimpleEntity('access_schedule_edit', intval($accessScheduleEdit));
}

global $CFG;
$outputXml
    ->addSimpleEntity('wwwroot', $CFG->wwwroot)
    ->addEntity($user)
    ->addEntity($user->getBalance())
    ->addSimpleEntity('is_admin', $isAdmin)
    ->addSimpleEntity('is_director', $isDirector)
    ->xsl('musadm/users/balance/balance.xsl')
    ->addSimpleEntity(
        'access_create_payment',
        (int)Core_Access::instance()->hasCapability(Core_Access::PAYMENT_CREATE_CLIENT)
    )
    ->addSimpleEntity(
        'access_buy_tariff',
        (int)Core_Access::instance()->hasCapability(Core_Access::PAYMENT_TARIF_BUY)
    )
    ->addSimpleEntity('access_schedule_absent_read', $accessAbsentPeriodRead)
    ->addSimpleEntity('access_schedule_absent_create', $accessAbsentPeriodCreate)
    ->addSimpleEntity('access_schedule_absent_edit', $accessAbsentPeriodEdit)
    ->addSimpleEntity('access_schedule_absent_delete', $accessAbsentPeriodDelete)
    ->addSimpleEntity(
        'access_user_edit_lessons',
        (int)Core_Access::instance()->hasCapability(Core_Access::USER_EDIT_LESSONS)
    )
    ->show();

//Формирование таблицы расписания для клиентов
if ($user->isClient() && Core_Access::instance()->hasCapability(Core_Access::SCHEDULE_READ_USER)) {
?>
    <input type="hidden" id="userid" value="<?=$user->getId()?>" />

    <section class="user-schedule section-bordered">
    <?php
    (new Schedule_Controller)
        ->userId($user->getId())
        ->setDate(date('Y-m-d'))
        ->printCalendar2();
    ?>
    </section>
    <?php
}

//Блок статистики посещаемости
if (Core_Access::instance()->hasCapability(Core_Access::SCHEDULE_REPORT_READ)) {
    $reportsQuery = Schedule_Lesson_Report::query()
        ->select(['Schedule_Lesson_Report.id', 'attendance', 'date', 'lesson_id', 'client_id',
            'surname', 'name', 'client_rate', 'teacher_rate', 'total_rate', 'type_id'])
        ->leftJoin('User AS usr', 'usr.id = teacher_id')
        ->orderBy('date', 'DESC');

    $userGroupsIds = Schedule_Group_Assignment::query()
        ->where('user_id', '=', $user->getId())
        ->get()
        ->map(function(Schedule_Group_Assignment $groupAssignment): int {
            return $groupAssignment->groupId();
        })
        ->toArray();
    if (count($userGroupsIds) > 0) {
        $reportsQuery
            ->open()
                ->where('client_id', '=', $user->getId())
                ->whereIn('type_id', [Schedule_Lesson::TYPE_INDIV, Schedule_Lesson::TYPE_PRIVATE])
            ->close()
            ->open()
                ->orWhereIn('client_id', $userGroupsIds)
                ->where('type_id', '=', Schedule_Lesson::TYPE_GROUP)
            ->close();
    } else {
        $reportsQuery
            ->where('client_id', '=', $user->getId())
            ->whereIn('type_id', [Schedule_Lesson::TYPE_INDIV, Schedule_Lesson::TYPE_PRIVATE]);
    }

    $reports = $reportsQuery->findAll();
    /** @var Schedule_Lesson_Report $report */
    foreach ($reports as $report) {
        $reportLesson = $report->getLesson();
        if (!is_null($reportLesson)) {
            $reportLesson->setRealTime($report->date());
            $report->time_from = refactorTimeFormat($reportLesson->timeFrom());
            $report->time_to = refactorTimeFormat($reportLesson->timeTo());
            $report->date(refactorDateFormat($report->date()));

            if ($report->typeId() == Schedule_Lesson::TYPE_GROUP) {
                $group = Schedule_Group::find($report->clientId());
                if (!is_null($group)) {
                    $report->surname = $group->title();
                    $report->name = '';
                    $clientAttendance = $report->getClientAttendance($user->getId());
                    if (is_null($clientAttendance)) {
                        $report->attendance(0);
                    } else {
                        $report->attendance($clientAttendance->attendance());
                    }
                }
            }
        }
    }

    (new Core_Entity())
        ->addEntities($reports)
        ->addSimpleEntity('is_director', $isDirector)
        ->addSimpleEntity(
            'access_schedule_report_edit',
            (int)Core_Access::instance()->hasCapability(Core_Access::SCHEDULE_REPORT_EDIT)
        )
        ->xsl('musadm/users/balance/attendance_report.xsl')
        ->show();
}

//Платежи
if (Core_Access::instance()->hasCapability(Core_Access::PAYMENT_READ_CLIENT)) {
    $payments = Payment::query()
        ->orderBy('id', 'DESC')
        ->where('user', '=', $user->getId())
        ->open()
            ->open()
                ->where('status', '=', Payment::STATUS_PENDING)
                ->where('merchant_order_id', 'IS NOT', 'NULL')
            ->close()
            ->orWhere('status', '<>', Payment::STATUS_PENDING)
        ->close()
        ->findAll();

    /** @var Payment $payment */
    foreach ($payments as $payment) {
        $payment->addEntities($payment->getComments(), 'notes');
        $payment->datetime(refactorDateFormat($payment->datetime()));
    }

    (new Core_Entity)
        ->addSimpleEntity('is_admin', $isAdmin)
        ->addEntities($payments)
        ->addEntity($parentUser, 'parent_user')
        ->addSimpleEntity(
            'access_payment_edit_client',
            (int)Core_Access::instance()->hasCapability(Core_Access::PAYMENT_EDIT_CLIENT)
        )
        ->addSimpleEntity(
            'access_payment_delete_client',
            (int)Core_Access::instance()->hasCapability(Core_Access::PAYMENT_DELETE_CLIENT)
        )
        ->xsl('musadm/users/balance/payments.xsl')
        ->show();
}

//Новый раздел со списком событий
if ($isAdmin === 1) {
    //Считаем кол-во дней жизни за вычетом отвала
    $dateEnd = date('Y-m-d');
    $absenceDays = 0;
    $userActivityList = User_Activity::query()
        ->where('user_id', '=', $user->id())
        ->findAll();
    foreach ($userActivityList as $userActivity) {
        if (!is_null($userActivity->dumpDateEnd())) {
            $absenceDays += strtotime($userActivity->dumpDateEnd()) - strtotime($userActivity->dumpDateStart());
        } else {
            $dateEnd = $userActivity->dumpDateStart();
        }
    }
    $lifeDaysSeconds = strtotime($dateEnd) - strtotime($user->registerDate()) - $absenceDays;
    $lifeDays = intval($lifeDaysSeconds / (60*60*24));

    //Считаем кол-во уроков , которые отходил
    $countLessons = 0;
    /** @var Schedule_Lesson_Report $report */
    foreach ($reports ?? [] as $report) {
        if ($report->attendance()) {
            $countLessons++;
        }
    }

    //Считаем кол-во денег, которые принес в систему
    $money = 0;
    $moneyIn = Payment::query()
        ->where('user', '=', $user->getId())
        ->where('status', '=', Payment::STATUS_SUCCESS)
        ->where('type', '=', Payment::TYPE_INCOME)
        ->findAll();
    foreach ($moneyIn as $in) {
        $money += $in->value();
    }

    //Считаем кешбек
    $cashBack = 0;

    $cash = Payment::query()
        ->where('user','=', $user->getId())
        ->where('type','=', Payment::TYPE_CASHBACK)
        ->findAll();
    foreach ($cash as $in) {
        $cashBack += $in->value();
    }

    (new Core_Entity)
        ->addSimpleEntity('life_days', $lifeDays)
        ->addSimpleEntity('count_lesson', count($reports))
        ->addSimpleEntity('money', $money)
        ->addSimpleEntity('cashBack', $cashBack)
        ->xsl('musadm/users/balance/life_history.xsl')
        ->show();

    $eventsQuery = (new Event)->queryBuilder()
        ->where('user_assignment_id', '=', $user->getId())
        ->whereIn('type_id', [
            Event::SCHEDULE_APPEND_USER,
            Event::SCHEDULE_REMOVE_USER,
            Event::SCHEDULE_APPEND_PRIVATE,
            Event::SCHEDULE_CREATE_ABSENT_PERIOD,
            Event::SCHEDULE_CHANGE_TIME,
            Event::CLIENT_ARCHIVE,
            Event::CLIENT_UNARCHIVE,
            Event::CLIENT_APPEND_COMMENT,
            Event::SCHEDULE_EDIT_ABSENT_PERIOD,
            Event::SCHEDULE_SET_ABSENT
        ])
        ->orderBy('time', 'DESC');
    $events = $eventsQuery->findAll();

    //Поиск задачь, связанных с пользователем
    $taskController = new Task_Controller($user);
    $tasks = $taskController
        ->isPeriodControl(false)
        ->isLimitedAreasAccess(false)
        ->addSimpleEntity('taskAfterAction', 'balance')
        ->getTasks();

    $tasksPriorities = Task_Priority::query()
        ->orderBy('priority', 'DESC')
        ->findAll();

    $areas = (new Schedule_Area)->getList();

    foreach ($tasks as $task) {
        $event = new Event();
        $event->addEntity($task);
        $event->time(strtotime($task->date()));
        $events[] = $event;
    }

    /** @var Event $event */
    foreach ($events as $event) {
        $event->date = date('d.m.Y H:i', $event->time());
        if ($event->getId()) {
            $event->text = $event->getTemplateString(Event::STRING_SHORT);
        }
    }

    //Сортировка задач и прочих событий по дате
    for ($i = 0; $i < count($events) - 1; $i++) {
        for($j = 0; $j < count($events) - 1; $j++) {
            if($events[$j]->time() < $events[$j + 1]->time()) {
                $tmp = $events[$j];
                $events[$j] = $events[$j + 1];
                $events[$j + 1] = $tmp;
            }
        }
    }

    global $CFG;
    (new Core_Entity)
        ->addSimpleEntity('wwwroot', $CFG->rootdir)
        ->addEntity($user, 'user')
        ->addEntities($events)
        ->addEntities($tasksPriorities)
        ->addEntities($areas)
        ->addSimpleEntity('afterTaskAction', 'balance')
        ->addSimpleEntity(
            'access_user_append_comment',
            (int)Core_Access::instance()->hasCapability(Core_Access::USER_APPEND_COMMENT)
        )
        ->addSimpleEntity(
            'access_task_edit',
            (int)Core_Access::instance()->hasCapability(Core_Access::TASK_EDIT)
        )
        ->addSimpleEntity(
            'access_task_append_comment',
            (int)Core_Access::instance()->hasCapability(Core_Access::TASK_APPEND_COMMENT)
        )
        ->xsl('musadm/users/events.xsl')
        ->show();
}