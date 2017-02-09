<?php

/**
 * Edits an event
 *
 * @param int $calendarID - The calendar the event to edit is in
 * @param String $title   - The new title of the event
 * @param String $desc    - The new description of the event
 * @param int $start      - The new start time of the event as UNIX timestamp
 * @param int $end        - The new end time of the event as UNIX timestamp
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_calendar_ajax_editEvent',
    function ($calendarID, $eventID, $title, $desc, $start, $end) {
        $Calendar = new \QUI\Calendar\Calendar($calendarID);
        $Calendar->editCalendarEvent($eventID, $title, $desc, $start, $end);
    },
    array('calendarID', 'eventID', 'title', 'desc', 'start', 'end'),
    'Permission::checkAdminUser'
);
