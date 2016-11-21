<?php

/**
 * Create a new calendar
 */

QUI::$Ajax->registerFunction(
    'package_quiqqer_calendar_ajax_createCalendar',
    function ($name, $userid) {
        $user = null;
        if (!is_null($userid) && !empty($userid)) {
            $user = new \QUI\Users\User($userid, new \QUI\Users\Manager());
        }
        \QUI\Calendar\Handler::createCalendar($name, $user);
    },
    array('name', 'userid'),
    'Permission::checkAdminUser'
);
