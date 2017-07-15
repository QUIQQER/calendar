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
        if (\QUI\Calendar\Handler::isExternalCalendar($calendarID)) {
            $Calendar = new \QUI\Calendar\ExternalCalendar($calendarID);
        } else {
            $Calendar = new \QUI\Calendar\InternalCalendar($calendarID);
        }

        $Calendar->editCalendar($name, $isPublic, $color);
    },
    array('calendarID', 'name', 'isPublic', 'color'),
    'quiqqer.calendar.edit'
);
