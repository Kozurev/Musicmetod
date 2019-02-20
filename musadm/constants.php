<?php
/**
 * Для более качественной читабельности кода и простоты было принято решение
 * создать данный файл с необходимыми константами.
 * Благодаря константам код становится более интуитивно понятным, да и с точки зрения проектирования это верное решение
 *
 * @author Bad Wolf
 * @date 18.02.2019 9:42
 * @version 20190220
 */


/**
 * Константы для класса Core_Array: возможные типы возвращаемых данных
 * Применяются, в основном, для осуществления контроля получаемых данных из AJAX-запросов
 * для, хотя бы частичной, защиты от SQL инъекций.
 *
 * @date 18.02.2019 9:43
 */
define( 'PARAM_INT',    'int' );
define( 'PARAM_FLOAT',  'float' );
define( 'PARAM_STRING', 'string' );
define( 'PARAM_BOOL',   'bool' );
define( 'PARAM_ARRAY',  'array' );


/**
 * Идентификаторы групп пользователей
 * В системе часто используется механизм проверка прав доступа к тому или иному разделу и дабы избежать
 * использования челочисленных идентификаторов групп пользователей (которые могут измениться) которые стороннему
 * разработчику мало о чем могут сказать было принято решение использовать константы с более информативными нахваниями
 *
 * @date 20.02.2019 10:22
 */
define( 'ROLE_ADMIN',       1 );
define( 'ROLE_MANAGER',     2 );
define( 'ROLE_TEACHER',     4 );
define( 'ROLE_CLIENT',      5 );
define( 'ROLE_DIRECTOR',    6 );