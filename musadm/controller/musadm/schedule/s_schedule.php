<?php


$oUser = Core::factory("User")->getCurrent();

if($oUser != true)
{
    $this->error404();
}


$breadcumbs[0] = new stdClass();
$breadcumbs[0]->title = "Расписание";
$breadcumbs[0]->active = 1;

if( $this->oStructureItem != false )
{
    $breadcumbs[1] = new stdClass();
    $breadcumbs[1]->title = $this->oStructureItem->title();
    $breadcumbs[1]->active = 1;
}

$this->setParam( "body-class", "body-green" );
$this->setParam( "title-first", "РАСПИСАНИЕ" );

if( $this->oStructureItem != false )
{
    $this->setParam( "title-second", $this->oStructureItem->title() );
    $this->setParam( "breadcumbs", $breadcumbs );
}
else 
{
    $this->setParam( "title-second", "Расписание" );
    $this->setParam( "breadcumbs", $breadcumbs );
}



$action = Core_Array::getValue($_GET, "action", null);


if($action === "getScheduleAbsentPopup")
{
    $clientId = Core_Array::getValue($_GET, "client_id", 0);
    $typeId = Core_Array::getValue($_GET, "type_id", 0);

    Core::factory("Core_Entity")
        ->addSimpleEntity( "clientid", $clientId )
        ->addSimpleEntity( "typeid", $typeId )
        ->xsl("musadm/schedule/absent_popup.xsl")
        ->show();

    exit;
}


if($action === "getScheduleLessonPopup")
{
    $classId =      Core_Array::getValue($_GET, "class_id", 0);
    $lessonType =   Core_Array::getValue($_GET, "model_name", "");
    $date =         Core_Array::getValue($_GET, "date", 0);
    $areaId =       Core_Array::getValue($_GET, "area_id", 0);

    $dayName =  new DateTime($date);
    $dayName =  $dayName->format("l");

    $period = "00:15:00";       //Временной промежуток (временное значение одной ячейки)
    if(defined("SCHEDULE_DELIMITER") != "")   $period = SCHEDULE_DELIMITER;

    $output = Core::factory("Core_Entity")
        ->addSimpleEntity( "class_id", $classId )
        ->addSimpleEntity( "date", $date )
        ->addSimpleEntity( "area_id", $areaId )
        ->addSimpleEntity( "day_name", $dayName )
        ->addSimpleEntity( "period", $period)
        ->addSimpleEntity( "lesson_type", $lessonType );

    $aoUsers = Core::factory("User")
        ->where("active", "=", 1)
        ->where("group_id", ">", 3)
        ->orderBy("surname", "ASC")
        ->findAll();

    $aoGroups = Core::factory("Schedule_Group")->findAll();
    $aoLessonTypes = Core::factory("Schedule_Lesson_Type")->findAll();

    $output
        ->addEntities($aoUsers)
        ->addEntities($aoGroups)
        ->addEntities($aoLessonTypes);

    if($lessonType == "2")       $output->addSimpleEntity( "schedule_type", "актуальное" );
    elseif($lessonType == "")   $output->addSimpleEntity( "schedule_type", "основное" );

    $output
        ->xsl("musadm/schedule/new_lesson_popup.xsl")
        ->show();

    exit;
}


if($action === "teacherReport")
{
    $lessonId =     Core_Array::getValue($_GET, "lesson_id", 0);
    $lessonType =   Core_Array::getValue($_GET, "lesson_type", 0);
    $attendance =   Core_Array::getValue($_GET, "attendance", 0);
    $teacherId =    Core_Array::getValue($_GET, "teacher_id", 0);
    $clientId =     Core_Array::getValue($_GET, "client_id", 0);
    $typeId =       Core_Array::getValue($_GET, "type_id", 0);
    $date =         Core_Array::getValue($_GET, "date", 0);

    /**
     * Проверка во избежание дублирование отчетов
     */
    $Lesson = Core::factory( "Schedule_Lesson", $lessonId );
    if( $Lesson->isReported( $date ) )  exit;

    /**
     * Создание отчета
     */
    $Report = Core::factory("Schedule_Lesson_Report")
        ->lessonId($lessonId)
        ->teacherId($teacherId)
        ->typeId($typeId)
        ->date($date)
        ->attendance($attendance)
        ->lessonType($lessonType)
        ->clientId($clientId);
    $Report->save();

    $oLesson = Core::factory( "Schedule_Lesson", $lessonId);
    $clients = array();

    if($oLesson->typeId() != 2)
    {
        $clients[] = $oLesson->getClient();
    }
    else
    {
        $oGroup = $oLesson->getCLient();
        $clients = $oGroup->getClientList();
    }


    if($oLesson->typeId() == 2)
        $propertyId = 14;
    else
        $propertyId = 13;

    $oProperty = Core::factory("Property", $propertyId);

    foreach ($clients as $client)
    {
        $clientCountLessons = $oProperty->getPropertyValues($client)[0];
        $count = floatval( $clientCountLessons->value() );
        if($attendance == 1)    $count--;
        else $count -= 0.5;
        $clientCountLessons->value($count)->save();
    }

    echo "0";
    exit;
}


if($action === "deleteReport")
{
    $reportId =     Core_Array::getValue($_GET, "report_id", 0);
    $lessonId =     Core_Array::getValue($_GET, "lesson_id", 0);
    $lessonType =   Core_Array::getValue($_GET, "lesson_type", 0);

    $oReport = Core::factory("Schedule_Lesson_Report", $reportId);
    $oLesson = Core::factory( "Schedule_Lesson", $lessonId);

    $attendance = $oReport->attendance();
    $clients = array();

    if($oLesson->typeId() != 2)
    {
        $clients[] = $oLesson->getClient();
    }
    else
    {
        $oGroup = $oLesson->getCLient();
        $clients = $oGroup->getClientList();
    }


    if($oLesson->typeId() == 2)
        $propertyId = 14;
    else
        $propertyId = 13;

    $oProperty = Core::factory("Property", $propertyId);

    foreach ($clients as $client)
    {
        $clientCountLessons = $oProperty->getPropertyValues($client)[0];
        $count = floatval( $clientCountLessons->value() );
        if($attendance == 1)    $count++;
        else $count += 0.5;
        $clientCountLessons->value($count)->save();
    }

    $oReport->delete();

    echo "0";
    exit;
}


if($action === "getclientList")
{
    $type = Core_Array::getValue($_GET, "type", 0);
    if($type == 2)
    {
        $aoGroups = Core::factory("Schedule_Group")->orderBy("title")->findAll();
        foreach ($aoGroups as $group)
            echo "<option value='".$group->getId()."'>" . $group->title() . "</option>";
    }
    else
    {
        $aoUsers = Core::factory("User")
            ->where("active", "=", 1)
            ->where("group_id", "=", 5)
            ->orderBy("surname", "ASC")
            ->findAll();

        foreach ($aoUsers as $user)
            echo "<option value='".$user->getId()."'>". $user->surname() . " " . $user->name() ."</option>";
    }

    exit;
}


if($action === "markDeleted")
{
    $lessonId =     Core_Array::getValue($_GET, "lessonid", 0);
    $deleteDate =   Core_Array::getValue($_GET, "deletedate", "");

    $oLesson = Core::factory("Schedule_Lesson", $lessonId);
    $oLesson->markDeleted($deleteDate);
    exit;
}


if($action === "markAbsent")
{
    $lessonId = Core_Array::getValue($_GET, "lessonid", 0);
    $date =     Core_Array::getValue($_GET, "date", "");

    Core::factory("Schedule_Lesson", $lessonId)->setAbsent($date);
    exit;
}


if( $action === "getScheduleChangeTimePopup" )
{
    $id =   Core_Array::getValue($_GET, "id", 0);
    $date = Core_Array::getValue($_GET, "date", "");

    Core::factory("Core_Entity")
        ->addSimpleEntity( "lesson_id", $id )
        ->addSimpleEntity( "date", $date )
        ->xsl( "musadm/schedule/time_modify_popup.xsl" )
        ->show();

    exit;
}


if( $action === "saveScheduleChangeTimePopup" )
{
    $lessonId = Core_Array::getValue( $_GET, "lesson_id", 0 );
    $date =     Core_Array::getValue( $_GET, "date", date( "Y-m-d" ) );
    $timeFrom = Core_Array::getValue( $_GET, "time_from", "" );
    $timeTo =   Core_Array::getValue( $_GET, "time_to", "" );

    $timeFrom .= ":00";
    $timeTo .= ":00";

    Core::factory( "Schedule_Lesson", $lessonId )->modifyTime( $date, $timeFrom, $timeTo );
    exit;
}


if($action === "new_task_popup")
{
    $aoTaskTypes = Core::factory("Task_Type")->findAll();
    $date = date("Y-m-d");

    Core::factory("Core_Entity")
        ->addEntities($aoTaskTypes)
        ->addSimpleEntity( "date", $date )
        ->xsl("musadm/schedule/new_task_popup.xsl")
        ->show();

    exit;
}


if($action === "save_task")
{
    $authorId = $oUser->getId();
    $noteDate = date("Y-m-d");
    $note = Core_Array::getValue($_GET, "text", "");

    $oTask = Core::factory("Task")
        ->date($noteDate);

    $oTask = $oTask->save();
    $oTask->addNote( $note );

    echo "0";
    exit;
}


if( $action === "addAbsentTask" )
{
    $dateTo =   Core_Array::getValue( $_GET, "date_to", "" );
    $clientId = Core_Array::getValue( $_GET, "client_id", 0 );

    $oTask = Core::factory( "Task" )
        ->date( $dateTo )
        ->type( 3 );

    $oTask = $oTask->save();

    $oAuthor = Core::factory( "User", $clientId );
    $fio = $oAuthor->surname() . " " . $oAuthor->name();
    $text = $fio . ", отсутствовал. Уточнить насчет дальнейшего графика.";
    $oTask->addNote( $text );
    exit;
}


if($action === "getSchedule")
{
    $this->execute();
    exit;
}

