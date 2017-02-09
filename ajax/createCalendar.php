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
        $User = null;
        if (!is_null($userid) && !empty($userid)) {
            $User = QUI::getUsers()->get($userid);
        }
        \QUI\Calendar\Handler::createCalendar($name, $User);
    },
    array('name', 'userid'),
    'Permission::checkAdminUser'
);
