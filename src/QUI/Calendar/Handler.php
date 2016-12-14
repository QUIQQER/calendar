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
     * @param User $User   - optional, User for which the calendar is
     */
    public static function createCalendar($name, $User = null)
    {
        $userID = null;

        if (QUI::getUsers()->isUser($User)) {
            $userID = $User->getId();
        }

        QUI::getDataBase()->insert(self::tableCalendars(), array(
            'name'   => $name,
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
     * The name of the database table containing calendars
     *
     * @return string
     */
    public static function tableCalendars()
    {
        return QUI::getDBTableName('calendars');
    }

    /**
     * The name of the database table containing calendar events
     *
     * @return string
     */
    public static function tableCalendarsEvents()
    {
        return QUI::getDBTableName('calendars_events');
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

            $Database->delete(self::tableCalendars(), array(
                'id' => $id
            ));

            $Database->delete(self::tableCalendarsEvents(), array(
                'calendarid' => $id
            ));
        }
    }

    /**
     * Returns all calendars from database.
     *
     * @return array - all calendars in the database
     */
    public static function getCalendars()
    {
        return QUI::getDataBase()->fetch(array(
            'from' => self::tableCalendars()
        ));
    }
}
