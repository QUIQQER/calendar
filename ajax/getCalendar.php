<?php

/**
 * Returns calendar information as an array
 *
 * @param integer $calendarID - The ID of the calendar of which the information should be returned
 *
 * @return array
 */

use QUI\Calendar\Handler;

QUI::$Ajax->registerFunction(
    'package_quiqqer_calendar_ajax_getCalendar',
    function ($calendarID) {
        return Handler::getCalendar($calendarID)->toArray();
    },
    ['calendarID']
);
