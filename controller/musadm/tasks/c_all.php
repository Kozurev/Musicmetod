<?php
/**
 * Created by PhpStorm.
 * User: Kozurev Egor
 * Date: 16.05.2018
 * Time: 17:12
 */

$currentDate = date("Y-m-d");

$aoTasks = Core::factory("Task")
//    ->where("done_date", "IS", Core::unchanged("NULL"))
//    ->where("done_date", "=", $currentDate, "OR")
    ->orderBy("date", "DESC")
    ->findAll();
$aoTypes = Core::factory("Task_Type")->findAll();

foreach ($aoTasks as $task)
{
    $aoTaskNotes = $task->getNotes();
    foreach ($aoTaskNotes as $note)
    {
        $note->addEntity($note->getAuthor());
        //$note->date(refactorDateFormat($note->date()));
    }
    $task->addEntities($aoTaskNotes);
    $task->date(refactorDateFormat($task->date()));
}

Core::factory("Core_Entity")
    ->addEntities($aoTasks)
    ->addEntities($aoTypes)
    ->addEntity(
        Core::factory("Core_Entity")
            ->name("table_name")
            ->value("all")
    )
    ->xsl("musadm/tasks/all.xsl")
    ->show();