<?php
/**
 * Обработчик ормирования контента раздела "Статистика"
 *
 * @author Bad Wolf
 * @date 03.06.2018 12:46
 * @version 20190221
 * @version 20190405
 * @version 20190414
 * @version 20190729
 */

$dateFormat = 'Y-m-d';
$date = date($dateFormat);
$dateFrom = Core_Array::Get('date_from', $date, PARAM_DATE);
$dateTo =   Core_Array::Get('date_to', $date, PARAM_DATE);
$areaId =   Core_Array::Get('area_id', 0, PARAM_INT);

$Director = User::current()->getDirector();
$subordinated = $Director->getId();
$userTableName = Core::factory('User')->getTableName();
$areasTable = Core::factory('Schedule_Area')->getTableName();
$areaAsgmTable = Core::factory('Schedule_Area_Assignment')->getTableName();

$Orm = new Orm();




//Статистика по балансу и урокам
$totalBalanceQuery = clone $Orm->clearQuery()
    ->select('sum(value)', 'sum')
    ->from('Property_Int', 'p')
    ->join($userTableName.' AS u',
         'u.id = p.object_id AND 
                    u.active = 1 AND 
                    u.subordinated = ' . $subordinated . ' AND 
                    u.group_id = ' . ROLE_CLIENT
    )
    ->where('p.model_name', '=', 'User')
    ->where('p.property_id', '=', 12);

if ($areaId !== 0) {
    $totalBalanceQuery->join(
        $areaAsgmTable.' as saa',
        'u.id = saa.model_id AND saa.model_name = \'User\' AND saa.area_id = ' . $areaId
    );
}

$Result = Orm::execute($totalBalanceQuery->getQueryString());
$Result = $Result->fetch();
$Result['sum'] != null
    ?   $sum = $Result['sum']
    :   $sum = 0;


//Кол-во оплаченных индивидуальных уроков
$indivLessonsQuery = clone $Orm->clearQuery()
    ->select('sum(value)', 'sum')
    ->from('Property_Int', 'p')
    ->join($userTableName.' AS u',
         'u.id = p.object_id AND 
                    u.active = 1 AND 
                    u.subordinated = ' . $subordinated . ' AND 
                    u.group_id = ' . ROLE_CLIENT
    )
    ->where('p.model_name', '=', 'User')
    ->where('p.property_id', '=', 13)
    ->where('value', '>', 0);

if ($areaId !== 0) {
    $indivLessonsQuery->join(
        $areaAsgmTable.' as saa',
        'u.id = saa.model_id AND saa.model_name = \'User\' AND saa.area_id = ' . $areaId
    );
}

$Result = Orm::execute($indivLessonsQuery->getQueryString());
$Result = $Result->fetch();
$Result['sum'] != null
    ?   $indiv_lessons_pos = $Result['sum']
    :   $indiv_lessons_pos = 0;


//Кол-во неоплаченных индивидуальных уроков
$indivLessonsDebtQuery = clone $Orm->clearQuery()
    ->select('sum(value)', 'sum')
    ->from('Property_Int', 'p')
    ->join($userTableName.' AS u',
         'u.id = p.object_id AND 
                    u.active = 1 AND 
                    u.subordinated = ' . $subordinated . ' AND 
                    u.group_id = ' . ROLE_CLIENT
    )
    ->where('p.model_name', '=', 'User')
    ->where('p.property_id', '=', 13)
    ->where('value', '<', 0);

if ($areaId !== 0) {
    $indivLessonsDebtQuery->join(
        $areaAsgmTable.' as saa',
        'u.id = saa.model_id AND saa.model_name = \'User\' AND saa.area_id = ' . $areaId
    );
}

$Result = Orm::execute($indivLessonsDebtQuery->getQueryString());
$Result = $Result->fetch();
$Result['sum'] != null
    ?   $indiv_lessons_neg = $Result['sum']
    :   $indiv_lessons_neg = 0;


//Кол-во оплаченных групповых уроков
$groupLessonsQuery = clone $Orm->clearQuery()
    ->select('sum(value)', 'sum')
    ->from('Property_Int', 'p')
    ->join($userTableName.' AS u',
         'u.id = p.object_id AND 
                    u.active = 1 AND 
                    u.subordinated = ' . $subordinated . ' AND 
                    u.group_id = ' . ROLE_CLIENT
    )
    ->where('p.model_name', '=', 'User')
    ->where('p.property_id', '=', 14)
    ->where('value', '>', 0);

if ($areaId !== 0) {
    $groupLessonsQuery->join(
        $areaAsgmTable.' as saa',
        'u.id = saa.model_id AND saa.model_name = \'User\' AND saa.area_id = ' . $areaId
    );
}

$Result = Orm::execute($groupLessonsQuery->getQueryString());
$Result = $Result->fetch();
$Result['sum'] != null
    ?   $group_lessons_pos = $Result['sum']
    :   $group_lessons_pos = 0;


//Кол-во неоплаченных груповых уроков
$groupLessonsDebtQuery = clone $Orm->clearQuery()
    ->select('sum(value)', 'sum')
    ->from('Property_Int', 'p')
    ->join($userTableName.' AS u',
         'u.id = p.object_id AND 
                    u.active = 1 AND 
                    u.subordinated = ' . $subordinated . ' AND 
                    u.group_id = ' . ROLE_CLIENT
    )
    ->where('property_id', '=', 14)
    ->where('value', '<', 0);

if ($areaId !== 0) {
    $groupLessonsDebtQuery->join(
        $areaAsgmTable.' as saa',
        'u.id = saa.model_id AND saa.model_name = \'User\' AND saa.area_id = ' . $areaId
    );
}

$Result = Orm::execute($groupLessonsDebtQuery->getQueryString());
$Result = $Result->fetch();
$Result['sum'] != null
    ?   $group_lessons_neg = $Result['sum']
    :   $group_lessons_neg = 0;


//Средний возраст
$birthYears = clone $Orm->clearQuery()
    ->select('value')
    ->from('Property_String', 'p')
    ->join($userTableName.' AS u',
         'u.id = p.object_id AND 
                    u.active = 1 AND 
                    u.subordinated = ' . $subordinated . ' AND 
                    u.group_id = ' . ROLE_CLIENT
    )
    ->where('property_id', '=', 28)
    ->where('value', '<>', '');

if ($areaId !== 0) {
    $birthYears->join(
        $areaAsgmTable.' as saa',
        'u.id = saa.model_id AND saa.model_name = \'User\' AND saa.area_id = ' . $areaId
    );
}

$birthYears = $birthYears->findAll();

$yearsSum = 0;
$formatYearsCount = 0;
foreach ($birthYears as $year) {
    if (mb_strlen($year->value) == 4) {
        $yearsSum += intval($year->value);
        $formatYearsCount++;
    }
}

if ($formatYearsCount > 0) {
    $avgYear = round($yearsSum / $formatYearsCount, 0);
    $avgAge = intval(date('Y')) - $avgYear;
} else {
    $avgAge = 0;
}


//Средняя медиана
$avgIndivCost = clone $Orm->clearQuery()
    ->select('avg(value)', 'value')
    ->from('Property_Int', 'p')
    ->join($userTableName.' AS u',
         'u.id = p.object_id AND 
                    u.active = 1 AND 
                    u.subordinated = ' . $subordinated . ' AND 
                    u.group_id = ' . ROLE_CLIENT
    )
    ->where('property_id', '=', 42)
    ->where('value', '>', 0);

$avgGroupCost = clone $Orm->clearQuery()
    ->select('avg(value)', 'value')
    ->from('Property_Int', 'p')
    ->join($userTableName.' AS u',
         'u.id = p.object_id AND 
                    u.active = 1 AND 
                    u.subordinated = ' . $subordinated . ' AND 
                    u.group_id = ' . ROLE_CLIENT
    )
    ->where('property_id', '=', 43)
    ->where('value', '>', 0);

if ($areaId !== 0) {
    $avgIndivCost->join(
        $areaAsgmTable.' as saa',
        'u.id = saa.model_id AND saa.model_name = \'User\' AND saa.area_id = ' . $areaId
    );
    $avgGroupCost->join(
        $areaAsgmTable.' as saa',
        'u.id = saa.model_id AND saa.model_name = \'User\' AND saa.area_id = ' . $areaId
    );
}

$avgIndivCost = $avgIndivCost->find();
$avgGroupCost = $avgGroupCost->find();
$avgIndivCost = is_object($avgIndivCost) && !is_null($avgIndivCost->value) ? round($avgIndivCost->value, 0) : 0;
$avgGroupCost = is_object($avgGroupCost) && !is_null($avgGroupCost->value) ? round($avgGroupCost->value, 0) : 0;


Core::factory('Core_Entity')
    ->addSimpleEntity('balance', $sum)
    ->addSimpleEntity('indiv_pos', $indiv_lessons_pos)
    ->addSimpleEntity('indiv_neg', $indiv_lessons_neg * -1)
    ->addSimpleEntity('group_pos', $group_lessons_pos)
    ->addSimpleEntity('group_neg', $group_lessons_neg * -1)
    ->addSimpleEntity('avgAge', $avgAge)
    ->addSimpleEntity('avgIndivMediana', $avgIndivCost)
    ->addSimpleEntity('avgGroupMediana', $avgGroupCost)
    ->xsl('musadm/statistic/balance.xsl')
    ->show();

//По просьбе убрана информация по лидам из статистики

///**
// * Статистика по лидам
// */
//Core::requireClass('Lid_Controller');
//$LidsOutput = Core::factory('Core_Entity');
//$totalCount = Lid_Controller::factory()
//    ->queryBuilder()
//    ->where('subordinated', '=', $subordinated);
//
//if ($dateFrom == $dateTo) {
//    $totalCount->where('control_date', '=', $dateFrom);
//} else {
//    $totalCount->where('control_date', '>=', $dateFrom);
//    $totalCount->where('control_date', '<=', $dateTo);
//}
//
//if ($areaId !== 0) {
//    $totalCount->where('area_id', '=', $areaId);
//}
//
//$totalCount = $totalCount->getCount();
//$Statuses = Core::factory('Lid_Status')
//    ->queryBuilder()
//    ->where('subordinated', '=', $subordinated)
//    ->orderBy('id', 'DESC')
//	->findAll();
//
//	if (count($Statuses) > 0) {
//		foreach ($Statuses as $status) {
//			$queryString = Core::factory('Orm')
//				->select('count(Lid.id)', 'count')
//				->from('Lid')
//                ->where('subordinated', '=', $subordinated)
//				->where('status_id', '=', $status->getId());
//
//            if ($dateFrom == $dateTo) {
//                $queryString->where('control_date', '=', $dateFrom);
//            } else {
//                $queryString->where('control_date', '>=', $dateFrom);
//                $queryString->where( 'control_date', '<=', $dateTo );
//            }
//            if ($areaId !== 0) {
//                $queryString->where('area_id', '=', $areaId);
//            }
//
//            $queryString = $queryString->getQueryString();
//			$Result = Core::factory('Orm')->executeQuery($queryString);
//
//			if ($Result != false) {
//				$Result = $Result->fetch();
//				$count = $Result['count'];
//				$totalCount == 0
//                    ?   $percents = 0
//                    :   $percents = round($count * 100 / $totalCount, 1);
//			} else {
//				$count = 0;
//				$percents = 0;
//			}
//
//			$status->addSimpleEntity('count', $count);
//			$status->addSimpleEntity('percents', round($percents, 2));
//			$LidsOutput->addEntity($status, 'status');
//		}
//	}
//
//$LidsOutput
//    ->addSimpleEntity('total', $totalCount)
//    ->xsl('musadm/statistic/lids.xsl')
//    ->show();




/**
 * Статистика по проведенным занятиям
 */
Core::factory('Schedule_Lesson');
$lessonReportsCount = Core::factory('Schedule_Lesson_Report')
    ->queryBuilder()
    ->where('Schedule_Lesson_Report.type_id', '<>', Schedule_Lesson::TYPE_CONSULT)
    ->leftJoin('User as u', 'u.id = teacher_id')
    ->where('u.subordinated', '=', $subordinated);

$attendanceCount = Core::factory('Schedule_Lesson_Report')
    ->queryBuilder()
    ->where('Schedule_Lesson_Report.type_id', '<>', Schedule_Lesson::TYPE_CONSULT)
    ->where('attendance', '=', 1)
    ->join('User as u', 'u.id = teacher_id')
    ->where('u.subordinated', '=', $subordinated);

if ($dateFrom == $dateTo) {
    $lessonReportsCount->where('date', '=', $dateFrom);
    $attendanceCount->where('date', '=', $dateFrom);
} else {
    $lessonReportsCount->where('date', '>=', $dateFrom);
    $lessonReportsCount->where('date', '<=', $dateTo);
    $attendanceCount->where('date', '>=', $dateFrom);
    $attendanceCount->where('date', '<=', $dateTo);
}

if ($areaId !== 0) {
    $lessonReportsCount->join(
        'Schedule_Lesson as lesson',
        'lesson.id = lesson_id AND lesson.area_id = ' . $areaId
    );
    $attendanceCount->join(
        'Schedule_Lesson as lesson',
        'lesson.id = lesson_id AND lesson.area_id = ' . $areaId
    );
}

$lessonReportsCount = $lessonReportsCount->getCount();
$attendanceCount = $attendanceCount->getCount();
if ($lessonReportsCount != 0) {
    $attendancePercent = $attendanceCount * 100 / $lessonReportsCount;
    $attendancePercent = intval($attendancePercent);
} else {
    $attendancePercent = 0;
}

//Кол-во дней за указанный промежуток
if ($dateFrom == $dateTo) {
    $countDaysInterval = 0;
} else {
    $countDaysInterval = (strtotime($dateTo) - strtotime($dateFrom)) / (60*60*24);
    $countDaysInterval = intval($countDaysInterval) + 1;
}

$countDaysInterval == 0
    ?   $lessonIndex = $attendanceCount
    :   $lessonIndex = round($attendanceCount / $countDaysInterval, 1);

Core::factory('Core_Entity')
    ->addSimpleEntity('day_index', $lessonIndex)
    ->addSimpleEntity('total_count', $lessonReportsCount)
    ->addSimpleEntity('attendance_count', $attendanceCount)
    ->addSimpleEntity('attendance_percent', $attendancePercent)
    ->xsl('musadm/statistic/lessons.xsl')
    ->show();

/**
 * Статистика по выплатам преподавателям
 */
$queryString = Core::factory('Orm')
    ->select('sum(value)', 'sum')
    ->from('Payment')
    ->where('type', '=', 3)
    ->where('Payment.subordinated', '=', $subordinated);

if ($areaId !== 0) {
    $queryString
        ->join('User as u', 'Payment.user = u.id')
        ->join(
            'Schedule_Area_Assignment as saa',
            'u.id = saa.model_id AND saa.model_name = \'User\' AND saa.area_id = ' . $areaId
        );
}

if ($dateFrom == $dateTo) {
    $queryString->where('datetime', '=', $dateFrom);
} else {
    $queryString->where('datetime', '>=', $dateFrom);
    $queryString->where('datetime', '<=', $dateTo);
}

$queryString = $queryString->getQueryString();
$Result = Core::factory('Orm')->executeQuery($queryString);
$Result = $Result->fetch();
if ($Result['sum'] == null) {
    $sum = 0;
} else {
    $sum = $Result['sum'];
}

Core::factory('Core_Entity')
    ->addSimpleEntity('total_sum', $sum)
    ->xsl('musadm/statistic/teacher_payments.xsl')
    ->show();

/**
 * Статистика по доходам, расходам и прибыли
 */
$finances = Core::factory('Schedule_Lesson_Report')
    ->queryBuilder()
    ->join('User AS u', 'teacher_id = u.id')
    ->where('u.subordinated', '=', $subordinated);

//Хозрасходы
$hostExpenses = Core::factory('Payment')
    ->queryBuilder()
    ->select('sum(Payment.value)', 'value')
    ->join('Payment_Type as t', 'Payment.type = t.id')
    ->where('t.subordinated', '=', $subordinated)
    ->where('Payment.subordinated', '=', $subordinated);

if ($areaId !== 0) {
    $finances->join(
        'Schedule_Lesson as lesson',
        'lesson.id = lesson_id AND lesson.area_id = ' . $areaId
    );
    $hostExpenses->where('area_id', '=', $areaId);
}

if ($dateFrom == $dateTo) {
    $finances->where('date', '=', $dateFrom);
    $hostExpenses->where('datetime', '=', $dateFrom);
} else {
    $finances->where('date', '>=', $dateFrom);
    $finances->where('date', '<=', $dateTo);
    $hostExpenses->where('datetime', '>=', $dateFrom);
    $hostExpenses->where('datetime', '<=', $dateTo);
}

$income =   clone $finances->select('sum(client_rate)', 'value');
$expenses = clone $finances->select('sum(teacher_rate)', 'value');
$profit =   clone $finances->select('sum(total_rate)', 'value');
$income =   $income->find()->value;
$expenses = $expenses->find()->value;
$profit =   $profit->find()->value;
$hostExpenses = $hostExpenses->find()->value();

if (is_null($income)) {
    $income = 0;
}
if (is_null($expenses)) {
    $expenses = 0;
}
if (is_null($profit)) {
    $profit = 0;
}
if (is_null($hostExpenses)) {
    $hostExpenses = 0;
}

Core::factory('Core_Entity')
    ->addSimpleEntity('income', $income)
    ->addSimpleEntity('expenses', $expenses)
    ->addSimpleEntity('profit', $profit)
    ->addSimpleEntity('host_expenses', $hostExpenses)
    ->xsl('musadm/statistic/lessons_income.xsl')
    ->show();




/**
 * Статистика по активным клиентам
 */


$userCount =  (new User())
    ->queryBuilder()
    ->where('group_id', '=', ROLE_CLIENT)
    ->where('register_date','<=',$dateTo)
    ->where('subordinated', '=', $subordinated)
    ->where('active', '=', 1)
    ->count();


$reportTableName = Core::factory('Schedule_Lesson_Report')->getTableName();

$userActiveCount =  (new User())
    ->queryBuilder()
    ->where('group_id', '=', ROLE_CLIENT)
    ->where('subordinated', '=', $subordinated)
    ->where('active', '=', 1)
    ->where('register_date','<=',$dateTo)
    ->join(
        $reportTableName . ' AS rep',
        'rep.client_id = User.id AND rep.attendance = 1 AND rep.date between "'.$dateFrom.'" AND "'.$dateTo.'" AND rep.type_id = 1'
    )
    ->groupBy('User.id')
    ->findAll();
Core::factory('Core_Entity')
    ->addSimpleEntity('total_count', $userCount)
    ->addSimpleEntity('active_count', count($userActiveCount))
    ->xsl('musadm/statistic/active_clients.xsl')
    ->show();



/**
 * Статистика по отвалу клиентов
 */

$userAllCount =  (new User())
    ->queryBuilder()
    ->where('group_id', '=', ROLE_CLIENT)
    ->where('subordinated', '=', $subordinated)
    ->where('register_date','<=',$dateTo)
    ->where('active', '=', 1)
    ->count();


$PropertyList =  (new Property_List_Values())
    ->queryBuilder()
    ->where('property_id', '=', Event::CLIENT_ACTIVITY)
    ->findAll();
$userActivityList = [];
foreach ($PropertyList as $property) {
    $UserActivityCount =  (new User_Activity())
        ->queryBuilder()
        ->where('reason_id', '=', $property->id())
        ->between('dump_date_start',$dateFrom,$dateTo)
        ->count();
    $property = $property->toStd();
    $property->count = $UserActivityCount;
    array_push($userActivityList,$property);

}

$countNewClient = (new User)
    ->queryBuilder()
    ->between('register_date',$dateFrom,$dateTo)
    ->where('active', '=', 1)
    ->count();

$countLeaveClient = (new User_Activity)
    ->queryBuilder()
    ->select('count(id)')
    ->between('dump_date_start',$dateFrom,$dateTo)
    ->groupBy('user_id')
    ->findAll();

$countComebackClient = (new User_Activity)
    ->queryBuilder()
    ->select('count(id)')
    ->between('dump_date_end',$dateFrom,$dateTo)
    ->groupBy('user_id')
    ->findAll();
$percentLeaveClient =  (round(((count($countLeaveClient) / $userAllCount)*100),2)).'%';

Core::factory('Core_Entity')
    ->addSimpleEntity('count_new_client',$countNewClient)
    ->addSimpleEntity('count_leave_client',count($countLeaveClient))
    ->addSimpleEntity('count_comeback_client',count($countComebackClient))
    ->addSimpleEntity('count_percent_client',$percentLeaveClient)
    ->addEntities($userActivityList,'userActivityList')
    ->xsl('musadm/statistic/archive_clients.xsl')
    ->show();
