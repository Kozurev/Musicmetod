<?php

$action = request()->get('action');

if ($action === 'set') {
    $areaId = intval(request()->get('area_id'));
    $paymentType = intval(request()->get('payment_type'));
    $target = intval(request()->get('target'));
    $month = intval(request()->get('month'));
    $year = intval(request()->get('year'));

    $statisticTarget = Statistic_Payment_Target::query()
        ->where('area_id', '=', $areaId)
        ->where('payment_type', '=', $paymentType)
        ->where('month', '=', $month)
        ->where('year', '=', $year)
        ->find();

    if (is_null($statisticTarget)) {
        $statisticTarget = (new Statistic_Payment_Target)
            ->paymentType($paymentType)
            ->areaId($areaId)
            ->month($month)
            ->year($year);
    }

    $statisticTarget->target($target);

    if (!$statisticTarget->save()) {
        exit(json_encode([
            'status' => 'error',
            'message' => $statisticTarget->_getValidateErrorsStr()
        ]));
    }

    $total = Statistic_Payment_Target::query()
        ->where('month', '=', $month)
        ->where('year', '=', $year)
        ->sum('target');

    exit(json_encode([
        'status' => 'success',
        'message' => 'Настройка успешно сохранена',
        'total' => $total
    ]));
}