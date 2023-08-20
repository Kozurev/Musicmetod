<?php
/**
 * Created by PhpStorm.
 * User: Egor
 * Date: 17.06.2019
 * Time: 18:25
 */

foreach ($_GET as $key => $param) {
    if (substr($key, 0, 4) == 'amp;') {
        $_GET[substr($key, 4)] = $param;
        unset($_GET[$key]);
    }
}


$action = Core_Array::Request('action', null, PARAM_STRING);

$currentUser = User_Auth::current();
$accessAreaMultiAccess = Core_Access::instance()->hasCapability(Core_Access::AREA_MULTI_ACCESS);


/**
 * Поиск списка тарифов
 *
 * @INPUT_GET:  params       array      список параметров формирования списка тарифов
 *
 * @OUTPUT:     json
 *
 * @OUTPUT_DATA: array of stdClass      список тарифов в виде объектов со всеми их свойствами
 */
if ($action === 'getList' || $action === 'get_list') {
    $params = Core_Array::Get('params', [], PARAM_ARRAY);
    $limit = Core_Array::getValue($params, 'limit', null, PARAM_INT);
    $offset = Core_Array::getValue($params, 'offset', null, PARAM_INT);
    $order = Core_Array::getValue($params, 'order', null, PARAM_ARRAY);

    $tariffsQuery = Payment_Tariff::query();

    //TODO: пока что проверка прав доступа осуществляется именно вот так, надо бы потом поменять
    if (is_null($currentUser) || $currentUser->isClient()) {
        $tariffsQuery->where('access', '=', Payment_Tariff::ACCESS_TYPE_PUBLIC);
    }

    if (!is_null($currentUser)) {
        $tariffsQuery->where('subordinated', '=', $currentUser->getDirector()->getId());
    }

    if (!$currentUser->isDirector() && !$accessAreaMultiAccess) {
        $userAreas = (new Schedule_Area_Assignment($currentUser))->getAssignments();
        $userAreasIds = collect($userAreas)->map(function(Schedule_Area_Assignment $assignment) {
            return $assignment->areaId();
        });
        $tariffsQuery->leftJoin(
            'Schedule_Area_Assignment as saa',
            'saa.model_id = Payment_Tariff.id and saa.model_name = "Payment_Tariff"'
            )
            ->open()
            ->whereIn('saa.area_id', $userAreasIds->toArray())
            ->orWhere('saa.area_id', 'is', 'NULL')
            ->close()
            ->groupBy((new Payment_Tariff)->getTableName() . '.id');
    }

    if (!is_null($limit)) {
        $tariffsQuery->limit($limit);
    }

    if (!is_null($offset)) {
        $tariffsQuery->offset($offset);
    }

    if (!is_null($order)) {
        $tariffsQuery->orderBy(
            Core_Array::getValue($order, 'field', 'id', PARAM_STRING),
            Core_Array::getValue($order, 'order', 'ASC', PARAM_STRING)
        );
    }

    $tariffs = $tariffsQuery->get();
    $response = $tariffs->map(function(Payment_Tariff $tariff) {
        return $tariff->toStd();
    });

    die(json_encode($response));
}


/**
 * Покупка клиента
 */
if ($action === 'buyForClient' || $action === 'buy_tariff') {
    //Проверка прав доступа
    if (!Core_Access::instance()->hasCapability(Core_Access::PAYMENT_TARIF_BUY)) {
        if ($action === 'buyForClient') {
            Core_Page_Show::instance()->error(403);
        } else {
            exit(REST::responseError(REST::ERROR_CODE_ACCESS));
        }
    }

    $clientId = Core_Array::Get('userId', null, PARAM_INT);
    $tariffId =  Core_Array::Get('tariffId', null, PARAM_INT);
    if (is_null($tariffId)) {
        $tariffId = Core_Array::Get('tariff_id', null, PARAM_INT);
    }

    if (is_null($clientId) && !is_null(User_Auth::current()) && User_Auth::current()->isClient()) {
        $clientId = User_Auth::current()->getId();
    }

    $client = \Model\User\User_Client::find($clientId);
    $tariff = Payment_Tariff::find($tariffId);

    if (is_null($client) || is_null($tariff)) {
        Core_Page_Show::instance()->error(404);
    }

    try {
        $tariffAreas = (new Schedule_Area_Assignment($tariff))->getAssignments();
        $tariffAreasIds = collect($tariffAreas)->map(function(Schedule_Area_Assignment $assignment): int {
            return $assignment->areaId();
        })->toArray();
        if (!empty($tariffAreasIds) && !$currentUser->isDirector() && !$accessAreaMultiAccess && !(new Schedule_Area_Assignment($currentUser))->hasAccessMulti($tariffAreasIds)) {
            throw new Exception('Отсутствует доступ к текущему тарифу');
        }

        $client->buyTariff($tariff);
    } catch (\Throwable $throwable) {
        exit(REST::responseError(REST::ERROR_CODE_CUSTOM, $throwable->getMessage()));
    }

    $response = ['user' => $client->toStd()];
    $response['user']->balance = $client->getBalance()->toStd();
    $response['tarif'] = $tariff->toStd();
    die(json_encode($response));
}

/**
 * Поиск тарифа по id
 */
if ($action === 'get_rate_by_id') {
    $tariffId = Core_Array::Get('tariff_id', 0, PARAM_INT);
    $tariffQuery = Payment_Tariff::query()->where('id', '=', $tariffId);
    //TODO: пока что проверка прав доступа осуществляется именно вот так, надо бы потом поменять
    $currentUser = User_Auth::current();
    if (is_null($currentUser) || $currentUser->isClient()) {
        $tariffQuery->where('access', '=', Payment_Tariff::ACCESS_TYPE_PUBLIC);
    }

    $tariff = $tariffQuery->find();

    if (is_null($tariff)) {
        exit(REST::responseError(REST::ERROR_CODE_NOT_FOUND));
    }

    die(json_encode($tariff->toStd()));
}

if ($action === 'remove') {
    if (!Core_Access::instance()->hasCapability(Core_Access::PAYMENT_TARIF_DELETE)) {
        Core_Page_Show::instance()->error(403, 'Недостаточно прав для удаления тарифа', true);
    }

    $tariffId = request()->get('tariffId', 0);
    $tariff = Payment_Tariff::find($tariffId);

    if (empty($tariffId) || is_null($tariff)) {
        Core_Page_Show::instance()->error(422, 'Тариф с указанным id не найден', true);
    }

    $tariff->delete();
    exit(REST::status(REST::STATUS_SUCCESS, 'Тариф был успешно удален'));
}