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
        if (\QUI\Calendar\Handler::isExternalCalendar($calendarID)) {
            $Calendar = new \QUI\Calendar\ExternalCalendar($calendarID);
        } else {
            $Calendar = new \QUI\Calendar\InternalCalendar($calendarID);
        }

        return $Calendar->toICal();
    },
    array('calendarID'),
    'Permission::checkAdminUser'
);
