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
     * @param User $User - Owner of the calendar
     * @param $isPublic - Is the calendar private or public?
     *
     * @return Calendar - The created calendar
     */
    public static function createCalendar($name, $User, $isPublic = false)
    {
        QUI::getDataBase()->insert(self::tableCalendars(), array(
            'name'     => $name,
            'userid'   => $User->getId(),
            'isPublic' => $isPublic
        ));
        $calendarID = QUI::getPDO()->lastInsertId();

        QUI::getMessagesHandler()->addSuccess(
            QUI::getLocale()->get(
                'quiqqer/calendar',
                'message.calendar.successful.created'
            )
        );

        return new Calendar($calendarID);
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

            $Calendar = new Calendar($id);
            $Calendar->checkPermission($Calendar::PERMISSION_DELETE_CALENDAR);

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
        $calendars = QUI::getDataBase()->fetch(array(
            'from' => self::tableCalendars()
        ));

        foreach ($calendars as $key => $calendarData) {
            $Calendar = new Calendar($calendarData['id']);

            // Only return calendars the user can edit
            try {
                $Calendar->checkPermission($Calendar::PERMISSION_EDIT_CALENDAR);
            } catch (QUI\Calendar\Exception $ex) {
                unset($calendars[$key]);
                continue;
            }

            $calendars[$key]['isPublic'] = $calendarData['isPublic'] == 1 ? true : false;
        }

        // Return array with new indexes starting at 0
        return array_values($calendars);
    }
}
