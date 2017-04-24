<?php

/**
 * Creates a new calendar from an iCal URL
 *
 * @param String $icalUrl - URL to the corresponding iCal (.ics) file
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_calendar_ajax_addExternalCalendar',
    function ($icalUrl) {
        try {
            \QUI\Calendar\Handler::addExternalCalendar($icalUrl);

            QUI::getMessagesHandler()->addSuccess(
                QUI::getLocale()->get(
                    'quiqqer/calendar',
                    'message.calendar.external.successful.added'
                )
            );
        } catch (Exception $ex) {
            return null;
        }
    },
    array('icalUrl'),
    'quiqqer.calendar.create'
);
