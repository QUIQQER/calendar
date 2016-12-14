<?php

/**
 * Returns calendar information in iCal format
 *
 * @param integer $calendarID - The ID of the calendar which should be returned in iCal format
 *
 * @return String - The calendar as an iCal string
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_calendar_ajax_getCalendarAsIcal',
    function ($calendarID) {
        $Calendar = new \QUI\Calendar\Calendar($calendarID);
        return $Calendar->toICal();
    },
    array('calendarID'),
    'Permission::checkAdminUser'
);
