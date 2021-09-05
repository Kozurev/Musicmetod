<?php
/**
 * Created by PhpStorm.
 * User: Kozurev Egor
 * Date: 19.04.2018
 * Time: 23:18
 */

authOrOut();

if (!User_Auth::parentAuth()->isManagementStaff()) {
    Core_Page_Show::instance()->error(403);
}

$breadcumbs[0] = new stdClass();
$breadcumbs[0]->title = Core_Page_Show::instance()->Structure->getParent()->title();
$breadcumbs[0]->active = 1;
$breadcumbs[1] = new stdClass();
$breadcumbs[1]->title = Core_Page_Show::instance()->Structure->title();
$breadcumbs[1]->active = 1;

Core_Page_Show::instance()->setParam( 'body-class', 'body-orange' );
Core_Page_Show::instance()->setParam( 'title-first', 'ЛИЧНЫЙ' );
Core_Page_Show::instance()->setParam( 'title-second', 'КАБИНЕТ' );
Core_Page_Show::instance()->setParam( 'breadcumbs', $breadcumbs );


if (Core_Array::Get('ajax', 0) == 1) {
    Core_Page_Show::instance()->execute();
    exit;
}

$User = User_Auth::current();

if (is_null($User)) {
    Core_Page_Show::instance()->error(403);
} else {
    $clientId = Core_Array::Get('userid', null, PARAM_INT);
    if (is_null($clientId)) {
        $pageUserFio = $User->surname() . ' ' . $User->name();
    } else {
        $Client = User_Controller::factory($clientId);
        if (is_null($Client)) {
            Core_Page_Show::instance()->error(403);
        }
        $pageUserFio = $Client->surname() . ' ' . $Client->name();
    }

    Core_Page_Show::instance()->title = $pageUserFio . ' | Личный кабинет';
}

$Director = $User->getDirector();
$action = Core_Array::Get('action', '');


/**
 * Обновление сожержимого страницы
 */
if ($action === 'refreshTablePayments') {
    //проверка прав доступа
    if (!Core_Access::instance()->hasCapability(Core_Access::USER_READ_CLIENTS)) {
        Core_Page_Show::instance()->error(403);
    }

    Core_Page_Show::instance()->execute();
    exit;
}


/**
 * Редактирование примечания
 */
if ($action === 'updateNote') {
    if (!Core_Access::instance()->hasCapability(Core_Access::USER_EDIT_CLIENT)) {
        Core_Page_Show::instance()->error(403);
    }

    $userId =   Core_Array::Get('userId', null, PARAM_INT);
    $note =     Core_Array::Get('note', null, PARAM_STRING);

    $user = User_Controller::factory($userId);
    if (is_null($user)) {
        Core_Page_Show::instance()->error(404);
    }
    $user->comment($note);
    $user->save();
    exit;
}


/**
 * Обновление значения свойства "Поурочно"
 */
if ($action === 'updatePerLesson') {
    if (!User::checkUserAccess(['groups' => [ROLE_DIRECTOR, ROLE_MANAGER]])) {
        Core_Page_Show::instance()->error(403);
    }

    $userId =   Core_Array::Get( 'userId', null, PARAM_INT );
    $value =    Core_Array::Get( 'value', 0, PARAM_INT );
    $Client = User_Controller::factory($userId);

    if (is_null($userId) || is_null($Client)) {
        Core_Page_Show::instance()->error(404);
    }

    Core::factory('Property')->getByTagName('per_lesson')
        ->getPropertyValues($Client)[0]
        ->value($value)
        ->save();

    exit;
}


/**
 * Открытие всплывающего окна для редактирования данных отчета о проведенном занятии
 */
if ($action === 'edit_report_popup') {
    if (!User::checkUserAccess(['groups' => [ROLE_DIRECTOR]])) {
        Core_Page_Show::instance()->error(403);
    }

    $id = Core_Array::Get('id', 0, PARAM_INT);
    $Report = Core::factory('Schedule_Lesson_Report', $id);

    if (is_null($Report)) {
        exit('Изменяемый вами отчет не существует. Перезагрузите страницу');
    }

    Core::factory('Core_Entity')
        ->addEntity($Report, 'rep')
        ->xsl('musadm/users/balance/edit_report_popup.xsl')
        ->show();

    exit;
}


/**
 * Формирование всплывающего окна для самостоятельной постановки в график
 */
if ($action === 'makeClientLessonPopup') {
    if (!Core_Access::instance()->hasCapability(Core_Access::SCHEDULE_CREATE) && !Core_Access::instance()->hasCapability(Core_Access::SCHEDULE_LESSON_TIME)) {
        Core_Page_Show::instance()->error(403);
    }

    $clientId = Core_Array::Get('clientId', 0, PARAM_INT);
    if (!empty($clientId)) {
        $client = User_Controller::factory($clientId);
    } else {
        $client = User_Auth::current();
    }

    $clientController = new User_Controller_Extended($client);
    $teachers = $clientController->getClientTeachers();

    $areas = (new Schedule_Area_Assignment())->getAreas($client);
    if (count($areas) != 1 || empty($teachers)) {
        exit('Самостоятельная постановка в график невозможна');
    }

    $clientLessonDuration = Property_Controller::factoryByTag('lesson_time')->getValues($client)[0]->value();
    $seconds = $clientLessonDuration * 60;
    $clientLessonDuration = toTime($seconds);

    (new Core_Entity())
        ->addEntity($client, 'client')
        ->addEntities($teachers, 'teacher')
        ->addSimpleEntity('area_id', $areas[0]->getId())
        ->addSimpleEntity('lesson_duration', $clientLessonDuration)
        ->xsl('musadm/users/balance/new_client_lesson_popup.xsl')
        ->show();
    exit;
}


if (!Core_Access::instance()->hasCapability(Core_Access::USER_LC_CLIENT)) {
    Core_Page_Show::instance()->error(403);
}

/**
 * Обновление контента страницы
 */
if ($action === 'refreshTableUsers') {
    echo "<div class='users'>";
    Core_Page_Show::instance()->execute();
    echo "</div>";
    exit;
}