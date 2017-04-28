<?php

/**
 * Sets an externals calendars URL
 *
 * @param int $calendarID - The ID of the calendar to edit.
 * @param string $externalUrl - The new external iCal (.ics) file's URL
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_calendar_ajax_setExternalUrl',
    function ($calendarID, $externalUrl) {
        $Calendar = new \QUI\Calendar\ExternalCalendar($calendarID);
        $Calendar->setExternalUrl($externalUrl);
    },
    array('calendarID', 'externalUrl'),
    'quiqqer.calendar.edit'
);
