<?php
/**
 * Наблюдатели
 *
 * @author: Kozurev Egor
 * @date: 13.04.2018 13:52
 * @version 20190326
 * @version 20190414
 * @version 20200908 - добавлен новый файл наблюдателей
 */

use Model\Senler;

require_once 'database.php';
require_once 'subordinated.php';
require_once 'events.php';

use Model\User\User_Client;

/**
 * Создание элемента списка "Студия"
 */
Core::attachObserver( 'before.ScheduleArea.save', function( $args ) {
    $area = $args[0];
    $director = User_Auth::current()->getDirector();
    $subordinated = $director->getId();
    $area->renderPath();    //Формирование уникального пути

    //Проверка на существование филиала с таким же путем
    $existsArea = Schedule_Area::query()
        ->where('id', '<>', $area->getId())
        ->where('path', '=', $area->path())
        ->where('subordinated', '=', $subordinated);

    if ($existsArea->count() > 0) {
        throw new Exception('Сохранение невозможно, так как уже существует филлиал с названием: "' . $area->title() . '"');
    }
});


/**
 * Удаление всех связей с удаляемым элементом списка доп. свойства
 */
Core::attachObserver('before.PropertyListValues.delete', function($args) {
    $propertyListValue = $args[0];
    $propertyLists = Property_List::query()
        ->where('property_id', '=', $propertyListValue->propertyId())
        ->where('value_id', '=', $propertyListValue->getId())
        ->findAll();

    foreach ($propertyLists as $val) {
        $val->delete();
    }
});


/**
 * Удаление всех занятий и связей с группами, принадлежащие этому пользователю
 */
Core::attachObserver('before.User.delete', function($args) {
    $user = $args[0];

    //Удаление принадлежности к группам
    $groupsAssignments = Schedule_Group_Assignment::query()
        ->where('user_id', '=', $user->getId())
        ->findAll();

    foreach ($groupsAssignments as $assignment) {
        $assignment->delete();
    }

    //Если пользователь был учителем одной из групп необходимо откорректировать свойство teacher_id
    $groups = Schedule_Group::query()
        ->where('teacher_id', '=', $user->getId())
        ->findAll();

    foreach ($groups as $group) {
        $group->teacherId(0)->save();
    }
});


/**
 * Рекурсивное удаление вложенных макетов и диекторий при удалении директории
 */
Core::attachObserver('before.TemplateDir.delete', function($args) {
    $childrenTemplatesDirs = $args[0]->getChildren();
    foreach ($childrenTemplatesDirs as $childTemplateDir) {
        $childTemplateDir->delete();
    }
});


/**
 * Рекурсивное удаление вложенных макетов при удалении макета
 */
Core::attachObserver('before.Template.Delete', function($args) {
    $childrenTemplates = $args[0]->getChildren();
    foreach ($childrenTemplates as $childTemplate) {
        $childTemplate->delete();
    }
});


/**
 * Удаление всех связей и значений доп. свойств при удалении объекта структуры
 */
Core::attachObserver('before.Structure.delete', function($args) {
    $structure = $args[0];
    Property::purgeForObject($structure);
});


/**
 * Удаление всех связей и значений доп. свойств при удалении объекта Элемента структуры
 */
Core::attachObserver('before.Item.delete', function($args) {
    $Structure = $args[0];
    Property::purgeForObject($Structure);
});


/**
 * Рекурсивное удаление всех дочерних структур и элементов всех уровней вложенности
 */
Core::attachObserver('before.Structure.delete', function($args) {
    $id = $args[0]->getId();

    $childrenItems = Structure_Item::query()
        ->where('parent_id', '=', $id)
        ->findAll();

    $childrenStructures = Structure::query()
        ->where('parent_id', '=', $id)
        ->findAll();

    $children = array_merge($childrenItems, $childrenStructures);
    foreach ($children as $child) {
        $child->delete();
    }
});


/**
 * Проверка на совпадение пути структуры для избежания дублирования пути
 */
Core::attachObserver('before.Structure.save', function($args) {
    $structure = $args[0];

    $rootStructure = Structure::query()
        ->where('path', '=', '')
        ->find();

    $parentId[] = $structure->parentId();

    $coincidingStructures = Structure::query()
        ->where('path', '=', $structure->path())
        ->where('id', '<>', $structure->getId());

    $coincidingItems = Structure_Item::query()
        ->where('path', '=', $structure->getId());

    if ($structure->parentId() == 0) {
        $parentId[] = $rootStructure->getId();
    }

    $countCoincidingStructures = $coincidingStructures
        ->whereIn('parent_id', $parentId)
        ->getCount();

    $countCoincidingItems = $coincidingItems
        ->whereIn('parent_id', $parentId)
        ->getCount();

    if ($countCoincidingItems > 0 || $countCoincidingStructures > 0) {
        throw new Exception('Дублирование путей');
    }
});


/**
 * Проверка на совпадение пути элемента структуры для избежания дублирования пути
 */
Core::attachObserver('before.Item.save', function($args) {
    $structure = $args[0];

    $rootStructure = Structure::query()
        ->where('path', '=', '')
        ->find();

    $parentId[] = $structure->parentId();

    $coincidingStructures = Structure::query()
        ->where('path', '=', $structure->path());

    $coincidingItems = Structure_Item::query()
        ->where('path', '=', $structure->getId())
        ->where('id', '<>', $structure->getId());

    if ($structure->parentId() == 0) {
        $parentId[] = $rootStructure->getId();
    }

    $countCoincidingStructures = $coincidingStructures
        ->whereIn('parent_id', $parentId)
        ->getCount();

    $countCoincidingItems = $coincidingItems
        ->whereIn('parent_id', $parentId)
        ->getCount();

    if ($countCoincidingItems > 0 || $countCoincidingStructures > 0) {
        throw new Exception('Дублирование путей');
    }
});


/**
 * При выставлении консультации с указанием лида создается комментарий
 */
Core::attachObserver('before.ScheduleLesson.save', function($args) {
    $lesson = $args[0];
    $typeId = $lesson->typeId();
    $clientId = $lesson->clientId();

    if (($typeId != Schedule_Lesson::TYPE_CONSULT && $typeId != Schedule_Lesson::TYPE_GROUP_CONSULT) || $clientId == 0) {
        return;
    }

    $client = $lesson->getClient();
    if (empty($client) || empty($client->getId())) {
        return;
    }
    if ($typeId == Schedule_Lesson::TYPE_CONSULT) {
        $lids = [$client];
    } else {
        $lids = $client->getClientList();
    }

    $newStatusId = (new Property())
        ->getByTagName('lid_status_consult')
        ->getPropertyValues(User_Auth::current()->getDirector())[0]
        ->value();

    $commentText = empty($lesson->getId())
        ?   'Консультация назначена на ' . date('d.m.Y', strtotime($lesson->insertDate())) . ' в '
        :   'Время проведение консультации изменилось на ';
    $commentText .= substr($lesson->timeFrom(), 0, 5);
    $commentText .= ', преп. ' . $lesson->getTeacher()->surname();
    $commentText .= ', филиал ' .$lesson->getArea()->title();
    foreach ($lids as $lid) {
        $lid->addComment($commentText);
        if ($newStatusId != 0) {
            $lid->changeStatus($newStatusId);
        }
    }
});


/**
 * Создание комментария у лида о проведенной консультации
 * и изменение статуса, если лид присутствовал
 */
Core::attachObserver('after.ScheduleLesson.makeReport', function($args) {
    $report = $args[0];
    if (($report->typeId() != Schedule_Lesson::TYPE_CONSULT && $report->typeId() != Schedule_Lesson::TYPE_GROUP_CONSULT) || $report->clientId() == 0) {
        return;
    }

    //Создание комментария
    $commentText = 'Консультация ';
    $commentText .= date('d.m.Y', strtotime($report->date()));
    $lesson = Core::factory('Schedule_Lesson', $report->lessonId());
    $commentText .= ' в ' . refactorTimeFormat($lesson->timeFrom()) . ' ' . refactorTimeFormat($lesson->timeTo());
    $commentTextAttendance = $commentText . ' состаялась';
    $commentTextAbsent = $commentText . ' не состаялась';

    $lessonClient = $lesson->getClient();
    if ($report->typeId() == Schedule_Lesson::TYPE_CONSULT) {
        $lids = [$lessonClient];
    } else {
        $lids = $lessonClient->getClientList();
    }

    $propName = 'lid_status_consult_';
    $propNameAttended = $propName . 'attended';
    $propNameAbsent = $propName . 'absent';
    $newStatusAttended = Property_Controller::factoryByTag($propNameAttended)
        ->getPropertyValues(User_Auth::current()->getDirector())[0]
        ->value();
    $newStatusAbsent = Property_Controller::factoryByTag($propNameAbsent)
        ->getPropertyValues(User_Auth::current()->getDirector())[0]
        ->value();

    foreach ($lids as $lid) {
        $lidAttendance = $report->getClientAttendance($lid->getId());
        if (!empty($lidAttendance)) {
            $attendance = $lidAttendance->attendance();
        } else {
            $attendance = $report->attendance();
        }
        if (!empty($attendance)) {
            $statusId = $newStatusAttended;
            $comment = $commentTextAttendance;
        } else {
            $statusId = $newStatusAbsent;
            $comment = $commentTextAbsent;
        }
        $lid->addComment($comment, false);
        if ($statusId != 0) {
            $lid->changeStatus($statusId);
        }
    }
});


/**
 * Задание начения author_id и author_fio для
 */
Core::attachObserver('before.Payment.insert', function($args) {
    /** @var Payment $Payment */
    $payment = $args[0];
    $user = User_Auth::parentAuth();
    if (!is_null($user)) {
        $payment->authorId($user->getId());
        $payment->authorFio($user->getFio());
    }
});


/**
 * Создание связи с филиалом
 */
Core::attachObserver('before.Payment.save', function($args) {
    /** @var Payment $Payment */
    $payment = $args[0];

    //Автоматическое создание связи платежа с филиалом
    if ($payment->areaId() == 0) {
        $paymentUser = $payment->getUser();
        if (!is_null($paymentUser)) {
            $areasAssignments = new Schedule_Area_Assignment();
            /** @var Schedule_Area[] $userAreas */
            $userAreas = $areasAssignments->getAreas($paymentUser, true);
            if (count($userAreas) == 1) {
                $payment->areaId($userAreas[0]->getId());
            }
        }
    }
});


/**
 * Корректировка баланса клиента при сохранении/редактировании платежа типа начисление/списание
 */
Core::attachObserver('before.Payment.save', function($args) {
    /** @var Payment $Payment */
    $payment = $args[0];

    //Корректировка баланса клиента
    if ($payment->isStatusSuccess() &&
        ($payment->type() == Payment::TYPE_INCOME
        || $payment->type() == Payment::TYPE_BONUS_CLIENT
        || $payment->type() == Payment::TYPE_DEBIT
        || $payment->type() == Payment::TYPE_REFUND_CLIENT
        || $payment->type() == Payment::TYPE_CASHBACK)) {

        if ($payment->getId() > 0) {
            /** @var Payment $OldPayment */
            $oldPayment = Core::factory('Payment', $payment->getId());
            if ($oldPayment->status() !== Payment::STATUS_SUCCESS && $payment->isStatusSuccess()) {
                $difference = $payment->value();
            } else {
                $difference = $payment->value() - $oldPayment->value();
            }
        } else {
            $difference = $payment->value();
        }
        $client = User_Client::find($payment->user());
        if (!is_null($client)) {
            $userBalance = $client->getBalance();
            $balanceOld =  $userBalance->getAmount();
            $balanceNew = $payment->type() == Payment::TYPE_DEBIT || $payment->type() == Payment::TYPE_REFUND_CLIENT
                ?   $balanceOld - floatval($difference)
                :   $balanceOld + floatval($difference);
            $userBalance->setAmount($balanceNew);
            $userBalance->save();
        }
    }
});


/**
 * Корректировка баланса клиента при удалении платежа
 * удаление всех свяей с филиалами
 * удаление всех значений доп. свойств
 */
Core::attachObserver('before.Payment.delete', function($args) {
    $payment = $args[0];
    $property = new Property();
    //Корректировка баланса клиента
    if ($payment->type() == Payment::TYPE_INCOME
    || $payment->type() == Payment::TYPE_DEBIT
    || $payment->type() == Payment::TYPE_REFUND_CLIENT
    || $payment->type() == Payment::TYPE_CASHBACK
    || $payment->type() == Payment::TYPE_BONUS_CLIENT) {
        $client = User_Client::find($payment->user());
        if (!is_null($client)) {
            $userBalance = $client->getBalance();
            $balanceNew = $payment->type() == Payment::TYPE_DEBIT || $payment->type() == Payment::TYPE_REFUND_CLIENT
                ?   $userBalance->getAmount() + floatval($payment->value())
                :   $userBalance->getAmount() - floatval($payment->value());
            $userBalance->setAmount($balanceNew);
            $userBalance->save();
        }
    }

    //Удаление связи с филлиалами
    $areasAssignments = new Schedule_Area_Assignment();
    $areasAssignments->clearAssignments($payment);

    //Удаление всех доп. свойств
    Property::purgeForObject($payment);
});


/**
 * Добавление связи задачи с тем филиалом что и клиент, к которому он привязан
 * в случае если для задачи не был заранее прикреплен филиал
 */
Core::attachObserver('before.Task.save', function($args) {
    $task = $args[0];
    if ($task->areaId() == 0 && $task->associate() > 0) {
        $associateClient = Core::factory('User', $task->associate());
        if (!is_null($associateClient)) {
            $assignments = (new Schedule_Area_Assignment())->getAssignments($associateClient);
            if (count($assignments) > 0) {
                (new Schedule_Area_Assignment())->createAssignment($associateClient, $assignments[0]->areaId());
            }
        }
    }
});


/**
 * При удалении статуса лида все лиды имеющие этот статус приобретали статус '0'
 */
Core::attachObserver('after.LidStatus.delete', function($args) {
    $status = $args[0];
    $subordinated = User_Auth::current()->getDirector()->getId();
    $lids = (new Lid())->queryBuilder()
        ->where('subordinated', '=', $subordinated)
        ->where('status_id', '=', $status->getId())
        ->findAll();
    foreach ($lids as $lid) {
        $lid->statusId(0)->save();
    }
});


/**
 * Создание задачи с напоминанием о низком уровне баланса занятий клиента
 */
Core::attachObserver('after.ScheduleLesson.makeReport', function($args) {
    /** @var Schedule_Lesson_Report $report */
    $report = $args[0];
    /** @var Schedule_Lesson $lesson */
    $lesson = Schedule_Lesson::find($report->lessonId());

    /**
     * Проверка остатка занятий у клиента
     * и создание задачи в случае если их осталось менее заданного значения
     */
    if ($report->typeId() == Schedule_Lesson::TYPE_INDIV) {
        $clientLessonsType = User_Balance::LESSONS_INDIVIDUAL;
        $clients = [User_Client::find($report->clientId())];
    } elseif ($report->typeId() == Schedule_Lesson::TYPE_GROUP) {
        $clientLessonsType = User_Balance::LESSONS_GROUP;

        $group = Schedule_Group::find($report->clientId());
        if (is_null($group)) {
            return;
        }
        $clients = $group->getClientList();
    } else {
        return;
    }

    $tomorrow = date('Y-m-d', strtotime('+1 day'));

    /** @var User_Client $client */
    foreach ($clients as $client) {
        //Присутствие клиента на занятии
        $clientAttendance = $report->getClientAttendance($client->getId());
        if (is_null($clientAttendance)) {
            $attendance = false;
        } else {
            $attendance = boolval($clientAttendance->attendance());
        }

        //Создание задачи с напоминанием о низком уровне баланса клиента
        $balance = $client->getBalance();
        if ($balance->getCountLessons($clientLessonsType) <= 0.5) {
            //Отправка пуш уведомления с напоминанием о занятиях
            if (!empty($client->pushId())) {
                $message = [
                    'title' => 'Остаток занятий на вашем балансе: ' . $balance->getCountLessons($clientLessonsType),
                    'body' => 'Внести оплату легко, зайдите в приложение, нажмите кнопку "Пополнить", и после этого в личном кабинете сможете внести средства'
                ];
                try {
                    Push::instance()->notification($message)->send($client->pushId());
                } catch (Exception $e) {
                    $errorMessage = 'User ' . $client->getFio() . ' error: ' . $e->getMessage();
                    Log::instance()->error(Log::TYPE_PUSH, $errorMessage);
                }
            }

            $isIssetTask = Task::query()
                ->where('associate', '=', $client->getId())
                ->where('done', '=', 0)
                ->where('type', '=', Task::TYPE_PAYMENT)
                ->find();

            //Если не существет подобной незакрытой задачи
            if (is_null($isIssetTask)) {
                $task = Task_Controller::factory()
                    ->date($tomorrow)
                    ->areaId($lesson->areaId())
                    ->type(Task::TYPE_PAYMENT)
                    ->priorityId(Task::PRIORITY_HIGH)
                    ->associate($client->getId())
                    ->save();

                $taskNoteText = $client->getFio() . '. Проверить баланс. Напомнить клиенту про оплату.';
                $task->addNote($taskNoteText, 0, date('Y-m-d'));
            }
        }

        /**
         * Проверка на отсутствие на занятии 2 раза подряд
         * и создание задачи с напоминание о звонке
         */
        if ($attendance == false) {
            $clientGroups = Schedule_Group::getClientGroups($client);
            $clientGroupsIds = [];
            foreach ($clientGroups as $group) {
                $clientGroupsIds[] = $group->getId();
            }

            $lastClientReport = Schedule_Lesson_Report::query()
                ->where('id', '<>', $report->getId())
                ->open()
                    ->open()
                        ->where('client_id', '=', $client->getId())
                        ->where('type_id', '=', Schedule_Lesson::TYPE_INDIV)
                    ->close();

            if (count($clientGroupsIds) > 0) {
                $lastClientReport
                    ->open()
                        ->orWhereIn('client_id', $clientGroupsIds)
                        ->where('type_id', '=', Schedule_Lesson::TYPE_GROUP)
                    ->close();
            }

            $lastClientReport = $lastClientReport
                ->close()
                ->orderBy('date', 'DESC')
                ->find();

            if (is_null($lastClientReport)) {
                $isPrevLessonAbsent = false;
            } else {
                $lastReportAttendance = $lastClientReport->getClientAttendance($client->getId());
                if (is_null($lastReportAttendance)) {
                    $isPrevLessonAbsent = false;
                } elseif ($lastReportAttendance->attendance() == 1) {
                    $isPrevLessonAbsent = false;
                } else {
                    $isPrevLessonAbsent = true;
                }
            }

            if ($isPrevLessonAbsent) {
                $isIssetTask = Task::query()
                    ->where('associate', '=', $client->getId())
                    ->where('done', '=', 0)
                    ->where('type', '=', Task::TYPE_SCHEDULE)
                    ->find();

                if (is_null($isIssetTask)) {
                    $task = Task_Controller::factory()
                        ->date($tomorrow)
                        ->type(Task::TYPE_SCHEDULE)
                        ->areaId($lesson->areaId())
                        ->priorityId(Task::PRIORITY_HIGH)
                        ->associate($client->getId())
                        ->save();

                    $taskNoteText = $client->getFio() . ' пропустил(а) два урока подряд. Необходимо связаться.';
                    $task->addNote($taskNoteText, 0, date('Y-m-d'));
                }
            }
        }

    }
});


Core::attachObserver('before.Task.insert', function($args) {
    /** @var Task $task */
    $task = $args[0];

    if ($task->associate() > 0 && $task->areaId() == 0) {
        $client = User::find($task->associate());
        $clientAreas = (new Schedule_Area_Assignment)->getAreas($client, false);
        if (count($clientAreas) == 1) {
            $area = $clientAreas[0];
            $task->areaId($area->getId());
        }
    }
});


Core::attachObserver('after.User.insert', function(array $args): void {
    /** @var User $user */
    $user = $args[0];
    if ($user->isClient()) {
        $balance = new User_Balance();
        Orm::execute('INSERT INTO User_Balance (user_id, individual_lessons_average_price, group_lessons_average_price) VALUES('.$user->getId().', '.$balance->getIndividualLessonsAvg().', '.$balance->getGroupLessonsAvg().')');
    }
});


/**
 * Причисление пользователя к какой-либо группе прав доступа при создании
 */
Core::attachObserver('after.User.insert', function($args){
    switch ($args[0]->groupId())
    {
        case ROLE_DIRECTOR:
            $accessGroupId = 1;
            break;

        case ROLE_MANAGER:
            $accessGroupId = 2;
            break;

        case ROLE_TEACHER:
            $accessGroupId = 3;
            break;

        case ROLE_CLIENT:
            $accessGroupId = 4;
            break;

        default: $accessGroupId = 0;
    }

    $group = Core::factory('Core_Access_Group', $accessGroupId);
    if (!is_null($group)) {
        $group->appendUser($args[0]->getId());
    }
});


/**
 * Создание задачи при выставлении периода отсутствия преподаватея
 */
Core::attachObserver('after.ScheduleAbsent.save', function($args) {
    $absentPeriod = $args[0];
    if ($absentPeriod->typeId() != 1) {
        return;
    }
    $periodUserId = $absentPeriod->objectId();
    $user = User_Controller::factory($periodUserId);
    if ($user->groupId() != ROLE_TEACHER) {
        return;
    }

    $task = Core::factory('Task');
    $task->priorityId(Task::PRIORITY_HIGH);
    $userAreas = (new Schedule_Area_Assignment)->getAreas($user);
    if (count($userAreas) == 1) {
        $task->areaId($userAreas[0]->getId());
    }
    if (!$task->save()) {
        return;
    }

    $taskComment = ($user->groupId() == ROLE_TEACHER ? 'Преподаватель ' : 'Клиент ' ) . $user->getFio() . '. Период отсутствия с '
        . refactorDateFormat($absentPeriod->dateFrom()) . ' ' . refactorTimeFormat($absentPeriod->timeFrom())
        . ' по ' . refactorDateFormat($absentPeriod->dateTo()) . ' ' . refactorTimeFormat($absentPeriod->timeTo())
        . ' проверить расписание: ';

    $teacherSchedule = Schedule_Controller_Extended::getSchedule($user, $absentPeriod->dateFrom(), $absentPeriod->dateTo());
    if (count($teacherSchedule) > 0) {
        foreach ($teacherSchedule as $day) {
            $taskComment .= refactorDateFormat($day->date) . ' ';
            foreach ($day->lessons as $lesson) {
                //Проверка на то что занятие подпадает под время периода отсутствия
                if ((compareDate($day->date, '==', $absentPeriod->dateFrom()) && compareTime($lesson->timeTo(), '<=', $absentPeriod->timeFrom()))
                    || (compareDate($day->date, '==', $absentPeriod->dateTo()) && compareTime($lesson->timeFrom(), '>=', $absentPeriod->timeTo()))) {
                    continue;
                }

                $client = $lesson->getClient();
                $taskComment .= $lesson->getArea()->title() . ' ';
                $taskComment .= refactorTimeFormat($lesson->timeFrom()) . ' - ' . refactorTimeFormat($lesson->timeTo()) . ' ';
                if ($client instanceof User) {
                    $taskComment .= $client->getFio() . '; ';
                } elseif ($client instanceof Schedule_Group) {
                    $taskComment .= $client->title() . '; ';
                }
            }
        }
    }
    $task->addNote($taskComment);
});


/**
 * Удаление занятий из актуального расписания при выставлении периода отсутсвия у клиента
 */
Core::attachObserver('before.ScheduleAbsent.save', function($args) {
    /** @var Schedule_Absent $absentPeriod */
    $absentPeriod = $args[0];
    if ($absentPeriod->typeId() != Schedule_Lesson::TYPE_INDIV) {
        return;
    }
    $user = $absentPeriod->getClient();
    if ($user instanceof User && $user->groupId() == ROLE_CLIENT) {
        $results = Schedule_Lesson::query()
            ->where('client_id', '=', $absentPeriod->objectId())
            ->where('lesson_type', '=', Schedule_Lesson::SCHEDULE_CURRENT)
            ->between('insert_date',$absentPeriod->dateFrom(), $absentPeriod->dateTo())
            ->where('delete_date', 'is','NULL')
            ->findAll();
        foreach ($results as $result) {
            $result->delete();
        }
    }
});


/**
 * Подписка лида на одну из групп рассылок в сенлере
 */
Core::attachObserver('after.Lid.changeStatus', function($args) {
    $lid = $args['Lid'];
    $status = $args['new_status'];
    Senler::setLidGroup($lid, $status);
});

Core::attachObserver('after.Lid.insert', function($args) {
    Senler::setLidGroup($args[0]);
});

Core::attachObserver('before.User.deactivate', function($args) {
    $user = $args[0];
    Senler::setUserGroup($user, Senler_Settings::USER_STATUS_ARCHIVE);
});


/**
 * При сохранении занятия в актуальном графике автоматически выставляет
 */
Core::attachObserver('before.ScheduleLesson.save', function($args) {
    $lesson = $args[0];
    if ($lesson instanceof Schedule_Lesson && empty($lesson->getId())) {
        // Автоматическое формирование значения названия дня недели
        if (empty($lesson->dayName()) && $lesson->lessonType() == Schedule_Lesson::SCHEDULE_CURRENT && !empty($lesson->insertDate())) {
            $lesson->dayName(date('l', strtotime($lesson->insertDate())));
        }
        // Автоматическое формирование класса занятия
        if (empty($lesson->classId()) && !empty($lesson->teacherId())) {
            $teacher = $lesson->getTeacher();
            if (!empty($teacher) && !empty($teacher->getId())) {
                $controller = new User_Controller_Extended($teacher);
                $classId = $controller->getTeacherClassId($lesson->insertDate());
                if (!empty($classId)) {
                    $lesson->classId($classId);
                }
            }
        }
    }
});

/**
 * Подписка/отписка клиента от группы сенлера при посещении занятия
 */
Core::attachObserver('after.ScheduleLesson.makeReport', function($args) {
    $report = $args[0];
    if ($report instanceof Schedule_Lesson_Report && $report->typeId() === Schedule_Lesson::TYPE_INDIV && $report->attendance() == 1) {
        $client = $report->getClient();
        $director = $client->getDirector();

        $vkUserLink = Property_Controller::factoryByTag('vk')->getValues($client)[0]->value();
        $vkUserIdResponse = Vk_Group::getVkId($vkUserLink);
        if (empty($vkUserIdResponse) || $vkUserIdResponse->type !== 'user') {
            return;
        }
        $vkUserId = $vkUserIdResponse->object_id;

        $senlerActivity = Property_Controller::factoryByTag('senler_activity_group')->getValues($director)[0]->value();
        $mainVkGroupId = Property_Controller::factoryByTag('vk_main_group')->getValues($director)[0]->value();

        if (empty($senlerActivity) || empty($mainVkGroupId)) {
            return;
        }

        $mainVkGroup = Core::factory('Vk_Group', $mainVkGroupId);
        if (empty($mainVkGroup)) {
            return;
        }

        $senler = new Senler($mainVkGroup);
        $logMsg = 'Пользователь ' . $client->surname() . ' ' . $client->name() . '; vk: ' . $vkUserLink;
        if ($senler->isSubscriber($vkUserId, $senlerActivity)) {
            $result = $senler->subscribeRemove($vkUserId, $senlerActivity);
            if ($result->success == true) {
                Log::instance()->debug(Log::TYPE_SENLER, $logMsg . ' успешно отписан от группы рассылки #' . $senlerActivity . ' после посещения занятия');
            } else {
                Log::instance()->error(Log::TYPE_SENLER, $logMsg . ' ошибка отписки от группы рассылки #' . $senlerActivity . ' после посещения занятия: ' . $result->error_message);
            }
        } else {
            $result = $senler->subscribe($vkUserId, $senlerActivity);
            if ($result->success == true) {
                Log::instance()->debug(Log::TYPE_SENLER, $logMsg . ' успешно подписан на группу рассылки #' . $senlerActivity . ' после посещения занятия');
            } else {
                Log::instance()->error(Log::TYPE_SENLER, $logMsg . ' ошибка подписки на группу рассылки #' . $senlerActivity . ' после посещения занятия: ' . $result->error_message);
            }
        }
    }
});

/**
 * Подписка/отписка клиента от группы сенлера при удалении отчета о проведении занятия
 */
Core::attachObserver('after.ScheduleLesson.clearReports', function($args) {
    $report = $args[0];
    if ($report instanceof Schedule_Lesson_Report && $report->typeId() === Schedule_Lesson::TYPE_INDIV && $report->attendance() == 1) {
        $client = $report->getClient();
        $director = $client->getDirector();

        $vkUserLink = Property_Controller::factoryByTag('vk')->getValues($client)[0]->value();
        $vkUserIdResponse = Vk_Group::getVkId($vkUserLink);
        if (empty($vkUserIdResponse) || $vkUserIdResponse->type !== 'user') {
            return;
        }
        $vkUserId = $vkUserIdResponse->object_id;

        $senlerActivityRevert = Property_Controller::factoryByTag('senler_activity_revert_group')->getValues($director)[0]->value();
        $mainVkGroupId = Property_Controller::factoryByTag('vk_main_group')->getValues($director)[0]->value();

        if (empty($senlerActivityRevert) || empty($mainVkGroupId)) {
            return;
        }

        $mainVkGroup = Core::factory('Vk_Group', $mainVkGroupId);
        if (empty($mainVkGroup)) {
            return;
        }

        $senler = new Senler($mainVkGroup);
        $logMsg = 'Пользователь ' . $client->surname() . ' ' . $client->name() . '; vk: ' . $vkUserLink;
        if ($senler->isSubscriber($vkUserId, $senlerActivityRevert)) {
            $result = $senler->subscribeRemove($vkUserId, $senlerActivityRevert);
            if ($result->success == true) {
                Log::instance()->debug(Log::TYPE_SENLER, $logMsg . ' успешно отписан от группы рассылки #' . $senlerActivityRevert . ' после удаления отчета');
            } else {
                Log::instance()->error(Log::TYPE_SENLER, $logMsg . ' ошибка отписки от группы рассылки #' . $senlerActivityRevert . ' после удаления отчета: ' . $result->error_message);
            }
        } else {
            $result = $senler->subscribe($vkUserId, $senlerActivityRevert);
            if ($result->success == true) {
                Log::instance()->debug(Log::TYPE_SENLER, $logMsg . ' успешно подписан на группу рассылки #' . $senlerActivityRevert . ' после удаления отчета');
            } else {
                Log::instance()->error(Log::TYPE_SENLER, $logMsg . ' ошибка подписки на группу рассылки #' . $senlerActivityRevert . ' после удаления отчета: ' . $result->error_message);
            }
        }
    }
});

/**
 * При создании пользователя
 * Если создается пользователь с указанным email - автоматически форимруется пароль
 */
Core::attachObserver('before.User.save', function ($args) {
    $user = $args[0];
    if ($user instanceof User && empty($user->getId()) && !empty($user->email()) && $user->groupId() === ROLE_CLIENT) {
        global $CFG;
        if (empty($user->password())) {
            $password = uniqidReal(8);
            $user->password($password);
        }

        $subject = 'Регистрация Musicmetod';
        $message = (new Core_Entity())
            ->addSimpleEntity('auth_link', mapping('auth', [
                'token' => $user->getAuthToken()
            ], MAPPING_CLIENT_LC))
            ->addSimpleEntity('password', $password ?? '')
            ->addSimpleEntity('wwwroot', $CFG->wwwroot)
            ->addSimpleEntity('client_lk_link', $CFG->client_lk_link)
            ->addSimpleEntity('service_name', 'Musicmetod')
            ->xsl('musadm/mail/new_user.xsl')
            ->show(false);

        $mail = \Model\Mail::factory();
        $mail->addAddress($user->email(), $user->getFio());
        $mail->Subject = $subject;
        $mail->msgHTML($message);
        $mail->send();
    }
});

/**
 * Начисление кэшбэка после депозита
 */
Core::attachObserver('after.user.deposit', function($args) {
    /** @var Payment $payment */
    $payment = $args['payment'];
    $client = $payment->getUser();

    $cashBack = Property_Controller::factoryByTag('payment_cashback');
    $director = $client->getDirector();
    $cashBack = $cashBack->getValues($director)[0]->value();

    if ($cashBack > 0) {
        $bonuses = intval($payment->value() * ($cashBack / 100));
        if ($bonuses > 0) {
            $cashBackPayment = new Payment();
            $cashBackPayment->description('Начисление бонусов');
            $cashBackPayment->value($bonuses);
            $cashBackPayment->type(Payment::TYPE_CASHBACK);
            $cashBackPayment->user($payment->user());
            $cashBackPayment->save();
        }
    }
});

/**
 * Создание задачи с напоминанием о выходе ученика
 * если ученик сам себе создает период отсутствия
 */
Core::attachObserver('after.ScheduleAbsent.save', function($args) {
    /** @var Schedule_Absent $absent */
    $absent = $args[0];

    $user = User_Auth::current();
    if ($user instanceof User && $user->groupId() == ROLE_CLIENT && $absent->typeId() == Schedule_Lesson::TYPE_INDIV) {
        $client = $absent->getClient();
        Task::addClientReminderTask($client, $absent->dateTo());
    }
});

/**
 *
 */
Core::attachObserver('after.ScheduleLesson.insert', function(array $args) {
    /** @var Schedule_Lesson $lesson */
    $lesson = $args[0];
    if ($lesson->typeId() == Schedule_Lesson::TYPE_INDIV && User_Auth::current()->getId() == $lesson->clientId()) {
        $client = User_Client::find(User_Auth::current()->getId());
        $balance = $client->getBalance();
        if ($balance->getIndividualLessonsCount() < 1) {
            //Создание задачи с напоминанием о самостоятельной постановки в график при нулевом балансе
            $isIssetTask = Task::query()
                ->where('associate', '=', $client->getId())
                ->where('done', '=', 0)
                ->where('type', '=', Task::TYPE_PAYMENT_WHEN_IN_SCHEDULE)
                ->find();

            //Если не существет подобной незакрытой задачи
            if (is_null($isIssetTask)) {
                $task = Task_Controller::factory()
                    ->date(date('Y-m-d'))
                    ->areaId($lesson->areaId())
                    ->type(Task::TYPE_PAYMENT_WHEN_IN_SCHEDULE)
                    ->priorityId(Task::PRIORITY_HIGH)
                    ->associate($client->getId())
                    ->save();

                $taskNoteText = $client->getFio() . '. Ученик поставил себя в график, на балансе не хватает купленных уроков. Контроль.';
                $task->addNote($taskNoteText, 0, date('Y-m-d'));
            }
        }
    }
});

/**
 * При добавлении комментария к преподу необходимо создать пост с тем же сообщением
 */
Core::attachObserver('after.UserCommentAssignment.insert', function(array $args) {
    /** @var User_Comment_Assignment $comment */
    $assignment = $args[0];
    $user = User::find($assignment->objectId());
    if ($user instanceof User && $user->isTeacher()) {
        /** @var Comment|null $comment */
        $comment = Comment::find($assignment->commentId());
        if (!is_null($comment)) {
            $post = new Post();
            $post->title = 'Преподаватель ' . $user->getFio();
            $post->content = $comment->text();
            $post->author_id = $comment->authorId();
            $post->author_name = $comment->authorFullname();
            $post->save();
        }
    }
});