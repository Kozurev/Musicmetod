<?php
/**
 * Настройки для раздела "Группы"
 *
 * @author: BadWolf
 * @date: 24.04.2018 19:46
 * @version 20190603
 * @version 20190405
 * @version 20190526
 */

Core::factory('User_Controller');

authOrOut();

$User = User::current();
$accessRules = ['groups' => [ROLE_DIRECTOR, ROLE_MANAGER]];
if (!User::checkUserAccess($accessRules, $User)) {
    Core_Page_Show::instance()->error(403);
}

$Director = $User->getDirector();
$subordinated = $Director->getId();

$breadcumbs[0] = new stdClass();
$breadcumbs[0]->title = Core_Page_Show::instance()->Structure->title();
$breadcumbs[0]->active = 1;
Core_Page_Show::instance()->setParam('body-class', 'body-blue');
Core_Page_Show::instance()->setParam('title-first', 'СПИСОК');
Core_Page_Show::instance()->setParam('title-second', 'ГРУПП');
Core_Page_Show::instance()->setParam('breadcumbs', $breadcumbs);

$action = Core_Array::Get('action', null, PARAM_STRING);


//основные права доступа к разделу
$accessRead =   Core_Access::instance()->hasCapability(Core_Access::SCHEDULE_GROUP_READ);
$accessCreate = Core_Access::instance()->hasCapability(Core_Access::SCHEDULE_GROUP_CREATE);
$accessEdit =   Core_Access::instance()->hasCapability(Core_Access::SCHEDULE_GROUP_EDIT);
$accessDelete = Core_Access::instance()->hasCapability(Core_Access::SCHEDULE_GROUP_DELETE);


//Формирование содержания всплывающего окна состава группы
if ($action === 'getGroupComposition') {
    if (!$accessEdit) {
        Core_Page_Show::instance()->error(403);
    }

    $groupId = Core_Array::Get('group_id', null, PARAM_INT);
    if (is_null($groupId)) {
        Core_Page_Show::instance()->error(404);
    }

    $Group = Core::factory('Schedule_Group', $groupId);
    if (is_null($Group)) {
        Core_Page_Show::instance()->error(404);
    }

    $Group->addEntities($Group->getClientList());
    $UserController = new User_Controller(User::current());
    $Users = $UserController
        ->groupId(ROLE_CLIENT)
        ->isWithAreaAssignments(false);
    $Users->queryBuilder()
        ->orderBy('surname', 'ASC')
        ->orderBy('name', 'ASC');
    Core::factory('Core_Entity')
        ->addEntity($Group, 'group')
        ->addEntities($Users->getUsers())
        ->xsl('musadm/groups/group_assignment.xsl')
        ->show();
    exit;
}


//Формирование всплывающего окна создания/редактирования
if ($action == 'updateForm') {
    $popupData = Core::factory('Core_Entity');
    $modelId = Core_Array::Get('group_id', 0, PARAM_INT);

    if ($modelId == 0 && !$accessCreate) {
        Core_Page_Show::instance()->error(403);
    }
    if ($modelId != 0 && !$accessEdit) {
        Core_Page_Show::instance()->error(403);
    }

    if ($modelId !== 0) {
        $Group = Core::factory('Schedule_Group', $modelId);
        if (is_null($Group)) {
            Core_Page_Show::instance()->error(404);
        }
        $Group->addEntity($Group->getTeacher());
        $Group->addEntities($Group->getClientList());
    } else {
        $Group = Core::factory('Schedule_Group');
    }

    $Users = User_Controller::factory()
        ->queryBuilder()
        ->open()
            ->where('group_id', '=', ROLE_TEACHER)
            ->orWhere('group_id', '=', ROLE_CLIENT)
        ->close()
        ->where('subordinated', '=', $subordinated)
        ->where('active', '=', 1)
        ->orderBy('surname')
        ->findAll();

    $popupData
        ->addEntity($Group)
        ->addEntities($Users)
        ->xsl('musadm/groups/edit_group_popup.xsl')
        ->show();

    exit;
}

//Обработчик для фильтрации (поиска) клиентов по фио
if ($action === 'groupSearchClient') {
    $Users = new User_Controller(User::current());
    $Users
        ->groupId(ROLE_CLIENT)
        ->filterType(User_Controller::FILTER_NOT_STRICT)
        ->isWithAreaAssignments(false);
    $Users->queryBuilder()
        ->orderBy('surname', 'ASC')
        ->orderBy('name', 'ASC');

    $searchingQuery = Core_Array::Get('surname', '', PARAM_STRING);
    if (!empty($searchingQuery)) {
        $Users->appendFilter('surname', $searchingQuery);
    }

    $Users = $Users->getUsers();

    $outputJson = [];
    foreach ($Users as $User) {
        $jsonUser = new stdClass();
        $jsonUser->id = $User->getId();
        $jsonUser->fio = $User->surname() . ' ' . $User->name();
        $outputJson[] = $jsonUser;
    }

    echo json_encode($outputJson);
    exit;
}

//Создание связей клиентов и группы
if ($action === 'groupCreateAssignments') {
    if (!$accessEdit) {
        Core_Page_Show::instance()->error(403);
    }

    $userIds = Core_Array::Get('userIds', [], PARAM_ARRAY);
    $groupId = Core_Array::Get('groupId', null, PARAM_INT);

    if (is_null($groupId)) {
        Core_Page_Show::instance()->error(403);
    }

    $Group = Core::factory('Schedule_Group')
        ->queryBuilder()
        ->where('id', '=', $groupId)
        ->where('subordinated', '=', $subordinated)
        ->find();

    if (is_null($Group)) {
        Core_Page_Show::instance()->error(403);
    }

    $outputJson = [];
    foreach ($userIds as $id) {
        $User = User_Controller::factory($id);
        if (!is_null($User)) {
            $Group->appendClient($User->getId());
            $jsonUser = new stdClass();
            $jsonUser->id = $User->getId();
            $jsonUser->fio = $User->surname() . ' ' . $User->name();
            $outputJson[] = $jsonUser;
        }
    }

    echo json_encode($outputJson);
    exit;
}

//Удаление существующих связей группы с клиентами
if ($action === 'groupDeleteAssignments') {
    if (!$accessEdit) {
        Core_Page_Show::instance()->error(403);
    }

    $userIds = Core_Array::Get('userIds', [], PARAM_ARRAY);
    $groupId = Core_Array::Get('groupId', null, PARAM_INT);

    if (is_null($groupId)) {
        Core_Page_Show::instance()->error(403);
    }

    $Group = Core::factory('Schedule_Group')
        ->queryBuilder()
        ->where('id', '=', $groupId)
        ->where('subordinated', '=', $subordinated)
        ->find();

    if (is_null($Group)) {
        Core_Page_Show::instance()->error(403);
    }

    $outputJson = [];

    foreach ($userIds as $id) {
        $User = User_Controller::factory($id);
        if (!is_null($User)) {
            $Group->removeClient($User->getId());
            $jsonUser = new stdClass();
            $jsonUser->id = $User->getId();
            $jsonUser->fio = $User->surname() . ' ' . $User->name();
            $outputJson[] = $jsonUser;
        }
    }

    echo json_encode($outputJson);
    exit;
}



if (!$accessRead) {
    Core_Page_Show::instance()->error(403);
}


//Обновление содержимого страницы
if ($action == 'refreshGroupTable') {
    Core_Page_Show::instance()->execute();
    exit;
}