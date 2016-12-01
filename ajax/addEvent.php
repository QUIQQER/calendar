<?php

/**
 * Add an event to a calendar
 */

QUI::$Ajax->registerFunction(
    'package_quiqqer_calendar_ajax_addEvent',
    function ($calendarID, $title, $desc, $start, $end) {
        $calendar = new \QUI\Calendar\Calendar($calendarID);
        $calendar->addCalendarEvent($title, $desc, $start, $end);
    },
    array('calendarID', 'title', 'desc', 'start', 'end'),
    'Permission::checkAdminUser'
);
