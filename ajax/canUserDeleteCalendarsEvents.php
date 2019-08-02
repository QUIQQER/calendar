<?php

/**
 * Can the current User delete events from the calendar with the given ID?
 *
 * @param int $calendarID - The ID of the calendar to check if events can be deleted.
 */

use QUI\Calendar\AbstractCalendar;
use QUI\Calendar\Handler;

QUI::$Ajax->registerFunction(
    'package_quiqqer_calendar_ajax_canUserDeleteCalendarsEvents',
    function ($calendarID) {
        $Calendar = Handler::getCalendar($calendarID);

        return $Calendar->hasPermission(AbstractCalendar::PERMISSION_REMOVE_EVENT);
    },
    ['calendarID']
);
