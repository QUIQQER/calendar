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

    public static $calendarTable = QUI_DB_PRFX . 'calendars';
    public static $calendarEventsTable = QUI_DB_PRFX . 'calendars_events';

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

        QUI::getDataBase()->insert(Handler::$calendarTable, array(
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


    /**
     * Deletes calendars with the given IDs from the database
     *
     * @param array $ids - The IDs of calendar which should be deleted
     */
    public static function deleteCalendars($ids)
    {
        $Database = QUI::getDataBase();
        foreach ($ids as $id) {
            $id = (int)$id;

            $Database->delete(Handler::$calendarTable, array(
                'id' => $id
            ));

            $Database->delete(Handler::$calendarEventsTable, array(
                'calendarid' => $id
            ));
        }
    }


    /**
     * Returns calendars from database.
     *
     * @return array - calendars in the database
     */
    public static function getCalendars()
    {
        return QUI::getDataBase()->fetch(array(
            'from'  => Handler::$calendarTable
        ));
    }
}
