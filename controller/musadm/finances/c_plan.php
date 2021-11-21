<?php

use Carbon\Carbon;

$user = User_Auth::current();
$director = $user->getDirector();

//Сбор общих данных по планам
$areas = (new Schedule_Area_Assignment($user))->getAreas();
$paymentTypes = Payment_Type::query()
    ->where('is_in_targets', '=', 1)
    ->where('subordinated', '=', $director->getId())
    ->findAll();

$month = intval(request()->get('month', date('m')));
$year = intval(request()->get('year', date('Y')));
$areaId = intval(request()->get('area', !empty($areas) ? $areas[0]->getId() : 0));

if (empty($areaId)) {
    Core_Page_Show::instance()->error(500, 'Филиалы отсутствуют');
}

$targets = Statistic_Payment_Target::query()
    ->where('month', '=', $month)
    ->where('year', '=', $year)
    ->where('area_id', '=', $areaId)
    ->get();

// Подсчет расходов
$targetsDate = Carbon::createFromDate($year, $month);
$expenses = (new Orm)
    ->select(['pt.id as type_id', 'sum(value) as amount'])
    ->from((new Payment)->getTableName(), 'p')
    ->join((new Payment_Type)->getTableName() . ' as pt', 'p.type = pt.id and pt.subordinated = '.$director->getId().' and pt.is_deletable = 1')
    ->where('p.area_id', '=', $areaId)
    ->where('p.datetime', '>=', $targetsDate->startOfMonth()->format('Y-m-d'))
    ->where('p.datetime', '<=', $targetsDate->endOfMonth()->format('Y-m-d'))
    ->groupBy('pt.id')
    ->get();

//Подсчет общего кол-ва
$total = new stdClass();
$total->expenses = 0;
$total->target = 0;
$paymentTypes = array_map(function(Payment_Type $type) use ($expenses, $targets, $total) {
    $type->expenses = (int)($expenses->where('type_id', $type->getId())->first()->amount ?? 0);
    $type->target = (int)($targets->where('payment_type', $type->getId())->first()->target ?? 0);
    $total->expenses += $type->expenses;
    $total->target += $type->target;
    return $type;
}, $paymentTypes);

global $CFG;
(new Core_Entity)
    ->addEntities($areas, 'area')
    ->addEntities($paymentTypes, 'payment_type')
    ->addSimpleEntity('month', $month)
    ->addSimpleEntity('year', $year)
    ->addSimpleEntity('area_id', $areaId)
    ->addSimpleEntity('wwwroot', $CFG->wwwroot)
    ->addEntity($total, 'total')
    ->xsl('musadm/statistic_targets/index.xsl')
    ->show();