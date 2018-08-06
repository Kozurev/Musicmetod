<?php
/**
 * Created by PhpStorm.
 * User: Egor
 * Date: 01.05.2018
 * Time: 11:22
 */

class Schedule_Area extends Core_Entity
{
    protected $id;
    protected $title;
    protected $count_classess;
    protected $path;
    protected $sorting;

    public function __construct(){}

    public function getId()
    {
        return $this->id;
    }


    public function title($val = null)
    {
        if(is_null($val))   return $this->title;
        if(strlen($val) > 255)
            die(Core::getMessage("TOO_LARGE_VALUE", array("title", "Schedule_Area", 255)));
        $this->title = strval($val);
        return $this;
    }


    public function countClassess($val = null)
    {
        if(is_null($val))   return $this->count_classess;
        $this->count_classess = intval($val);
        return $this;
    }


    public function path($val = null)
    {
        if(is_null($val))  return $this->path;
        if(strlen($val) > 255)
            die(Core::getMessage("TOO_LARGE_VALUE", array("path", "Schedule_Area", 255)));
        $this->path = strval($val);
        return $this;
    }


    public function sorting($val = null)
    {
        if(is_null($val))	return $this->sorting;
        //if(!is_int($val))   die(Core::getMessage("INVALID_TYPE", array("sorting", "Structure", "int")));
        $this->sorting = intval($val);
        return $this;
    }

}