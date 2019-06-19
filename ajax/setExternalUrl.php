<?php

/**
 * Sets an externals calendars URL
 *
 * @param int    $calendarID  - The ID of the calendar to edit.
 * @param string $externalUrl - The new external iCal (.ics) file's URL
 */

use QUI\Calendar\Handler;

QUI::$Ajax->registerFunction(
    'package_quiqqer_calendar_ajax_setExternalUrl',
    function ($calendarID, $externalUrl) {
        $Calendar = Handler::getCalendar($calendarID);
        $Calendar->checkExternal();

        $Calendar->setExternalUrl($externalUrl);
    },
    ['calendarID', 'externalUrl'],
    'Permission::checkUser'
);
