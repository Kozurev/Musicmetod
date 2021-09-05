<?php
/**
 * Индексная страница для менеджеров
 *
 * @author Bad Wolf
 * @date 18.03.2018 21:21
 * @version 20190222
 * @version 20190427
 */

authOrOut();

if (!User_Auth::current()->isManagementStaff()) {
    Core_Page_Show::instance()->error(403);
}

Core_Page_Show::instance()->setParam('body-class', 'body-green');
Core_Page_Show::instance()->setParam('title-first', 'НОВОСТИ');

$action = Core_Array::Get('action', null, PARAM_STRING);

$user = User_Auth::current();
$director = $user->getDirector();
$subordinated = $director->getId();


/**
 * Обработчик для открытия всплывающего окна редактирования списка дополнительного свойства
 */
if ($action === 'getPropertyListPopup') {
    $propId = Core_Array::Get('prop_id', null, PARAM_INT);
    if (is_null($propId)) {
        Core_Page_Show::instance()->error(404);
    }

    $Property = Property_Controller::factory($propId);
    if (is_null($Property)) {
        Core_Page_Show::instance()->error(404);
    }

    Core::factory('Core_Entity')
        ->addEntity($Property)
        ->addEntities($Property->getList())
        ->xsl('musadm/edit_property_list.xsl')
        ->show();

    exit;
}


/**
 * Сохранение элемента списка дополнительного свойства
 */
if ($action === 'savePropertyListValue') {
    $id =     Core_Array::Get('id', null, PARAM_INT);
    $propId = Core_Array::Get('prop_id', null, PARAM_INT);
    $value =  Core_Array::Get('value', null, PARAM_STRING);

    if (is_null($propId) || is_null($value) || $propId <= 0 || $value == '') {
        Core_Page_Show::instance()->error(404);
    }

    $NewValue = Property_Controller::factoryListValue($id);
    if (is_null($NewValue)) {
        Core_Page_Show::instance()->error(404);
    }

    $NewValue
        ->propertyId($propId)
        ->value($value)
        ->sorting(0);
    $NewValue->save();

    $returnJson = new stdClass();
    $returnJson->id = $NewValue->getId();
    $returnJson->propertyId = $propId;
    $returnJson->value = $value;
    echo json_encode($returnJson);
    exit;
}


/**
 * Удаление элемента списка дополнения свйоства
 */
if ($action === 'deletePropertyListValue') {
    $id = Core_Array::Get('id', null, PARAM_INT);
    if (is_null($id)) {
        Core_Page_Show::instance()->error(404);
    }

    $PropertyListValue = Property_Controller::factoryListValue($id);
    if (is_null($PropertyListValue)) {
        Core_Page_Show::instance()->error(404);
    }

    $PropertyListValue->delete();
    exit;
}


/**
 * Обработчик для сохранения значения доп. свойства
 */
if ($action === 'savePropertyValue') {
    $propertyName = Core_Array::Get('prop_name', null, PARAM_STRING);
    $propertyValue= Core_Array::Get('value', null, PARAM_STRING);
    $modelId =      Core_Array::Get('model_id', null, PARAM_INT);
    $modelName =    Core_Array::Get('model_name', null, PARAM_STRING);

    $Object = Core::factory($modelName);
    $Property = Property_Controller::factoryByTag($propertyName);

    if (is_null($Property) || is_null($Object)) {
        Core_Page_Show::instance()->error(404);
    }

    if (method_exists($Object, 'subordinated')) {
        $Object->queryBuilder()
            ->open()
            ->where('subordinated', '=', $subordinated)
            ->orWhere('subordinated', '=', 0)
            ->close();
    }

    $Object = $Object->queryBuilder()
        ->where('id', '=', $modelId)
        ->find();

    if (is_null($Object)) {
        Core_Page_Show::instance()->error(404);
    }
    //Проверка , если доп.свойство типа boolean
    if($propertyValue == 'true' or $propertyValue == 'false' ) {
        $propertyValue = $propertyValue == 'true' ? true : false;
    }

    $Value = $Property->getValues($Object)[0];
    if( $Value->value() == $propertyValue){
        exit(json_encode(['status'=>false]));
    }
    $Value->value($propertyValue);
    $Value->save();

    $response = new stdClass();
    $response->object = $Object->toStd();
    $response->property = $Property->toStd();
    $response->value = $Value->toStd();
    exit(json_encode($response));
}

/**
 * Обработчик для получения значения доп. свойства
 */
if ($action === 'checkPropertyValue') {
    $propertyName = Core_Array::Get('prop_name', null, PARAM_STRING);
    $modelId =      Core_Array::Get('model_id', null, PARAM_INT);
    $modelName =    Core_Array::Get('model_name', null, PARAM_STRING);

    $Object = Core::factory($modelName);
    $Property = Property_Controller::factoryByTag($propertyName);

    if (is_null($Property) || is_null($Object)) {
        Core_Page_Show::instance()->error(404);
    }

    if (method_exists($Object, 'subordinated')) {
        $Object->queryBuilder()
            ->open()
            ->where('subordinated', '=', $subordinated)
            ->orWhere('subordinated', '=', 0)
            ->close();
    }

    $Object = $Object->queryBuilder()
        ->where('id', '=', $modelId)
        ->find();

    if (is_null($Object)) {
        Core_Page_Show::instance()->error(404);
    }


    $Value = $Property->getValues($Object)[0];
    $response = new stdClass();
    $response->value = $Value->toStd();
    exit(json_encode($response));
}

/**
 * Обработчик для удаления доп. свойства
 */
if ($action === 'deleteProperty') {
    $propertyName = Core_Array::Get('prop_name', null, PARAM_STRING);
    $modelId =      Core_Array::Get('model_id', null, PARAM_INT);
    $modelName =    Core_Array::Get('model_name', null, PARAM_STRING);

    $Object = Core::factory($modelName);
    $Property = Property_Controller::factoryByTag($propertyName);

    if (is_null($Property) || is_null($Object)) {
        Core_Page_Show::instance()->error(404);
    }

    if (method_exists($Object, 'subordinated')) {
        $Object->queryBuilder()
            ->open()
            ->where('subordinated', '=', $subordinated)
            ->orWhere('subordinated', '=', 0)
            ->close();
    }

    $Object = $Object->queryBuilder()
        ->where('id', '=', $modelId)
        ->find();

    if (is_null($Object)) {
        Core_Page_Show::instance()->error(404);
    }


    $Value = $Property->getValues($Object)[0];
    $Value->delete();

    $response = new stdClass();
    $response->object = $Object->toStd();
    $response->property = $Property->toStd();
    $response->value = $Value->toStd();
    exit(json_encode($response));
}

///**
// * Обновление таблицы лидов
// */
//if ($action === 'refreshLidTable') {
//    $LidController = new Lid_Controller(User_Auth::current());
//
//    $areaId = Core_Array::Get('area_id', 0, PARAM_INT);
//    if ($areaId !== 0) {
//        $forArea = Core::factory('Schedule_Area', $areaId);
//        $LidController->forAreas([$forArea]);
//        $LidController->isEnableCommonLids(false);
//    }
//
//    $phone = Core_Array::Get('phone', null, PARAM_STRING);
//    if (!is_null($phone)) {
//        $LidController->appendFilter('number', $phone);
//        $LidController->addSimpleEntity('number', $phone);
//        $LidController->isPeriodControl(false);
//    }
//
//    $LidController
//        ->lidId(Core_Array::Get('lidid', null, PARAM_INT))
//        ->isEnableCommonLids(false)
//        ->isWithAreasAssignments(true)
//        ->isShowPeriods(false)
//        ->show();
//    exit;
//}
//
//
///**
// * Обновление таблицы
// */
//if ($action === 'refreshTasksTable') {
//    $TaskController = new Task_Controller(User::current());
//
//    $areaId = Core_Array::Get('areaId', 0, PARAM_INT);
//    if ($areaId !== 0) {
//        $forArea = Core::factory('Schedule_Area', $areaId);
//        $TaskController->forAreas([$forArea]);
//        $TaskController->isEnableCommonTasks(false);
//    }
//
//    $TaskController
//        ->isWithAreasAssignments(true)
//        ->isShowPeriods(false)
//        ->isSubordinate(true)
//        ->isLimitedAreasAccess(true)
//        ->show();
//    exit;
//}


//if ($action === 'search_client') {
//    $surname = Core_Array::Get('surname', null, PARAM_STRING);
//    $name    = Core_Array::Get('name', null, PARAM_STRING);
//    $phone   = Core_Array::Get('phone', null, PARAM_STRING);
//
//    $ClientController = new User_Controller(User::current());
//    $ClientController
//        ->isSubordinate(true)
//        ->filterType(User_Controller::FILTER_NOT_STRICT)
//        ->isActiveBtnPanel(false)
//        ->groupId(ROLE_CLIENT)
//        ->properties(true)
//        ->isLimitedAreasAccess(true)
//        ->xsl('musadm/users/clients.xsl');
//
//    if (!is_null($surname)) {
//        $ClientController->appendFilter('surname', $surname);
//    }
//    if (!is_null($name)) {
//        $ClientController->appendFilter('name', $name);
//    }
//    if (!is_null($phone)) {
//        $ClientController->appendFilter('phone_number', $phone);
//    }
//
//    $SearchingClientsHtml = $ClientController->show(false);
//    if (count($ClientController->getUserIds()) > 0) {
//        echo "<div class='users'>";
//        echo $SearchingClientsHtml;
//        echo "</div>";
//    }
//    exit;
//}


if ($action === 'getObjectInfoPopup') {
    $id =     Core_Array::Get('id', 0, PARAM_INT);
    $model =  Core_Array::Get('model', '', PARAM_STRING);

    $Object = Core::factory($model)
        ->queryBuilder()
        ->where('id', '=', $id)
        ->where('subordinated', '=', $subordinated)
        ->find();

    if (is_null($Object)) {
        exit("<h2>Объект с переданными данными не найден или был удален</h2>");
    }

    $Output = Core::factory('Core_Entity')
        ->xsl('musadm/object.xsl');

    switch ($model)
    {
        case 'Task' :
            $TaskController = new Task_Controller( User::current() );
            $TaskController
                ->isShowPeriods( false )
                ->isShowButtons( false )
                ->isPeriodControl( false )
                ->taskId( $id )
                ->xsl( 'musadm/tasks/all.xsl' )
                ->show();
            exit;

        case 'Lid' :
            $LidController = new Lid_Controller(User::current());
            $LidController
                ->isShowPeriods(false)
                ->isShowButtons(false )
                ->isPeriodControl(false)
                ->lidId($id)
                ->xsl('musadm/lids/lids.xsl')
                ->show();
            exit;

        case 'Certificate' :
            $Object->sellDate(refactorDateFormat($Object->sellDate()));
            $Object->activeTo(refactorDateFormat($Object->activeTo()));

            $Notes = Core::factory( 'Certificate_Note' )->queryBuilder()
                ->select(['certificate_id', 'author_id', 'date', 'text', 'surname', 'name'])
                ->where('certificate_id', '=', $id)
                ->leftJoin('User AS u', 'u.id = author_id')
                ->orderBy('date', 'DESC')
                ->orderBy('Certificate_Note.id', 'DESC')
                ->findAll();

            foreach ($Notes as $Note) {
                $Note->date(refactorDateFormat($Note->date()));
            }

            $Output->addEntities($Notes, 'note');
            break;

        default: echo "<h2>Ошибка: отсутствует обработчик для модели '". $model ."'</h2>";
    }

    $Output->addEntity($Object)->show();
    exit;
}


if ($action === 'refreshTableUsers') {
    Core_Page_Show::instance()->execute();
    exit;
}