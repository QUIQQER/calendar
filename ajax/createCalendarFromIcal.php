<?php

/**
 * Creates a new calendar from an iCal URL
 *
 * @param String $name - The name of the calendar
 * @param int $userid - The ID of the owner.
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_calendar_ajax_createCalendarFromIcal',
    function ($icalUrl, $userid) {
        try {
            $User = QUI::getUsers()->get($userid);
        } catch (Exception $ex) {
            return null;
        }
        \QUI\Calendar\Handler::createCalendarFromIcal($icalUrl, $User);

        QUI::getMessagesHandler()->addSuccess(
            QUI::getLocale()->get(
                'quiqqer/calendar',
                'message.calendar.successful.imported'
            )
        );
    },
    array('icalUrl', 'userid')
);
