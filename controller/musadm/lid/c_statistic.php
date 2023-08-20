<?php
/**
 * Файл формирования контента раздела аналитики лидов
 *
 * @author BadWolf
 * @date 20.07.2019 13:07
 * @version 2020-02-15 13:06
 */


$dateFrom =     Core_Array::Post('date_from', date('Y-m-d'), PARAM_DATE);
$dateTo =       Core_Array::Post('date_to', date('Y-m-d'), PARAM_DATE);

$director = User_Auth::current()->getDirector();
$subordinated = $director->getId();

$sourceProp = Property_Controller::factoryByTag('lid_source');
$markerProp = Property_Controller::factoryByTag('lid_marker');
$sources = $sourceProp->getList();
$markers = $markerProp->getList();
$statuses = Lid::getStatusList();
$areas = (new Schedule_Area_Assignment(User_Auth::current()))->getAreas();
$userAreasIds = collect($areas)->pluck('id')->toArray();

//Формирование блока редактирования статусов
$onConsult =        Property_Controller::factoryByTag('lid_status_consult');
$attendedConsult =  Property_Controller::factoryByTag('lid_status_consult_attended');
$absentConsult =    Property_Controller::factoryByTag('lid_status_consult_absent');
$lidClient =        Property_Controller::factoryByTag('lid_status_client');
$onConsult =        $onConsult->getValues($director)[0]->value();
$attendedConsult =  $attendedConsult->getValues($director)[0]->value();
$absentConsult =    $absentConsult->getValues($director)[0]->value();
$lidClient =        $lidClient->getValues($director)[0]->value();

(new Core_Entity())
    ->addSimpleEntity('capability_create_status', (int)User_Auth::current()->isDirector())
    ->addSimpleEntity('capability_edit_status', (int)User_Auth::current()->isDirector())
    ->addSimpleEntity('capability_remove_status', (int)User_Auth::current()->isDirector())
    ->addSimpleEntity('date_from', $dateFrom)
    ->addSimpleEntity('date_to', $dateTo)
    ->addSimpleEntity('directorid', $subordinated)
    ->addSimpleEntity('lid_status_consult', $onConsult)
    ->addSimpleEntity('lid_status_consult_attended', $attendedConsult)
    ->addSimpleEntity('lid_status_consult_absent', $absentConsult)
    ->addSimpleEntity('lid_status_client', $lidClient)
    ->addEntities($statuses)
    ->addEntities(Lid_Status::getColors(), 'color')
    ->xsl('musadm/lids/statuses.xsl')
    ->show();

//Таблица из раздела статистики + фильтр по преподам и филиам
$teacherId =    Core_Array::Post('teacherId', 0, PARAM_INT);
$areaId =       Core_Array::Post('areaId', 0, PARAM_INT);

$lidsOutput = new Core_Entity();
$countQuery = (new Lid)->queryBuilder()
    ->where('subordinated', '=', $subordinated);
$countFromScheduleQuery = (new Lid)->queryBuilder()
    ->where('subordinated', '=', $subordinated);
$countFromDateControl = (new Event())->queryBuilder()
    ->where('type_id', '=', Event::LID_CREATE);

//Если выборка идет только по лидам то в условие попадает дата контроля лида
//а если выборка идет по консультациям преподавателя то в условии буддет дата отчета
$dateRow = $teacherId === 0
    ?   'control_date'
    :   'rep.date';

$timeFrom = strtotime($dateFrom . " 00:00:00");
$timeTo = strtotime($dateTo . " 23:59:00");

if ($dateFrom == $dateTo) {
    $countQuery->where($dateRow, '=', $dateFrom);
    $countFromScheduleQuery->where('insert_date', '=', $dateFrom);
} else {
    $countQuery->between($dateRow, $dateFrom, $dateTo);
    $countFromScheduleQuery->between('insert_date', $dateFrom, $dateTo);
}
$countFromDateControl
    ->between('Event.time', $timeFrom, $timeTo)
    ->orderBy('time', 'DESC');

if ($teacherId !== 0) {
    $reportTableName = Core::factory('Schedule_Lesson_Report')->getTableName();
    $lessonTableName = Core::factory('Schedule_Lesson')->getTableName();
    $countQuery->join(
        $reportTableName . ' AS rep',
        'rep.type_id = ' . Schedule_Lesson::TYPE_CONSULT . ' AND rep.client_id = Lid.id AND rep.teacher_id = ' . $teacherId
    );
    $countFromScheduleQuery->join(
        $lessonTableName . ' AS lesson',
        'lesson.type_id = ' . Schedule_Lesson::TYPE_CONSULT . ' AND lesson.client_id = Lid.id AND lesson.teacher_id = ' . $teacherId
    );
} else {
    $lessonTableName = Core::factory('Schedule_Lesson')->getTableName();
    $countFromScheduleQuery->join(
        $lessonTableName . ' AS lesson',
        'lesson.type_id = ' . Schedule_Lesson::TYPE_CONSULT . ' AND lesson.client_id = Lid.id'
    );
}
if ($areaId !== 0 && in_array($areaId, $userAreasIds)) {
    $countQuery->where('area_id', '=', $areaId);
    $countFromScheduleQuery->where('lesson.area_id', '=', $areaId);
    $countFromDateControl->where('data', 'like', '%"area_id":' . $areaId . ',%');
} else {
    $countQuery->whereIn('area_id', $userAreasIds);
    $countFromScheduleQuery->whereIn('lesson.area_id', $userAreasIds);

    if (count($userAreasIds) == 1) {
        $countFromDateControl->where('data', 'like', '%"area_id":' . $userAreasIds[0] . ',%');
    } else {
        $countFromDateControl->open();
        foreach ($userAreasIds as $i => $id) {
            if ($i == 0) {
                $countFromDateControl->where('data', 'like', '%"area_id":' . $id . ',%');
            } else {
                $countFromDateControl->orWhere('data', 'like', '%"area_id":' . $id . ',%');
            }
        }
        $countFromDateControl->close();
    }
}

//Достаем Id лидов для получения актуального статуса по дате создания
$lidsDateControl = [];
foreach ($countFromDateControl->findAll() as $event) {
    if(is_object($event->getData())) {
        array_push($lidsDateControl,($event->getData()->lid->id));
    }
}

$totalCount = $countQuery->getCount();
$totalCountFromSchedule = $countFromScheduleQuery->getCount();
$totalCountFromDateControl = $countFromDateControl->getCount();

if (count($statuses) > 0) {
    foreach ($statuses as $key => $status) {
        $countWithStatus = clone $countQuery;
        $countWithStatusFromSchedule = clone $countFromScheduleQuery;
        $countWithStatusFromDateControl = (new Lid())->queryBuilder()
            ->whereIn('Lid.id', $lidsDateControl);

        // Костыль для конкретного директора дабы считать колонку "Был на консультации" как сумму "Был на консультации" и "Записался"
        if (User_Auth::current()->subordinated() === 516 && $status->getId() === 3) {
            $count = $countWithStatus
                ->whereIn('status_id', [3, 4])
                ->getCount();
            $countFromSchedule = $countWithStatusFromSchedule
                ->whereIn('status_id', [3, 4])
                ->getCount();
            $countFromDateControl = $totalCountFromDateControl !== 0
                ?  $countWithStatusFromDateControl->whereIn('status_id', [3, 4])->getCount()
                :  0;
        } else {
            $count = $countWithStatus
                ->where('status_id', '=', $status->getId())
                ->getCount();
            $countFromSchedule = $countWithStatusFromSchedule
                ->where('status_id', '=', $status->getId())
                ->getCount();
            $countFromDateControl = $totalCountFromDateControl !== 0
                ?  $countWithStatusFromDateControl->where('status_id', '=', $status->getId())->getCount()
                :  0;
        }

        $outputStatus = clone $statuses[$key];
        $outputStatus->addSimpleEntity('count', $count);
        $outputStatusSchedule = clone $statuses[$key];
        $outputStatusSchedule->addSimpleEntity('countSchedule', $countFromSchedule);
        $outputStatusDateControl = clone $statuses[$key];
        $outputStatusDateControl->addSimpleEntity('countDateControl', $countFromDateControl);
        $lidsOutput->addEntity($outputStatus, 'status');
        $lidsOutput->addEntity($outputStatusSchedule, 'statusSchedule');
        $lidsOutput->addEntity($outputStatusDateControl, 'statusDateControl');
    }
}

$teachersController = new User_Controller(User_Auth::current());
$teachersController->queryBuilder()
    ->clearOrderBy()
    ->orderBy('surname');
$teachers = $teachersController
    ->filterType(User_Controller::FILTER_STRICT)
    ->appendFilter('group_id', ROLE_TEACHER)
    ->getUsers();

echo '<section class="section-bordered">';
echo '<div class="row center-block">';
echo '<div class=""></div>';
$lidsOutput
    ->addSimpleEntity('total', $totalCount)
    ->addSimpleEntity('totalFromSchedule', $totalCountFromSchedule)
    ->addSimpleEntity('totalFromDateControl', $totalCountFromDateControl)
    ->addSimpleEntity('selectedTeacherId', $teacherId)
    ->addSimpleEntity('selectedAreaId', $areaId)
    ->addEntities($teachers)
    ->addEntities((new Schedule_Area_Assignment())->getAreas(User_Auth::current()))
    ->xsl('musadm/statistic/lids.xsl')
    ->show();
echo '</div></section>';

//Сводка по маркерам/источникам/преподам
$markerId = Core_Array::Post('markerId', 0, PARAM_INT);
$sourceId = Core_Array::Post('sourceId', 0, PARAM_INT);
$output = new Core_Entity();
$output->addSimpleEntity('markerId', $markerId);
$output->addSimpleEntity('sourceId', $sourceId);
$output->addEntities($statuses);
$outputFilters = new Core_Entity();
$outputFilters->_entityName('filters');
$outputFilters->addEntities($markers, 'marker');
$outputFilters->addEntities($sources, 'source');
$output->addEntity($outputFilters);
$output->xsl('musadm/lids/statistic_filtered.xsl');

//Свойство для подсчета общего числа лидов
foreach ($statuses as $status) {
    $status->totalCount = 0;
}

//Для подсчета кол-ва лидов, у которых вручную прописан статус
$sources[] = (new Property_List_Values)
    ->propertyId(50)
    ->value('Другое')
    ->setId(0);

foreach ($sources as $source) {
    $sourceTotalCount = 0;
    foreach ($statuses as $status) {
        $lidsController = new Lid_Controller_Extended();
        $lidsController->isWithComments(false);
        $lidsController->getQueryBuilder()->clearSelect()->select(['id']);
        $lidsController->setAreas($areas);

        if ($markerId === 0 && $sourceId !== 0 && $source->getId() !== $sourceId) {
            continue;
        }

        $lidsController->appendAddFilter($sourceProp->getId(), '=', $source->getId());

        if ($markerId !== 0) {
            $lidsController->appendAddFilter($markerProp->getId(), '=', $markerId);
        } else {
            if ($dateFrom === $dateTo) {
                $lidsController->appendFilter('date_create', $dateFrom, '=', Lid_Controller_Extended::FILTER_STRICT);
            } else {
                $lidsController->appendFilter('date_create', $dateFrom, '>=', Lid_Controller_Extended::FILTER_STRICT);
                $lidsController->appendFilter('date_create', $dateTo, '<=', Lid_Controller_Extended::FILTER_STRICT);
            }
        }

        if ($areaId !== 0) {
            $lidsController->setAreas([(new Schedule_Area())->setId($areaId)]);
        }

        $statusCloned = clone $status;
        $lidsController->appendFilter('status_id', $status->getId(), '=', Lid_Controller_Extended::FILTER_STRICT);
        $countWithStatus = count($lidsController->getLids());
        $sourceTotalCount += $countWithStatus;
        $statusCloned->addSimpleEntity('count_lids', $countWithStatus);
        $source->addEntity($statusCloned, 'status');
        $status->totalCount += $countWithStatus;
    }

    $source->addSimpleEntity('total_count', $sourceTotalCount);
    $output->addEntity($source, 'source');
}

$totalCount = 0;
foreach ($statuses as $key => $status) {
    $totalCount += $status->totalCount;
    //$statuses[$key]->addSimpleEntity('totalCount', $status->totalCount);
}

$output->addEntities($statuses, 'statuses');
$output->addSimpleEntity('totalCount', $totalCount);
$output->show();