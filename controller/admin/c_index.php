<?php

if(isset($_GET["menuTab"]) && isset($_GET["menuAction"]))
{
	$tabName = $_GET["menuTab"];
	$action = $_GET["menuAction"];
	$objectName = "Admin_Menu_" . $tabName;

	$oTab = Core::factory($objectName);

	if($oTab === false) 
		die("<br>Ошибка: неопознанная вкладка меню");

	$oTab->$action($_GET);


	if(isset($_GET["ajax"]))
	{
		echo "<pre>";
		//print_r($_GET);
		echo "</pre>";	
	}
	
}