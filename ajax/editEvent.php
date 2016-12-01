<?php

/**
 * Edits a calendar
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_calendar_ajax_editEvent',
    function ($calendarID, $eventID, $title, $desc, $start, $end) {
        $calendar = new \QUI\Calendar\Calendar($calendarID);
        $calendar->editCalendarEvent($eventID, $title, $desc, $start, $end);
    },
    array('calendarID', 'eventID', 'title', 'desc', 'start', 'end'),
    'Permission::checkAdminUser'
);
