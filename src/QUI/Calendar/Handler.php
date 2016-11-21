<?php

/**
 * This file contains QUI\Calendar\Handler
 */
namespace QUI\Calendar;

use QUI;
use QUI\Users\User;

/**
 * Class Handler
 * @package QUI\Calendar
 */
class Handler
{
    /**
     * Creates a new Calendar
     *
     * @param string $name - Calendar name
     * @param User $User - optional, User for which the calendar is
     */
    public static function createCalendar($name, $User = null)
    {
        $userID = null;
        if (!is_null($User) && get_class($User) == get_class($User)) {
            $userID = $User->getId();
        }

        QUI::getDataBase()->insert(QUI_DB_PRFX . 'calendars', array(
            'name' => $name,
            'userid' => $userID
        ));

        QUI::getMessagesHandler()->addSuccess(
            QUI::getLocale()->get(
                'quiqqer/calendar',
                'message.calendar.successful.created'
            )
        );
    }
}
