<?php

/**
 * Removes event with ID eventID from the calendar with ID calendarID
 *
 * @param string $id - the event id
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_calendar_ajax_removeEvent',
    function ($calendarID, $eventID) {
        $Calendar = new QUI\Calendar\Calendar($calendarID);
        $Calendar->removeCalendarEvent($eventID);
    },
    array('calendarID', 'eventID'),
    'Permission::checkAdminUser'
);
