<?php
/**
 * Для более качественной читабельности кода и простоты было принято решение
 * создать данный файл с необходимыми константами.
 * Благодаря константам код становится более интуитивно понятным, да и с точки зрения проектирования это верное решение
 *
 * @author Bad Wolf
 * @date 18.02.2019 9:42
 * @version 20190220
 * @version 20190402
 * @version 20190403
 */


/**
 * Константы для класса Core_Array: возможные типы возвращаемых данных
 * Применяются, в основном, для осуществления контроля получаемых данных из AJAX-запросов
 * для, хотя бы частичной, защиты от SQL инъекций.
 *
 * @date 18.02.2019 9:43
 * @version 20190403
 */
define('PARAM_INT',         'int');
define('PARAM_FLOAT',       'float');
define('PARAM_STRING',      'string');
define('PARAM_BOOL',        'bool');
define('PARAM_ARRAY',       'array');
define('PARAM_DATE',        'date');
define('PARAM_TIME',        'time');
define('PARAM_DATETIME',    'datetime');


/**
 * Идентификаторы групп пользователей
 * В системе часто используется механизм проверка прав доступа к тому или иному разделу и дабы избежать
 * использования челочисленных идентификаторов групп пользователей (которые могут измениться) которые стороннему
 * разработчику мало о чем могут сказать было принято решение использовать константы с более информативными нахваниями
 *
 * @date 20.02.2019 10:22
 */
define('ROLE_ADMIN',       1);
define('ROLE_DIRECTOR',    6);
define('ROLE_MANAGER',     2);
define('ROLE_TEACHER',     4);
define('ROLE_CLIENT',      5);


/**
 * Идентификаторы моделей
 * Подобные идентификаторы можно было бы брать из таблицы Admin_Form_Modelname
 * но так как данная таблица устарела и не используется то было приянто решение ввести отдельные константы
 * Используются данные идентификаторы, в основном, в связующих таблицах
 *
 * @date 27.09.2019 13:35
 */
define('MODEL_UNDEFINED',       0);
define('MODEL_USER_ID',         1);
define('MODEL_LID_ID',          2);
define('MODEL_TASK_ID',         3);
define('MODEL_PAYMENT_ID',      4);
define('MODEL_COMMENT_ID',      5);
define('MODEL_CERTIFICATE_ID',  6);


/**
 * Константы расписания
 *
 * @date 24.01.2020 01:22
 */
define('SCHEDULE_LESSON_INTERVAL', '00:10:00');
define('SCHEDULE_GAP', '00:10:00');
define('SCHEDULE_TIME_START', '09:00:00');
define('SCHEDULE_TIME_END', '22:00:00');
define('SCHEDULE_MAX_ACTION_TIME_CLIENT', '18:00:00');
define('SCHEDULE_MAX_ACTION_TIME_TEACHER', '23:59:00');
define('SCHEDULE_DEFAULT_LESSON_DURATION', 50);

define('MAPPING_BASE', 0);
define('MAPPING_CLIENT_LC', 1);

define('ADMIN_EMAIL', 'creative27016@gmail.com');