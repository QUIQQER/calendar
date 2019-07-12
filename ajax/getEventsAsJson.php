<?php

/**
 * Returns calendars events as JSON string
 *
 * @param integer $calendarID - The ID of the calendar which should be returned in iCal format
 *
 * @return String - The calendars events as JSON string
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_calendar_ajax_getEventsAsJson',
    function ($calendarID) {
        return \QUI\Calendar\Handler::getCalendar($calendarID)->getAllEvents()->toSchedulerFormat();
    },
    ['calendarID'],
    false
);
