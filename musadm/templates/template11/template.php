<?php
/**
 * Created by PhpStorm.
 * User: Kozurev Egor
 * Date: 18.10.2018
 * Time: 16:53
 */
?>

<input type='hidden' id='taskAfterAction' value='tasks' />

<div class="tasks">
    <?php
    Core_Page_Show::instance()->execute();
    ?>
</div>