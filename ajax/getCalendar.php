<?php

/**
 * Returns calendar information
 *
 * @param integer $calendarID - Calendar-ID
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
