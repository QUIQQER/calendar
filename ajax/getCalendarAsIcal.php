<?php

/**
 * Returns calendar information
 *
 * @param integer $calendarID - Calendar-ID
 *
 * @return array
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
