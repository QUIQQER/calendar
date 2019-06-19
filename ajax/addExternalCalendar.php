<?php

/**
 * Creates a new calendar from an iCal URL
 *
 * @param string $calendarName - Name of the calendar
 * @param string $icalUrl      - URL of the iCal (.ics) file
 * @param User   $User         - Owner of the calendar
 * @param bool   $isPublic     - Is the calendar private or public?
 * @param string $color        - The calendars color in hex format (leading #)
 */

use QUI\Calendar\Handler;
use QUI\Interfaces\Users\User;

QUI::$Ajax->registerFunction(
    'package_quiqqer_calendar_ajax_addExternalCalendar',
    function ($calendarName, $icalUrl, $userid, $isPublic = false, $color = '#2F8FC6') {
        try {
            $User = QUI::getUsers()->get($userid);
        } catch (Exception $ex) {
            return null;
        }

        Handler::addExternalCalendar($calendarName, $icalUrl, $User, $isPublic, $color);

        QUI::getMessagesHandler()->addSuccess(
            QUI::getLocale()->get(
                'quiqqer/calendar',
                'message.calendar.external.successful.added'
            )
        );
    },
    ['calendarName', 'icalUrl', 'userid', 'isPublic', 'color'],
    'Permission::checkUser'
);
