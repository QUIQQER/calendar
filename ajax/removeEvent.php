<?php

/**
 * Removes event with ID eventID from the calendar with ID calendarID
 *
 * @param int $calendarID - The ID of the calendar where the event is in
 * @param int $eventID    - The ID of the event that should be removed
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_calendar_ajax_removeEvent',
    function ($calendarID, $eventID) {
        $Calendar = \QUI\Calendar\Handler::getCalendar($calendarID);
        $Calendar->checkInternal();

        $Calendar->removeCalendarEvent($eventID);
    },
    ['calendarID', 'eventID'],
    'Permission::checkUser'
);
