<?php

/**
 * Add an event to a calendar
 */

QUI::$Ajax->registerFunction(
    'package_quiqqer_calendar_ajax_addEvent',
    function ($calendarID, $title, $desc, $start, $end) {
        try {
            if (is_null($calendarID) || empty($calendarID)) {
                return null;
            }
            $Calendar = new \QUI\Calendar\Calendar((int) $calendarID);
            $eventID = $Calendar->addCalendarEvent($title, $desc, $start, $end);
            return (int) $eventID;
        } catch (\Exception $ex) {
            return "Error!: " . $ex->getMessage();
        }
    },
    array('calendarID', 'title', 'desc', 'start', 'end'),
    'Permission::checkAdminUser'
);
