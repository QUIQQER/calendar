<?php

/**
 * Returns a share URL for the current user and a given calendar
 *
 * @return array
 */

use QUI\Calendar\Handler;

QUI::$Ajax->registerFunction(
    'package_quiqqer_calendar_ajax_getShareUrl',
    function ($calendarID) {
        return Handler::getCalendar($calendarID)->getShareUrl();
    },
    ['calendarID'],
    'Permission::checkUser'
);
