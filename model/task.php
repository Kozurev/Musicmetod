<?php
/**
 * Created by PhpStorm.
 * User: Kozurev Egor
 * Date: 16.05.2018
 * Time: 16:47
 */

class Task extends Task_Model
{
    public function __construct(){}


    public function getNotes()
    {
        $aoNotes = Core::factory("Task_Note")->where("task_id", "=", $this->id)->findAll();
        return $aoNotes;
    }


    public function save($obj = null)
    {
        Core::notify(array(&$this), "beforeTaskSave");
        if($this->date == "")   $this->date = date("Y-m-d");
        parent::save();
        Core::notify(array(&$this), "afterTaskSave");
        return $this;
    }


    public function delete($obj = null)
    {
        Core::notify(array(&$this), "beforeTaskDelete");
        parent::delete();
        Core::notify(array(&$this), "afterTaskDelete");
    }

}