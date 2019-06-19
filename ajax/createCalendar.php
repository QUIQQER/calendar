<?php

/**
 * Creates a new calendar
 *
 * @param String  $name     - The name of the calendar
 * @param int     $userid   - The ID of the owner.
 * @param boolean $isPublic - Is the calendar public or private?
 * @param string  $color    - The calendars color in hex format (leading #)
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_calendar_ajax_createCalendar',
    function ($name, $userid, $isPublic, $color) {
        try {
            $User = QUI::getUsers()->get($userid);
        } catch (Exception $ex) {
            return null;
        }
        \QUI\Calendar\Handler::createCalendar($name, $User, $isPublic, $color);

        QUI::getMessagesHandler()->addSuccess(
            QUI::getLocale()->get(
                'quiqqer/calendar',
                'message.calendar.successful.created'
            )
        );
    },
    ['name', 'userid', 'isPublic', 'color'],
    'Permission::checkUser'
);
