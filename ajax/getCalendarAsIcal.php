<?php

/**
 * Returns calendar information in iCal format
 *
 * @param integer $calendarID - The ID of the calendar which should be returned in iCal format
 *
 * @return String - The calendar as an iCal string
 */

use QUI\Calendar\Handler;

QUI::$Ajax->registerFunction(
    'package_quiqqer_calendar_ajax_getCalendarAsIcal',
    function ($calendarID) {
        return Handler::getCalendar($calendarID)->toICal();
    },
    ['calendarID'],
    'Permission::checkAdminUser'
);
