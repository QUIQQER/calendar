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
        $Calendar = new \QUI\Calendar\Calendar($calendarID);
        return $Calendar->toArray();
    },
    array('calendarID'),
    'Permission::checkAdminUser'
);
