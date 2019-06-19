<?php

/**
 * Creates a new calendar from an iCal URL
 *
 * @param String $name   - The name of the calendar
 * @param int    $userid - The ID of the owner.
 */

use QUI\Calendar\Handler;

QUI::$Ajax->registerFunction(
    'package_quiqqer_calendar_ajax_createCalendarFromIcal',
    function ($icalUrl, $userid) {
        try {
            $User = QUI::getUsers()->get($userid);
        } catch (Exception $ex) {
            return null;
        }
        Handler::createCalendarFromIcal($icalUrl, $User);

        QUI::getMessagesHandler()->addSuccess(
            QUI::getLocale()->get(
                'quiqqer/calendar',
                'message.calendar.successful.imported'
            )
        );
    },
    ['icalUrl', 'userid'],
    'Permission::checkUser'
);
