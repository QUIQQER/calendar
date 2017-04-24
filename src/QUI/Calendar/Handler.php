<?php

/**
 * This file contains QUI\Calendar\Handler
 */

namespace QUI\Calendar;

use ICal\ICal;
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
     * @return InternalCalendar - The created calendar
     */
    public static function createCalendar($name, $User, $isPublic = false)
    {
        QUI::getDataBase()->insert(self::tableCalendars(), array(
            'name'     => $name,
            'userid'   => $User->getId(),
            'isPublic' => $isPublic
        ));
        $calendarID = QUI::getPDO()->lastInsertId();

        return new InternalCalendar($calendarID);
    }

    /**
     * Creates a new calendar (with events) from an iCal URL
     *
     * @param string $icalUrl - The iCal URL
     * @param User $User - The calendar owner
     *
     * @return InternalCalendar - The created calendar object
     */
    public static function createCalendarFromIcal($icalUrl, $User)
    {
        // Translation of the word "Calendar"
        $calendarTranslation = QUI::getLocale()->get('quiqqer/calendar', 'calendar');

        $IcalCalendar = new ICal($icalUrl);
        $Calendar     = self::createCalendar($User->getName() . " " . $calendarTranslation, $User);

        $eventsFromIcal = $IcalCalendar->events();

        $events = array();
        foreach ($eventsFromIcal as $IcalEvent) {
            $events[] = new Event(
                $IcalEvent->summary,
                $IcalEvent->description,
                (int)$IcalCalendar->iCalDateToUnixTimestamp($IcalEvent->dtstart),
                (int)$IcalCalendar->iCalDateToUnixTimestamp($IcalEvent->dtend)
            );
        }

        $Calendar->addCalendarEvents($events);

        return $Calendar;
    }


    /**
     * Adds an external calendar to the system.
     *
     * @param string $icalUrl - URL of the iCal (.ics) file
     * @param QUI\Interfaces\Users\User $User - The owner of the calendar
     * @return ExternalCalendar
     */
    public static function addExternalCalendar($icalUrl, $User = null)
    {
        if (is_null($User)) {
            $User = QUI::getUserBySession();
        }

        // Translation of the word "Calendar"
        $calendarTranslation = QUI::getLocale()->get('quiqqer/calendar', 'calendar');

        QUI::getDataBase()->insert(self::tableCalendars(), array(
            'name'        => $User->getName() . " " . $calendarTranslation,
            'userid'      => $User->getId(),
            'isPublic'    => false,
            'isExternal'  => true,
            'externalUrl' => $icalUrl
        ));
        $calendarID = QUI::getPDO()->lastInsertId();

        return new ExternalCalendar($calendarID);
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

            $Calendar = new InternalCalendar($id);
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
            $Calendar = new InternalCalendar($calendarData['id']);

            // Only return calendars the user can edit
            try {
                $Calendar->checkPermission($Calendar::PERMISSION_EDIT_CALENDAR);
            } catch (QUI\Calendar\Exception $ex) {
                unset($calendars[$key]);
                continue;
            }

            $calendars[$key]['isPublic']   = $calendarData['isPublic']   == 1 ? true : false;
            $calendars[$key]['isExternal'] = $calendarData['isExternal'] == 1 ? true : false;
        }

        // Return array with new indexes starting at 0
        return array_values($calendars);
    }
}
