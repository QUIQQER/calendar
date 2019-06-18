<?php

/**
 * Add an event to a calendar
 *
 * @param int $calendarID - The calendar the event is in
 * @param String $title - The title of the event
 * @param String $desc - The description of the event
 * @param int $start - The start time of the event as UNIX timestamp
 * @param int $end - The end time of the event as UNIX timestamp
 *
 * @return int $eventID   - The ID the event got assigned
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_calendar_ajax_addEvent',
    function ($calendarID, $title, $desc, $start, $end, $eventurl) {
        if (is_null($calendarID) || empty($calendarID)) {
            return null;
        }

        $Calendar = \QUI\Calendar\Handler::getCalendar($calendarID);

        $Calendar->checkInternal();

        $eventID = $Calendar->addCalendarEvent($title, $desc, $start, $end, $eventurl);

        return (int)$eventID;
    },
    array('calendarID', 'title', 'desc', 'start', 'end', 'eventurl')
);
