<?php

/**
 * Add an event to a calendar
 */

QUI::$Ajax->registerFunction(
    'package_quiqqer_calendar_ajax_addEvent',
    function ($title, $desc, $start, $end, $calendarID) {
        $calendar = new \QUI\Calendar\Calendar($calendarID);
        $calendar->addCalendarEvent($title, $desc, $start, $end);
    },
    array('title', 'desc', 'start', 'end', 'calendarID'),
    'Permission::checkAdminUser'
);
