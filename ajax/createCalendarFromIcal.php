<?php

/**
 * Creates a new calendar from an iCal data string
 *
 * @param String $name - The name of the calendar
 * @param int $userid - The ID of the owner.
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_calendar_ajax_createCalendarFromIcal',
    function ($ical, $userid) {
        try {
            $User = QUI::getUsers()->get($userid);
        } catch (Exception $ex) {
            return null;
        }
        \QUI\Calendar\Handler::createCalendarFromIcal($ical, $User);
    },
    array('ical', 'userid'),
    'quiqqer.calendar.create'
);
