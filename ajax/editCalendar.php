<?php

/**
 * Edits a calendars values
 *
 * @param int $calendarID - The ID of the calendar to edit.
 * @param String $name - The new name of the calendar.
 * @param boolean $isPublic - Is the calendar public or private?
 * @param string $color - The calendars color in hex format (leading #)
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_calendar_ajax_editCalendar',
    function ($calendarID, $name, $isPublic, $color = '#2F8FC6') {
        \QUI\Calendar\Handler::getCalendar($calendarID)->editCalendar($name, $isPublic, $color);
    },
    array('calendarID', 'name', 'isPublic', 'color')
);
