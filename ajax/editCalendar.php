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
        try {
            $calendar = new \QUI\Calendar\Calendar($calendarID);
        } catch (\QUI\Calendar\Exception $ex) {
            return $ex;
        }

        $calendar->editCalendar($name, $isPublic);

        ob_start();
        var_dump($isPublic);
        return ob_get_clean();  // string(0) ""

        return null;
    },
    array('calendarID', 'name', 'isPublic'),
    'quiqqer.calendar.edit'
);
