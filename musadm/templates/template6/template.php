<?php

Core_Page_Show::instance()->css( "/templates/template6/css/style.css" );

//echo "<input type='hidden' id='taskAfterAction' value='balance' />";

global $CFG;

$Areas = Core::factory( 'Schedule_Area' )->getList();
$Instruments = Core::factory( 'Property' )->getByTagName( 'instrument' )->getList();
$Teachers = Core::factory( 'Property' )->getByTagName( 'teachers' )->getList();

Core::factory( 'Core_Entity' )
    ->addSimpleEntity( 'wwwroot', $CFG->rootdir )
    ->addEntities( $Areas )
    ->addEntities( $Instruments, 'property_value' )
    ->addEntities( $Teachers, 'property_value' )
    ->xsl( 'musadm/users/client_filter.xsl' )
    ->show();

echo "<div class='users'>";
Core_Page_Show::instance()->execute();
echo "</div>";
?>
