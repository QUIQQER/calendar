<?php

/**
 * Returns calendar information as an array
 *
 * @param integer $calendarID - The ID of the calendar of which the information should be returned
 *
 * @return array
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_calendar_ajax_getCalendar',
    function ($calendarID) {
        if (\QUI\Calendar\Handler::isExternalCalendar($calendarID)) {
            $Calendar = new \QUI\Calendar\ExternalCalendar($calendarID);
        } else {
            $Calendar = new \QUI\Calendar\InternalCalendar($calendarID);
        }
        
        return $Calendar->toArray();
    },
    array('calendarID'),
    'Permission::checkAdminUser'
);
