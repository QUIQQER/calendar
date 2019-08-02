<?php

/**
 * Can the current User edit the calendar with the given ID?
 *
 * @param int $calendarID - The ID of the calendar to check if editable.
 */

use QUI\Calendar\AbstractCalendar;
use QUI\Calendar\Handler;

QUI::$Ajax->registerFunction(
    'package_quiqqer_calendar_ajax_canUserEditCalendar',
    function ($calendarID) {
        $Calendar = Handler::getCalendar($calendarID);

        return $Calendar->hasPermission(AbstractCalendar::PERMISSION_EDIT_CALENDAR);
    },
    ['calendarID']
);
