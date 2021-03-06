<?php
/**
 * Файл с дополнительно-подключаемыми файлами
 *
 * @author: BadWolf
 * @date 08.05.2018 13:27
 * @version 2019-05-21
 * @version 2019-07-15
 * @version 2019-08-03
 * @version 2020-03-19
 */

require_once ROOT . '/model/rest/controller.php';
require_once ROOT . '/model/REST.php';
require_once ROOT . '/lib/functions.php';
require_once ROOT . '/lib/schedule_functions.php';
require_once ROOT . '/model/comment/model.php';
require_once ROOT . '/model/comment.php';

spl_autoload_register(function ($className) {
    $classSegments = explode('\\', $className);
    if ($classSegments[0] == 'Model') {
        $classSegments = [end($classSegments)];
    }
    Core::requireClass(implode('_', $classSegments));
});


register_shutdown_function(function() {
    $error = error_get_last();

    //Список паттернов файлов, из которых ошибки не логируются, а то всё время прилетают ошибки с файла шрифтов
    $ignoredFiles = [
        '/assets/'
    ];
    $isIgnore = false;
    foreach ($ignoredFiles as $pattern) {
        if (preg_match($pattern, $error['file'])) {
            $isIgnore = true;
        }
    }
    if ($isIgnore) {
        return;
    }

    if (!is_null($error)) {
        $errorLogMessage = 'Error in file ' . $error['file'] . ' on line ' . $error['line'] . ':' . PHP_EOL . $error['message'] . PHP_EOL;
        Log::instance()->error(Log::TYPE_CORE, $errorLogMessage);

        global $CFG;
        if ($CFG->debug) {
            dd($error);
        }
    }
});