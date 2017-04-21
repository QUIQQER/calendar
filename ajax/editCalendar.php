<?php

/**
 * Edits a calendars values
 *
 * @param int $calendarID - The ID of the calendar to edit.
 * @param String $name - The new name of the calendar.
 * @param boolean $isPublic - Is the calendar public or private?
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_calendar_ajax_editCalendar',
    function ($calendarID, $name, $isPublic) {
        $calendar = new \QUI\Calendar\InternalCalendar($calendarID);
        $calendar->editCalendar($name, $isPublic);
    },
    array('calendarID', 'name', 'isPublic'),
    'quiqqer.calendar.edit'
);
