<?php

/**
 * Edits a calendar
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_calendar_ajax_editCalendar',
    function ($calendarID, $name, $userid) {
        $calendar = new \QUI\Calendar\Calendar($calendarID);

        if (is_null($userid) || empty($userid)) {
            $user = null;
        } else {
            $user = QUI::getUsers()->get($userid);
        }

        $calendar->editCalendar($name, $user);
    },
    array('calendarID', 'name', 'userid'),
    'Permission::checkAdminUser'
);
