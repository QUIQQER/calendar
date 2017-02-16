<?php

/**
 * Can the current User edit the calendar with the given ID?
 *
 * @param int $calendarID - The ID of the calendar to check if editable.
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_calendar_ajax_canUserEditCalendar',
    function ($calendarID) {
        $Calendar = new \QUI\Calendar\Calendar($calendarID);
        return $Calendar->checkPermission($Calendar::PERMISSION_EDIT_CALENDAR);
    },
    array('calendarID'),
    'quiqqer.calendar.edit'
);
