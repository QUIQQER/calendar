<?php

/**
 * Returns a share URL for the current user and a given calendar
 *
 * @return array
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_calendar_ajax_getShareUrl',
    function ($calendarID) {
        return \QUI\Calendar\Handler::getCalendar($calendarID)->getShareUrl();
    },
    ['calendarID'],
    'Permission::checkAdminUser'
);
