<?php
/**
 * Created by PhpStorm.
 * User: Kozurev Egor
 * Date: 11.04.2018
 * Time: 21:21
 */

//Объект, содержащий основные конфигурации системы которые не должны зависить от базы данных
$CFG = new stdClass();

//Корневой каталог
$CFG->rootdir = "";

//Список индексируемых объектов как элементы структур
$CFG->items_mapping = array(
    "Structure_Item"    =>  array(
        "parent"    =>  "parent_id",
        "index"     =>  "path",
        "active"    =>  true
    ),
    "User_Group"        =>  array(
        "index"     =>  "path",
        "active"    =>  false
    ),
//    "User"  =>  array(
//        "parent"    => "group_id",
//        "index"     =>  "id",
//        "active"    =>  true
//    ),
);