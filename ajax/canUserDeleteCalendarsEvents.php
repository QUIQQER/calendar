<?php

/**
 * Can the current User delete events from the calendar with the given ID?
 *
 * @param int $calendarID - The ID of the calendar to check if events can be deleted.
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_calendar_ajax_canUserDeleteCalendarsEvents',
    function ($calendarID) {
        $Calendar = \QUI\Calendar\Handler::getCalendar($calendarID);

        return $Calendar->hasPermission(\QUI\Calendar\AbstractCalendar::PERMISSION_REMOVE_EVENT);
    },
    ['calendarID']
);
