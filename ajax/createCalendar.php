<?php

/**
 * Creates a new calendar
 *
 * @param String $name     - The name of the calendar
 * @param int|null $userid - The ID of the owner. Null for global calendar.
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_calendar_ajax_createCalendar',
    function ($name, $userid) {
        $user = null;
        if (!is_null($userid) && !empty($userid)) {
            $user = QUI::getUsers()->get($userid);
        }
        \QUI\Calendar\Handler::createCalendar($name, $user);
    },
    array('name', 'userid'),
    'Permission::checkAdminUser'
);
