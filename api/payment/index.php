<?php
/**
 * @author BadWolf
 * @date 19.06.2019 23:04
 */

use Model\Checkout\Checkout;
use Model\Payonline;

foreach ($_GET as $key => $param) {
    if (substr($key, 0, 4) == 'amp;') {
        $_GET[substr($key, 4)] = $param;
        unset($_GET[$key]);
    }
}

$action = Core_Array::Request('action', null, PARAM_STRING);


/**
 * Получение информации о конкретном платеже
 */
if ($action === 'getPayment') {
    $paymentId = Core_Array::Get('paymentId', null, PARAM_INT);

    $Payment = Payment_Controller::factory($paymentId);
    if (is_null($Payment)) {
        die(REST::error(1, 'Платеж не найден'));
    }

    if ($paymentId === 0) {
        $Payment->datetime(date('Y-m-d'));
    }

    $response = new stdClass();
    $response->id = $Payment->getId();
    $response->datetime = $Payment->dateTime();
    $response->refactoredDatetime = refactorDateFormat($Payment->datetime());
    $response->userId = $Payment->user();
    $response->typeId = $Payment->type();
    $response->value = $Payment->value();
    $response->description = $Payment->description();
    $response->areaId = $Payment->areaId();
    $response->authorId = $Payment->authorId();
    $response->authorFio = $Payment->authorFio();
    $response->comments = [];
    if (isset($Payment->comments)) {
        foreach ($Payment->comments as $comment) {
            $stdComment = new stdClass();
            $stdComment->id = $comment->getId();
            $stdComment->text = $comment->value();
            $response->comments[] = $stdComment;
        }
    }

    if ($paymentId !== 0) {
        //Поиск информации о пользователе, с которым связан платеж
        $PaymentUser = $Payment->getUser();
        $response->userFio = $PaymentUser->surname() . ' ' . $PaymentUser->name();

        //Получение названия типа платежа
        if ($Payment->type() !== 0) {
            $PaymentType = Core::factory('Payment_Type', $Payment->type());
            $typeName = !is_null($PaymentType) ? $PaymentType->title() : '';
        } else {
            $typeName = '';
        }
        $response->typeName = $typeName;

        //Получение навания филиала
        if ($Payment->areaId() !== 0) {
            Core::requireClass('Schedule_Area_Assignment');
            $AreaAssignment = new Schedule_Area_Assignment();
            try {
                $PaymentArea = $AreaAssignment->getArea($Payment);
            } catch (Exception $e) {
                die(REST::error(2, $e->getMessage()));
            }
            $areaName = !is_null($PaymentArea) ? $PaymentArea->title() : '';
        } else {
            $areaName = '';
        }
        $response->areaName = $areaName;
    }

    die(json_encode($response));
}



/**
 * Сохранение данных платежа
 *
 * Пока что реализован функционал лишь для сохранения одного доп. комментария
 */
if ($action === 'save') {
    $accessCreateAll =      Core_Access::instance()->hasCapability(Core_Access::PAYMENT_CREATE_ALL);
    $accessCreateClient =   Core_Access::instance()->hasCapability(Core_Access::PAYMENT_CREATE_CLIENT);
    $accessCreateTeacher =  Core_Access::instance()->hasCapability(Core_Access::PAYMENT_CREATE_TEACHER);

    $accessEditAll =        Core_Access::instance()->hasCapability(Core_Access::PAYMENT_EDIT_ALL);
    $accessEditClient =     Core_Access::instance()->hasCapability(Core_Access::PAYMENT_EDIT_CLIENT);
    $accessEditTeacher =    Core_Access::instance()->hasCapability(Core_Access::PAYMENT_EDIT_TEACHER);

    $accessDeleteAll =      Core_Access::instance()->hasCapability(Core_Access::PAYMENT_DELETE_ALL);
    $accessDeleteClient =   Core_Access::instance()->hasCapability(Core_Access::PAYMENT_DELETE_CLIENT);
    $accessDeleteTeacher =  Core_Access::instance()->hasCapability(Core_Access::PAYMENT_DELETE_TEACHER);

    $id =           Core_Array::Get('id', null, PARAM_INT);
    $typeId =       Core_Array::Get('typeId', 0, PARAM_INT);
    $date =         Core_Array::Get('date', date('Y-m-d'), PARAM_DATE);
    $userId =       Core_Array::Get('userId', 0, PARAM_INT);
    $areaId =       Core_Array::Get('areaId', 0, PARAM_INT);
    $value =        Core_Array::Get('value', 0, PARAM_INT);
    $description =  Core_Array::Get('description', '', PARAM_STRING);
    $comment =      Core_Array::Get('comment', null, PARAM_STRING);

    $hasAccess = false;
    $hasAccessEdit = false;
    $hasAccessDelete = false;
    if (empty($id)) {
        if (($typeId == Payment::TYPE_INCOME || $typeId == Payment::TYPE_DEBIT) && $accessCreateClient) {
            $hasAccess = true;
        } elseif ($typeId == Payment::TYPE_TEACHER && $accessCreateTeacher) {
            $hasAccess = true;
        } else {
            $hasAccess = $accessCreateAll;
        }
    } else {
        if (($typeId == Payment::TYPE_INCOME || $typeId == Payment::TYPE_DEBIT) && $accessEditClient) {
            $hasAccess = true;
        } elseif ($typeId == Payment::TYPE_TEACHER && $accessEditTeacher) {
            $hasAccess = true;
        } else {
            $hasAccess = $accessEditAll;
        }
    }
    if (($typeId == Payment::TYPE_INCOME || $typeId == Payment::TYPE_DEBIT) && $accessEditClient) {
        $hasAccessEdit = true;
    } elseif ($typeId == Payment::TYPE_TEACHER && $accessEditTeacher) {
        $hasAccessEdit = true;
    } else {
        $hasAccessEdit = $accessEditAll;
    }
    if (($typeId == Payment::TYPE_INCOME || $typeId == Payment::TYPE_DEBIT) && $accessDeleteClient) {
        $hasAccessDelete = true;
    } elseif ($typeId == Payment::TYPE_TEACHER && $accessDeleteTeacher) {
        $hasAccessDelete = true;
    } else {
        $hasAccessDelete = $accessDeleteAll;
    }
    if (User_Auth::current()->getId() == $userId && $typeId == Payment::TYPE_INCOME) {
        $hasAccess = true;
    }

    if (!$hasAccess) {
        Core_Page_Show::instance()->error(403);
    }

    $Payment = Payment_Controller::factory($id);
    $Payment->type($typeId);
    $Payment->areaId($areaId);
    $Payment->datetime($date);
    $Payment->user($userId);
    $Payment->value($value);
    $Payment->description($description);
    $Payment->save();

    $Property = new Property();
    $PaymentComment = $Property->getByTagName('payment_comment');
//    if ($typeId == 1 || $typeId == 2) {
//        $newUserBalance = User_Balance::find($Payment->user())->getAmount();
//    } else {
//        $newUserBalance = 0;
//    }
    $userBalance = User_Balance::find($Payment->user());
    $newUserBalance = !is_null($userBalance) ? $userBalance->getAmount() : 0;

    if (!is_null($comment) && $comment !== '') {
        $Comment = $PaymentComment->getValues($Payment)[0];
        $Comment->value($comment)->save();
    } else {
        if (isset($Payment->comments)) {
            unset($Payment->comments);
        }
    }

    $Payment->comments = [];
    $Comments = $PaymentComment->getValues($Payment);
    if (count($Comments) > 0 && !empty($Comments[0]->getId())) {
        foreach ($Comments as $comment) {
            $commentStd = new stdClass();
            $commentStd->id = $comment->getId();
            $commentStd->text = $comment->value();
            $Payment->comments[] = $commentStd;
        }
    }

    $response = new stdClass();
    $response->id = $Payment->getId();
    $response->datetime = $Payment->dateTime();
    $response->refactoredDatetime = refactorDateFormat($Payment->datetime());
    $response->userId = $Payment->user();
    $response->typeId = $Payment->type();
    $response->value = $Payment->value();
    $response->description = $Payment->description();
    $response->areaId = $Payment->areaId();
    $response->comments = $Payment->comments;
    $response->userBalance = $newUserBalance;
    $response->accessEdit = $hasAccessEdit;
    $response->accessDelete = $hasAccessDelete;
    die(json_encode($response));
}


/**
 * Удаление платежа по id
 */
if ($action === 'remove') {
    $paymentId = Core_Array::Get('paymentId', null, PARAM_INT);
    if (is_null($paymentId) || $paymentId <= 0) {
        die(REST::status(REST::STATUS_ERROR, 'Нееврно передан идентификатор платежа'));
    }

    $Payment = Payment_Controller::factory($paymentId);
    if (is_null($Payment)) {
        die(REST::status(REST::STATUS_ERROR, 'Платеж не найден'));
    }

    $accessDeleteAll =      Core_Access::instance()->hasCapability(Core_Access::PAYMENT_DELETE_ALL);
    $accessDeleteClient =   Core_Access::instance()->hasCapability(Core_Access::PAYMENT_DELETE_CLIENT);
    $accessDeleteTeacher =  Core_Access::instance()->hasCapability(Core_Access::PAYMENT_DELETE_TEACHER);

    $hasAccess = false;
    if (($Payment->type() == Payment::TYPE_INCOME || $Payment->type() == Payment::TYPE_DEBIT) && $accessDeleteClient) {
        $hasAccess = true;
    } elseif ($Payment->type() == Payment::TYPE_TEACHER && $accessDeleteTeacher) {
        $hasAccess = true;
    } else {
        $hasAccess = $accessDeleteAll;
    }

    if (!$hasAccess) {
        Core_Page_Show::instance()->error(403);
    }

    $Payment->delete();

    $response = $Payment->toStd();
    $response->refactoredDatetime = refactorDateFormat($Payment->datetime());
    die(json_encode($response));
}



/**
 * Создание комментария к платежу
 */
if ($action === 'appendComment') {
    $paymentId = Core_Array::Get('paymentId', null, PARAM_INT);
    $comment = Core_Array::Get('comment', '', PARAM_STRING);

    if (is_null($paymentId)) {
        die(REST::error(1, 'Параметр paymentId не может быть пустым'));
    }
    if ($comment === '') {
        die(REST::error(2, 'Текст комментари должен содержать минимум один символ'));
    }

    $Payment = Payment_Controller::factory($paymentId);
    if (is_null($Payment)) {
        die(REST::error(3, 'Платеж не найден'));
    }

    $PaymentComment = $Payment->appendComment($comment);

    $response = new stdClass();
    $response->payment = $Payment->toStd();
    $response->comment = new stdClass();
    $response->comment->id = $PaymentComment->getId();
    $response->comment->text = $comment;
    die(json_encode($response));
}




/**
 * Удаление комментария к платежу
 */
if ($action === 'removeComment') {
    $commentId = Core_Array::Get('commentId', null, PARAM_INT);
    if (empty($commentId)) {
        die(REST::error(1, 'Неверно передан идентификатор коммнтария'));
    }

    $Property = new Property();
    $PaymentComment = $Property->getByTagName('payment_comment');
    $Comment = Core::factory('Property_String', $commentId);
    if ($Comment->propertyId() == $PaymentComment->getId()) {
        $Comment->delete();
    } else {
        die(REST::error(2, 'Передан некорректный идентификатор комментария'));
    }
}


/**
 * Формирование списка типов платежей
 */
if ($action === 'getCustomTypesList') {
    $Payment = new Payment();
    try {
        $Types = $Payment->getTypes();
    } catch (Exception $e) {
        die(REST::error(1, $e->getMessage()));
    }
    $response = [];
    foreach ($Types as $type) {
        $response[] = $type->toStd();
    }
    die(json_encode($response));
}

/**
 *
 */
if ($action === 'get_payments') {
    $user = User_Auth::current();

    if (is_null($user)
        || ($user->isClient() && !Core_Access::instance()->hasCapability(Core_Access::PAYMENT_READ_CLIENT))
        || ($user->isTeacher() && !Core_Access::instance()->hasCapability(Core_Access::PAYMENT_READ_TEACHER))) {
        exit(REST::responseError(REST::ERROR_CODE_ACCESS, 'Недостаточно прав для получения информации о платежах'));
    }

    $dateFrom = Core_Array::Get('date_from', null, PARAM_DATE);
    $dateTo = Core_Array::Get('date_to', null, PARAM_DATE);
    $types = Core_Array::Get('types', null, PARAM_ARRAY);

    $userId = !$user->isManagementStaff()
        ?   $user->getId()
        :   Core_Array::Get('user_id', 0, PARAM_INT);

    $paymentsQuery = Payment::getListQuery()
        ->where('user', '=', $userId);

    if (!is_null($dateFrom)) {
        $paymentsQuery->where('datetime', '>=', $dateFrom);
    }
    if (!is_null($dateTo)) {
        $paymentsQuery->where('datetime', '<=', $dateTo);
    }
    if (is_array($types) && !empty($types)) {
        $paymentsQuery->whereIn('type', $types);
    }

    $paymentsQuery->orderBy('id', 'DESC');

    if (Core_Array::Get('without_paginate', 0, PARAM_INT)) {
        $response = $paymentsQuery->get()->map(function(Payment $payment) {
            return $payment->toStd();
        });
    } else {
        $pagination = new Pagination($paymentsQuery, $_GET);
        $response = $pagination->execute();
    }

    die(json_encode($response));
}


/**
 * Регистрация платежа в эквайринге
 */
if ($action === 'registerOrder') {
    $amount = Core_Array::Request('amount', 0, PARAM_INT);
    $description = Core_Array::Request('description', 'Оплата музыкального обучения', PARAM_STRING);

    $user = User_Auth::current();
    if (is_null($user)) {
        Core_Page_Show::instance()->error(403);
    }

    //Проверка на наличие кассы для филиалов пользователя
    try {
        if (!Checkout::hasCheckout($user)) {
            throw new Exception('Для вашего филиала отсутствует прием платежей онлайн');
        }
    } catch (\Throwable $throwable) {
        die(json_encode(['errorCode' => '1', 'errorMessage' => $throwable->getMessage()]));
    }

    $userId = $user->getId();

    $payment = new Payment();
    $payment->user($userId);
    $payment->value($amount / 100);
    $payment->description($description);
    $payment->type(Payment::TYPE_INCOME);
    $payment->status(Payment::STATUS_PENDING);
    $payment->save();

    $payOnline = Payonline::instance($payment->getId(), $payment->value());
    $payOnline->setDescription($payment->description());
    $payOnline->setSuccessUrl(Core_Array::Request('successUrl', '', PARAM_STRING));
    $payOnline->setFailUrl(Core_Array::Request('errorUrl', '', PARAM_STRING));
    $paymentLink = $payOnline->getPaymentLink();

    //$payment->merchantOrderId($response->orderId ?? null);
    //$payment->save();
//    $tmpData = [
//        'successUrl' => Core_Array::Request('successUrl', '', PARAM_STRING),
//        'errorUrl' => Core_Array::Request('errorUrl', '', PARAM_STRING)
//    ];
//    Temp::put($payment->getId(), $tmpData);

    exit(json_encode([
        'formUrl' => $paymentLink
    ]));


//    $sberbak = Sberbank::instance();
//    $sberbak->setAmount($amount);
//    $sberbak->setUserId($userId);
//    $sberbak->setDescription($description);
//    $sberbak->setOrderNumber($payment->getId());
//    $response = $sberbak->registerOrder();

//    if (empty($response->errorCode ?? null)) {
//        $payment->merchantOrderId($response->orderId ?? null);
//        $payment->save();
//        $tmpData = [
//            'successUrl' => Core_Array::Request('successUrl', '', PARAM_STRING),
//            'errorUrl' => Core_Array::Request('errorUrl', '', PARAM_STRING)
//        ];
//    } else {
//        $payment->setStatusError();
//        $payment->appendComment('Ошибка платежного шлюза: '. ($response->errorMessage ?? 'Неизвестная ошибка'));
//        $tmpData = [
//            'successUrl' => Core_Array::Request('successUrl', '', PARAM_STRING),
//            'errorUrl' => Core_Array::Request('errorUrl', '',PARAM_STRING)
//        ];
//    }

//    if (!empty($response->orderId)) {
//        Temp::put($response->orderId, $tmpData);
//    }
//
//    exit(json_encode($response));
}

/**
 *
 */
if ($action === 'checkStatus') {
    if (!User_Auth::current()->isManagementStaff()) {
        Core_Page_Show::instance()->error(403);
    }

    $paymentId = request()->get('paymentId');

    if (empty($paymentId)) {
        exit(REST::responseError(REST::ERROR_CODE_REQUIRED_PARAM, 'Отсутствует обязательный параметр "paymentId"'));
    }

    /** @var Payment|null $payment */
    $payment = Payment::find($paymentId);
    if (empty($payment)) {
        exit(REST::responseError(REST::ERROR_CODE_NOT_FOUND, 'Платеж с ID ' . $paymentId . ' не найден'));
    }

    try {
        $sberbak = Sberbank::instance();
        $sberbak->checkStatus($payment);
    } catch (Throwable $throwable) {
        exit(REST::responseError($throwable->getCode(), $throwable->getMessage()));
    }
    exit(json_encode([
        'status' => true,
        'message' => 'Данные платежа были обновлены'
    ]));
}

if ($action === 'check_p2p_available') {
    if (!User_Auth::current()->isClient()) {
        Core_Page_Show::instance()->error(403);
    }

    $amount = request()->get('amount', 0);
    $dateFrom = \Carbon\Carbon::now()->startOfMonth();
    $dateTo = \Carbon\Carbon::now()->endOfMonth();

    $p2pService = new \Model\P2P\P2P();

    exit(json_encode([
        'status' => true,
        'receivers' => $p2pService->getReceiversDataAggregate($amount, $dateFrom, $dateTo),
    ]));
}
