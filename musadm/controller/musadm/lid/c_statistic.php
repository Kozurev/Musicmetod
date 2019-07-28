<?php
/**
 * Файл формирования контента раздела аналитики лидов
 *
 * @author BadWolf
 * @date 20.07.2019 13:07
 */

Core::requireClass('Lid');
Core::requireClass('Property_Controller');

$subordinated = User::current()->getDirector()->getId();

$dateFrom = Core_Array::Get('date_from', date('Y-m-d'), PARAM_DATE);
$dateTo = Core_Array::Get('date_to', date('Y-m-d'), PARAM_DATE);

$Lid = new Lid();

$QueryBuilder = new Orm();
$QueryBuilder
    ->select('count(l.id)', 'count')
    ->from('Lid', 'l');

if ($dateFrom == $dateTo) {
    $QueryBuilder->where('l.control_date', '=', $dateFrom);
} else {
    $QueryBuilder
        ->where('l.control_date', '>=', $dateFrom)
        ->where('l.control_date', '<=', $dateTo);
}


$Sources = Property_Controller::factoryByTag('lid_source')->getList();
$Markers = Property_Controller::factoryByTag('lid_marker')->getList();
$Statuses = $Lid->getStatusList();

//Формирование блока редактирования статусов
$OnConsult =        Property_Controller::factoryByTag('lid_status_consult');
$AttendedConsult =  Property_Controller::factoryByTag('lid_status_consult_attended');
$AbsentConsult =    Property_Controller::factoryByTag('lid_status_consult_absent');
$OnConsult =        $OnConsult->getValues(User::current())[0]->value();
$AttendedConsult =  $AttendedConsult->getValues(User::current())[0]->value();
$AbsentConsult =    $AbsentConsult->getValues(User::current())[0]->value();

$StatusesOutput = new Core_Entity();
$StatusesOutput
    ->addSimpleEntity('date_from', $dateFrom)
    ->addSimpleEntity('date_to', $dateTo)
    ->addSimpleEntity('directorid', $subordinated)
    ->addSimpleEntity('lid_status_consult', $OnConsult)
    ->addSimpleEntity('lid_status_consult_attended', $AttendedConsult)
    ->addSimpleEntity('lid_status_consult_absent', $AbsentConsult)
    ->xsl('musadm/lids/statuses.xsl')
    ->addEntities($Statuses)
    ->show();


$StatisticOutput = new Core_Entity();
$StatisticOutput->addEntities($Statuses);
$StatisticOutput->xsl('musadm/lids/statistic.xsl');


$statisticForPropVal = [
    '50' => $Sources,
    '54' => $Markers
];

foreach ($statisticForPropVal as $propId => $propValues) {
    $Output = new Core_Entity();
    $Output->_entityName('table');
    $Output->addSimpleEntity('title', Property_Controller::factory($propId)->title());

    foreach ($propValues as $PropVal) {
        $Query = clone $QueryBuilder;
        $Query->join(
            'Property_List as pl',
            'pl.value_id = ' . $PropVal->getId() . ' and pl.property_id = ' . $propId . ' and pl.object_id = l.id');

        $TotalValQuery = clone $Query;
        $total = $TotalValQuery->find()->count;

        $PropVal->addSimpleEntity('title', Property_Controller::factoryListValue($PropVal->getId())->value());
        $PropVal->addSimpleEntity('total_count', $total);

        foreach ($Statuses as $Status) {
            $PropStatusQuery = clone $Query;
            $PropStatusQuery->where('l.status_id', '=', $Status->getId());
            $sourceStatusCount = $PropStatusQuery->find()->count;

            $ValStatus = clone $Status;
            $ValStatus->addSimpleEntity('count_lids', $sourceStatusCount);
            $PropVal->addEntity($ValStatus, 'status');
        }

        $Output->addEntity($PropVal, 'val');
    }

    $StatisticOutput->addEntity($Output);
}

$StatisticOutput->show();