<?php
/**
 * @date 01.04.2020
 */

$userId = Core_Array::Get('userid', null, PARAM_INT);
is_null($userId)
    ?   $User = User_Auth::current()
    :   $User = User_Controller::factory($userId);
$userId = $User->getId();

$today = date('Y-m-d');
$date = Core_Array::Get('date', $today, PARAM_STRING);

$accessScheduleRead = Core_Access::instance()->hasCapability(Core_Access::SCHEDULE_READ_USER);
$accessReportRead =   Core_Access::instance()->hasCapability(Core_Access::SCHEDULE_REPORT_READ);
$accessReportCreate = Core_Access::instance()->hasCapability(Core_Access::SCHEDULE_REPORT_CREATE);
$accessReportDelete = Core_Access::instance()->hasCapability(Core_Access::SCHEDULE_REPORT_DELETE);
$accessPaymentRead =  Core_Access::instance()->hasCapability(Core_Access::PAYMENT_READ_TEACHER);
$accessPaymentCreate= Core_Access::instance()->hasCapability(Core_Access::PAYMENT_CREATE_TEACHER);
$accessPaymentEdit =  Core_Access::instance()->hasCapability(Core_Access::PAYMENT_EDIT_TEACHER);
$accessPaymentDelete= Core_Access::instance()->hasCapability(Core_Access::PAYMENT_DELETE_TEACHER);
$accessPaymentConfig= Core_Access::instance()->hasCapability(Core_Access::PAYMENT_CONFIG);
$accessAbsentRead =   Core_Access::instance()->hasCapability(Core_Access::SCHEDULE_ABSENT_READ);
$accessAbsentCreate = Core_Access::instance()->hasCapability(Core_Access::SCHEDULE_ABSENT_CREATE);
$accessAbsentEdit =   Core_Access::instance()->hasCapability(Core_Access::SCHEDULE_ABSENT_EDIT);
$accessAbsentDelete = Core_Access::instance()->hasCapability(Core_Access::SCHEDULE_ABSENT_DELETE);

$month = getMonth($date);
if (intval($month) < 10) {
    $month = '0' . $month;
}
$year = getYear($date);

if ($accessScheduleRead) {


    echo '<section class="section-bordered">';
    (new Schedule_Controller)
        ->userId($userId)
        ->setDate($date)
        //->setCalendarPeriod($month, $year)
        ->printCalendar2();
    echo '</section>';
}

echo '<section class="section-bordered">';

if ($accessReportRead) {
    $TeacherLessons = (new Schedule_Controller())
        ->userId($userId)
        ->unsetPeriod()
        ->setDate($date)
        ->getLessons();

    //Формирование таблицы с отметками о явке/неявке>>
    sortByTime($TeacherLessons, 'timeFrom');

    foreach ($TeacherLessons as $key => $Lesson) {
        $Lesson->timeFrom(refactorTimeFormat($Lesson->timeFrom()));
        $Lesson->timeTo(refactorTimeFormat($Lesson->timeTo()));
        $LessonReport = $Lesson->getReport($date);
        $LessonClient = $Lesson->getClient();

        if ($LessonClient instanceof Schedule_Group) {
            $Clients = $LessonClient->getClientList();
            if (!is_null($LessonReport)) {
                $LessonAttendances = $LessonReport->getAttendances();
                foreach ($LessonAttendances as $Attendance) {
                    foreach ($Clients as $Client) {
                        if ($Attendance->clientId() == $Client->getId()) {
                            $Client->addEntity($Attendance, 'attendance');
                        }
                    }
                }
            }
            $LessonClient->addEntities($Clients, 'client');
        } else {
            if (!is_null($LessonReport)) {
                $LessonAttendances = $LessonReport->getAttendances();
                if (count($LessonAttendances) > 0) {
                    $LessonClient->addEntity($LessonAttendances[0], 'attendance');
                }
            }
        }

        $Lesson->addSimpleEntity('is_reported', (int)$Lesson->isReported($date));
        $Lesson->addEntity($LessonReport, 'report');
        $Lesson->addEntity($LessonClient, 'client');
        $Lesson->addSimpleEntity('lesson_type', $Lesson->lessonType());
    }

    $output = Core::factory('Core_Entity')
        ->addSimpleEntity('date', refactorDateFormat($date))
        ->addSimpleEntity('real_date', $date)
        ->addEntity($User)
        ->addEntities($TeacherLessons, 'lesson');

    User::checkUserAccess(['groups' => [ROLE_ADMIN, ROLE_DIRECTOR]], User::parentAuth())
        ?   $isAdmin = 1
        :   $isAdmin = 0;

    $output
        ->addSimpleEntity('is_admin', $isAdmin)
        ->addSimpleEntity('access_report_create', (int)$accessReportCreate)
        ->addSimpleEntity('access_report_delete', (int)$accessReportDelete)
        ->xsl('musadm/schedule/teacher_table.xsl')
        ->show();
}

$dateFrom = substr($date, 0, 8) . '01';
$currentMonth = intval(substr($date, 5, 2));
$currentYear = intval(substr($date, 0, 4));
$countDays = cal_days_in_month(CAL_GREGORIAN, $currentMonth, $currentYear);

if ($currentMonth < 10) {
    $currentMonth = '0' . $currentMonth;
}

$dateTo = $currentYear . '-' . $currentMonth . '-' . $countDays;

$totalCount = Core::factory('Schedule_Lesson_Report')
    ->queryBuilder()
    ->where('teacher_id', '=', $User->getId())
    ->where('date', '>=', $dateFrom)
    ->where('date', '<=', $dateTo)
    ->where('type_id', '<>', 3)
    ->getCount();

$attendenceIndivCount = Core::factory('Schedule_Lesson_Report')
    ->queryBuilder()
    ->where('teacher_id', '=', $User->getId())
    ->where('date', '>=', $dateFrom)
    ->where('date', '<=', $dateTo)
    ->where('type_id', '=', 1)
    ->where('attendance', '=', 1)
    ->getCount();

$disAttendenceIndivCount = Core::factory('Schedule_Lesson_Report')
    ->queryBuilder()
    ->where('teacher_id', '=', $User->getId())
    ->where('date', '>=', $dateFrom)
    ->where('date', '<=', $dateTo)
    ->where('type_id', '=', 1)
    ->where('attendance', '=', 0)
    ->getCount();

$attendenceGroupCount = Core::factory('Schedule_Lesson_Report')
    ->queryBuilder()
    ->where('teacher_id', '=', $User->getId())
    ->where('date', '>=', $dateFrom)
    ->where('date', '<=', $dateTo)
    ->where('type_id', '=', 2)
    ->where('attendance', '=', 1)
    ->getCount();

$disAttendenceGroupCount = Core::factory('Schedule_Lesson_Report')
    ->queryBuilder()
    ->where('teacher_id', '=', $User->getId())
    ->where('date', '>=', $dateFrom)
    ->where('date', '<=', $dateTo)
    ->where('type_id', '=', 2)
    ->where('attendance', '=', 0)
    ->getCount();

echo "<h4>Общее число проведенных занятий в этом месяце: $totalCount</h4>";
echo "<h4>из них явки/неявки: $attendenceIndivCount / $disAttendenceIndivCount (индивидуальные), 
            $attendenceGroupCount / $disAttendenceGroupCount (групповые).</h4>";
echo '</section>';


/**
 * Подсчет сумм необходимых выплат преподавателю и того что уже выплачено
 * за текущий период (месяц)
 */
if ($accessPaymentRead) {
    $totalPayedSql = Core::factory('Orm')
        ->select('sum(value) AS payed')
        ->from('Payment')
        ->where('user', '=', $User->getId())
        ->where('type', '=', Payment::TYPE_TEACHER)
        ->where('datetime', '>=', $dateFrom)
        ->where('datetime', '<=', $dateTo)
        ->getQueryString();

    $totalAdditionalPayedSql = Core::factory('Orm')
        ->select('sum(value) AS payed')
        ->from('Payment')
        ->where('user', '=', $User->getId())
        ->where('type', '=', Payment::TYPE_BONUS_PAY)
        ->where('datetime', '>=', $dateFrom)
        ->where('datetime', '<=', $dateTo)
        ->getQueryString();

    $totalHaveToPaySql = Core::factory('Orm')
        ->select('sum(teacher_rate) AS total')
        ->from('Schedule_Lesson_Report')
        ->where('teacher_id', '=', $User->getId())
        ->where('date', '>=', $dateFrom)
        ->where('date', '<=', $dateTo)
        ->getQueryString();

    $totalAdditionalHaveToPaySql = Core::factory('Orm')
        ->select('sum(value) AS total')
        ->from('Payment')
        ->where('user', '=', $User->getId())
        ->where('type', '=', Payment::TYPE_BONUS_ADD)
        ->where('datetime', '>=', $dateFrom)
        ->where('datetime', '<=', $dateTo)
        ->getQueryString();

    $totalPayed = Core::factory('Orm')
        ->executeQuery($totalPayedSql)
        ->fetch();

    $totalHaveToPay = Core::factory('Orm')
        ->executeQuery($totalHaveToPaySql)
        ->fetch();

    $totalAdditionalPayed = Core::factory('Orm')
        ->executeQuery($totalAdditionalPayedSql)
        ->fetch();

    $totalAdditionalHaveToPay = Core::factory('Orm')
        ->executeQuery($totalAdditionalHaveToPaySql)
        ->fetch();
    $totalPayed = Core_Array::getValue($totalPayed, 'payed', 0, PARAM_INT);
    $totalHaveToPay = Core_Array::getValue($totalHaveToPay, "total", 0, PARAM_INT);
    $totalAdditionalPayed = Core_Array::getValue($totalAdditionalPayed, 'payed', 0, PARAM_INT);
    $totalAdditionalHaveToPay = Core_Array::getValue($totalAdditionalHaveToPay, "total", 0, PARAM_INT);
    $debt = $totalHaveToPay - $totalPayed;
    $debtAdditional = $totalAdditionalHaveToPay - $totalAdditionalPayed;

    //Формирование таблицы с выплатами>>
    $Payments = Core::factory('Payment')
        ->queryBuilder()
        ->addSelect('Payment_Type.title as payment_type')
        ->whereIn('type', [Payment::TYPE_TEACHER,Payment::TYPE_BONUS_PAY,Payment::TYPE_BONUS_ADD])
        ->join(
            ' Payment_Type',
            'Payment_Type.id = Payment.type')
        ->where('user', '=', $User->getId())
        ->orderBy('datetime', 'DESC')
        ->orderBy('id', 'DESC')
        ->findAll();


    $MonthesPayments = [];
    $prevMonth = 0;
    $index = 0;

    foreach ($Payments as $Payment) {
        if (getMonth($Payment->datetime()) != $prevMonth) {
            $monthName = getMonthName($Payment->datetime()) . ' ' . getYear($Payment->datetime());
            $index++;
            $prevMonth = getMonth($Payment->datetime());
            $MonthesPayments[$index] = Core::factory('Core_Entity')->_entityName('month');
            $MonthesPayments[$index]->addSimpleEntity('month_name', $monthName);

            /**
             * Подсчет общего числа выплат за месяц
             * @date 11.08.2019
             */
            //вычисление даты начала месяца, в котором был совершен платеж
            $paymentMonthNum = getMonth($Payment->datetime());
            $paymentMonth = $paymentMonthNum < 10
                ?   '0' . $paymentMonthNum
                :   $paymentMonthNum;
            $paymentYear = getYear($Payment->datetime());
            $paymentMonthStart = $paymentYear . '-' . $paymentMonth . '-01';

            //вычисление даты начала следующего месяца от того, в котором был совершен платеж
            if ($paymentMonthNum == 12) {
                $paymentNextMonth = '01';
                $paymentNextYear = $paymentYear + 1;
            } else {
                $paymentNextMonthNum = $paymentMonthNum + 1;
                $paymentNextMonth = $paymentNextMonthNum < 10
                    ?   '0' . $paymentNextMonthNum
                    :   $paymentNextMonthNum;
                $paymentNextYear = $paymentYear;
            }
            $paymentNextMonthStart = $paymentNextYear . '-' . $paymentNextMonth . '-01';

            $monthTotalPayed = Core::factory('Payment')
                ->queryBuilder()
                ->select('sum(value)', 'total')
                ->where('user', '=', $User->getId())
                ->where('type', '=',Payment::TYPE_TEACHER)
                ->where('datetime', '>=', $paymentMonthStart)
                ->where('datetime', '<', $paymentNextMonthStart)
                ->sum('value');
            $monthAdditionalAdd = Core::factory('Payment')
                ->queryBuilder()
                ->select('sum(value)', 'total')
                ->where('user', '=', $User->getId())
                ->where('type', '=',Payment::TYPE_BONUS_ADD)
                ->where('datetime', '>=', $paymentMonthStart)
                ->where('datetime', '<', $paymentNextMonthStart)
                ->sum('value');
            $monthAdditionalPayed = Core::factory('Payment')
                ->queryBuilder()
                ->select('sum(value)', 'total')
                ->where('user', '=', $User->getId())
                ->where('type', '=',Payment::TYPE_BONUS_PAY)
                ->where('datetime', '>=', $paymentMonthStart)
                ->where('datetime', '<', $paymentNextMonthStart)
                ->sum('value');

            $MonthesPayments[$index]->addSimpleEntity('month_total_pay', $monthTotalPayed);
            $MonthesPayments[$index]->addSimpleEntity('month_additional_pay', $monthAdditionalPayed);
            $MonthesPayments[$index]->addSimpleEntity('month_additional_add', $monthAdditionalAdd);
        }

        $Payment->datetime(date('d.m.Y', strtotime($Payment->datetime())));
        $MonthesPayments[$index]->addEntity($Payment);
    }

    //Проверка на авторизованность под видом текущего пользователя
    User_Auth::isAuthAs()
        ?   $isAdmin = 1
        :   $isAdmin = 0;

    //Проверка на авторизованность директора ? администратора под видом преподавателя
    User_Auth::parentAuth()->groupId() === ROLE_DIRECTOR || User_Auth::parentAuth()->superuser() == 1
        ?   $isDirector = 1
        :   $isDirector = 0;
    Core::factory('Core_Entity')
        ->addEntities($MonthesPayments)
        ->addSimpleEntity('userid', $User->getId())
        ->addSimpleEntity('is_admin', $isAdmin)
        ->addSimpleEntity('is_director', $isDirector)
        ->addSimpleEntity('access_payment_create', (int)$accessPaymentCreate)
        ->addSimpleEntity('access_payment_edit', (int)$accessPaymentEdit)
        ->addSimpleEntity('access_payment_delete', (int)$accessPaymentDelete)
        ->addSimpleEntity('date', date('Y-m-d'))
        ->addSimpleEntity('debt', $debt)
        ->addSimpleEntity('debtAdditional', $debtAdditional)
        ->addSimpleEntity('total-payed', $totalPayed)
        ->addSimpleEntity('totalAdditionalPayed', $totalAdditionalPayed)
        ->xsl('musadm/finances/teacher_payments.xsl')
        ->show();
}

//Периоды отсутствия преподавателя
if ($accessAbsentRead) {
    $AbsentPeriods = Core::factory('Schedule_Absent')
        ->queryBuilder()
        ->where('type_id', '=', 1)
        ->where('date_to', '>=', date('Y-m-d'))
        ->where('object_id', '=', $User->getId())
        ->findAll();

    foreach ($AbsentPeriods as $Period) {
        $Period->refactoredDateFrom = date('d.m.y', strtotime($Period->dateFrom()));
        $Period->refactoredDateTo =   date('d.m.y', strtotime($Period->dateTo()));
        $Period->refactoredTimeFrom = substr($Period->timeFrom(), 0, 5);
        $Period->refactoredTimeTo =   substr($Period->timeTo(), 0, 5);
    }

    Core::factory('Core_Entity')
        ->addEntity($User)
        ->addEntities($AbsentPeriods)
        ->addSimpleEntity('userId', $User->getId())
        ->addSimpleEntity('access_absent_create', intval($accessAbsentCreate))
        ->addSimpleEntity('access_absent_edit', intval($accessAbsentEdit))
        ->addSimpleEntity('access_absent_delete', intval($accessAbsentDelete))
        ->xsl('musadm/schedule/teacher_absent.xsl')
        ->show();
}

//Таблица с настройками тарифов преподавателя
if ($accessPaymentConfig) {
    //Общие значения
    $Director = User::current()->getDirector();

    $TeacherRateDefaultIndiv =      Property_Controller::factoryByTag('teacher_rate_indiv_default');
    $TeacherRateDefaultGroup =      Property_Controller::factoryByTag('teacher_rate_group_default');
    $TeacherRateDefaultConsult =    Property_Controller::factoryByTag('teacher_rate_consult_default');
    $TeacherRateDefaultAbsent =     Property_Controller::factoryByTag('teacher_rate_absent_default');

    $teacherRateDefIndivValue =     $TeacherRateDefaultIndiv->getPropertyValues($Director)[0]->value();
    $teacherRateDefGroupValue =     $TeacherRateDefaultGroup->getPropertyValues($Director)[0]->value();
    $teacherRateDefConsultValue =   $TeacherRateDefaultConsult->getPropertyValues($Director)[0]->value();
    $teacherRateDefAbsentValue =    $TeacherRateDefaultAbsent->getPropertyValues($Director)[0]->value();

    //Индивидуальный или общий тариф у преподавателя
    $IsTeacherRateDefaultIndiv =    Property_Controller::factoryByTag('is_teacher_rate_default_indiv');
    $IsTeacherRateDefaultGroup =    Property_Controller::factoryByTag('is_teacher_rate_default_group');
    $IsTeacherRateDefaultConsult =  Property_Controller::factoryByTag('is_teacher_rate_default_consult');
    $IsTeacherRateDefaultAbsent =   Property_Controller::factoryByTag('is_teacher_rate_default_absent');

    $isTeacherRateDefIndivValue =   $IsTeacherRateDefaultIndiv->getPropertyValues($User)[0]->value();
    $isTeacherRateDefGroupValue =   $IsTeacherRateDefaultGroup->getPropertyValues($User)[0]->value();
    $isTeacherRateDefConsultValue = $IsTeacherRateDefaultConsult->getPropertyValues($User)[0]->value();
    $isTeacherRateDefAbsentValue =  $IsTeacherRateDefaultAbsent->getPropertyValues($User)[0]->value();

    //Значения индивидуальных тарифов преподавателя
    $TeacherRateIndiv =         Property_Controller::factoryByTag('teacher_rate_indiv');
    $TeacherRateGroup =         Property_Controller::factoryByTag('teacher_rate_group');
    $TeacherRateConsult =       Property_Controller::factoryByTag('teacher_rate_consult');
    $TeacherRateAbsent =        Property_Controller::factoryByTag('teacher_rate_absent');

    $teacherRateIndivValue =    $TeacherRateIndiv->getPropertyValues($User)[0]->value();
    $teacherRateGroupValue =    $TeacherRateGroup->getPropertyValues($User)[0]->value();
    $teacherRateConsultValue =  $TeacherRateConsult->getPropertyValues($User)[0]->value();
    $teacherRateAbsentValue =   $TeacherRateAbsent->getPropertyValues($User)[0]->value();

    $AbsentRateType = Core::factory('Property')->getByTagName('teacher_rate_type_absent_default');
    $absentRateType = $AbsentRateType->getPropertyValues($Director)[0]->value();

    Core::factory('Core_Entity')
        ->addSimpleEntity('teacher_id', $User->getId())
        ->addSimpleEntity('is_teacher_rate_default_indiv', $isTeacherRateDefIndivValue)
        ->addSimpleEntity('is_teacher_rate_default_gorup', $isTeacherRateDefGroupValue)
        ->addSimpleEntity('is_teacher_rate_default_consult', $isTeacherRateDefConsultValue)
        ->addSimpleEntity('is_teacher_rate_default_absent', $isTeacherRateDefAbsentValue)
        ->addSimpleEntity('teacher_rate_indiv', $teacherRateIndivValue)
        ->addSimpleEntity('teacher_rate_group', $teacherRateGroupValue)
        ->addSimpleEntity('teacher_rate_consult', $teacherRateConsultValue)
        ->addSimpleEntity('teacher_rate_absent', $teacherRateAbsentValue)
        ->addSimpleEntity('teacher_rate_indiv_default', $teacherRateDefIndivValue)
        ->addSimpleEntity('teacher_rate_gorup_default', $teacherRateDefGroupValue)
        ->addSimpleEntity('teacher_rate_consult_default', $teacherRateDefConsultValue)
        ->addSimpleEntity('teacher_rate_absent_default', $teacherRateDefAbsentValue)
        ->addSimpleEntity('teacher_rate_type_absent', $absentRateType)
        ->xsl('musadm/finances/teacher_rate_config.xsl')
        ->show();
}

if (User_Auth::current()->groupId() == ROLE_DIRECTOR or User_Auth::current()->groupId() == ROLE_MANAGER ) {
    //График работы преподавателя
    $MainSchedule = Core::factory('Schedule_Teacher')
        ->queryBuilder()
        ->where('teacher_id', '=', $User->getId())
        ->orderBy('time_from')
        ->findALl();

    foreach ($MainSchedule as $time) {
        $time->timeFrom = refactorTimeFormat($time->timeFrom());
        $time->timeTo = refactorTimeFormat($time->timeTo());
    }

    //Список учеников перподавателя
    $Teacher = User_Controller::factory($userId);
    $TeacherList = Core::factory('Property')->getByTagName('teachers');
    $teacherFio = $Teacher->surname() . ' ' . $Teacher->name();
    $TeacherProperty = Core::factory('Property_List_Values')
        ->queryBuilder()
        ->where('property_id', '=', $TeacherList->getId())
        ->where('value', '=', $teacherFio)
        ->find();

    $UserController =  new User_Controller_Extended(User_Auth::current());
    $UserController->appendAddFilter($TeacherList->getId(),'=',$TeacherProperty->getId());
    $Users = $UserController->getUsers();

    Core::factory('Core_Entity')
        ->addEntity($User)
        ->addEntities($MainSchedule)
        ->addEntities($Users,'clients')
        ->addSimpleEntity('property_id',$TeacherList->getId())
        ->addSimpleEntity('user_group',User_Auth::current()->groupId())
        ->addSimpleEntity('value_id',$TeacherProperty->getId())
        ->xsl('musadm/schedule/teacher_time.xsl')
        ->show();
}