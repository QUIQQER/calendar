<?php

/**
 * Add an event to a calendar
 */

QUI::$Ajax->registerFunction(
    'package_quiqqer_calendar_ajax_addEvent',
    function ($calendarID, $title, $desc, $start, $end) {
        if (is_null($calendarID) || empty($calendarID)) {
            return null;
        }
        $Calendar = new \QUI\Calendar\Calendar((int) $calendarID);
        $eventID = $Calendar->addCalendarEvent($title, $desc, $start, $end);
        return (int) $eventID;
    },
    array('calendarID', 'title', 'desc', 'start', 'end'),
    'Permission::checkAdminUser'
);
