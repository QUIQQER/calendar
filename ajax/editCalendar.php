<?php

/**
 * Edits a calendars values
 *
 * @param int $calendarID   - The ID of the calendar to edit.
 * @param String $name      - The new name of the calendar.
 * @param int|null $userid  - The user ID of the new owner. Null for global calendar.
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
