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
     * @param bool $isPublic - Is the calendar private or public?
     * @param string $color - The calendars color in hex format (leading #)
     *
     * @return InternalCalendar - The created calendar
     *
     * @throws Exception
     * @throws QUI\Calendar\Exception\Database - Could not insert calendar into the database
     */
    public static function createCalendar($name, $User, $isPublic = false, $color = '#2F8FC6')
    {
        try {
            QUI::getDataBase()->insert(self::tableCalendars(), [
                'name'     => $name,
                'userid'   => $User->getId(),
                'isPublic' => $isPublic ? 1 : 0,
                'color'    => $color
            ]);
            $calendarID = QUI::getPDO()->lastInsertId();
        } catch (QUI\Database\Exception $Exception) {
            QUI\System\Log::writeException($Exception);
            throw new QUI\Calendar\Exception\Database();
        }

        return new InternalCalendar($calendarID);
    }

    /**
     * Creates a new calendar (with events) from an iCal URL
     *
     * @param string $icalUrl - The iCal URL
     * @param User $User - The calendar owner
     *
     * @return InternalCalendar - The created calendar object
     *
     * @throws Exception
     */
    public static function createCalendarFromIcal($icalUrl, $User)
    {
        // Translation of the word "Calendar"
        $calendarTranslation = QUI::getLocale()->get('quiqqer/calendar', 'calendar');

        $IcalCalendar = new ICal($icalUrl);
        $Calendar     = self::createCalendar($User->getName()." ".$calendarTranslation, $User);

        $eventsFromIcal = $IcalCalendar->events();

        $events = [];
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
     * @param string $calendarName - Name of the calendar
     * @param string $icalUrl - URL of the iCal (.ics) file
     * @param User $User - Owner of the calendar
     * @param bool $isPublic - Is the calendar private or public?
     * @param string $color - The calendars color in hex format (leading #)
     *
     * @return ExternalCalendar
     *
     * @throws Exception
     * @throws QUI\Calendar\Exception\Database - Couldn't insert calendar into the database
     */
    public static function addExternalCalendar(
        $calendarName,
        $icalUrl,
        $User = null,
        $isPublic = false,
        $color = '#2F8FC6'
    ) {
        if (!QUI\Utils\Request\Url::isReachable($icalUrl)) {
            throw new Exception(['quiqqer/calendar', 'message.calendar.external.error.url.invalid']);
        }

        try {
            $icalString = QUI\Utils\Request\Url::get($icalUrl);
        } catch (QUI\Exception $Exception) {
            throw new Exception(['quiqqer/calendar', 'message.calendar.external.error.url.invalid']);
        }

        if (!ExternalCalendar::isValidIcal($icalString)) {
            throw new Exception(['quiqqer/calendar', 'exception.ical.invalid']);
        }

        if (is_null($User)) {
            $User = QUI::getUserBySession();
        }

        try {
            QUI::getDataBase()->insert(self::tableCalendars(), [
                'name'        => $calendarName,
                'userid'      => $User->getId(),
                'isPublic'    => $isPublic,
                'isExternal'  => 1,
                'externalUrl' => $icalUrl,
                'color'       => $color
            ]);
        } catch (QUI\Database\Exception $Exception) {
            QUI\System\Log::writeException($Exception);
            throw new QUI\Calendar\Exception\Database();
        }

        $calendarID = QUI::getPDO()->lastInsertId();

        return new ExternalCalendar($calendarID);
    }


    /**
     * Returns if the given calendar ID belongs to an external calendar
     *
     * @param $calendarID
     *
     * @return bool
     *
     * @throws Exception
     */
    public static function isExternalCalendar($calendarID)
    {
        return !self::getCalendar($calendarID)->isInternal();
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
     * The name of the database table containing calendar url hashes
     *
     * @return string
     */
    public static function tableCalendarsShares()
    {
        return QUI::getDBTableName('calendars_shares');
    }

    /**
     * Deletes calendars with the given IDs from the database
     *
     * @param array $ids - The IDs of calendar which should be deleted
     *
     * @throws QUI\Calendar\Exception\NoPermission - User has no permission to delete one of the calendars
     * @throws Exception - One of the calendars could not be found
     * @throws QUI\Calendar\Exception\Database - Couldn't delete calendar from the database
     */
    public static function deleteCalendars($ids)
    {
        $Database = QUI::getDataBase();

        foreach ($ids as $id) {
            $id = (int)$id;

            $Calendar = Handler::getCalendar($id);

            $Calendar->checkPermission($Calendar::PERMISSION_DELETE_CALENDAR);

            try {
                $Database->delete(self::tableCalendars(), [
                    'id' => $id
                ]);

                $Database->delete(self::tableCalendarsEvents(), [
                    'calendarid' => $id
                ]);
            } catch (QUI\Database\Exception $Exception) {
                QUI\System\Log::writeException($Exception);
                throw new QUI\Calendar\Exception\Database();
            }
        }
    }

    /**
     * Returns all calendars from database.
     *
     * @return array - all calendars in the database
     *
     * @throws QUI\Calendar\Exception\Database - Couldn't fetch calendars' data from the database
     */
    public static function getCalendars()
    {
        try {
            $calendars = QUI::getDataBase()->fetch([
                'from' => self::tableCalendars()
            ]);
        } catch (QUI\Database\Exception $Exception) {
            QUI\System\Log::writeException($Exception);
            throw new QUI\Calendar\Exception\Database();
        }

        foreach ($calendars as $key => $calendarData) {
            $calendars[$key]['isPublic']   = $calendarData['isPublic'] == 1 ? true : false;
            $calendars[$key]['isExternal'] = $calendarData['isExternal'] == 1 ? true : false;
        }

        // Return array with new indexes starting at 0
        return array_values($calendars);
    }

    /**
     * Returns all external calendars from database.
     *
     * @return ExternalCalendar[] - All external calendars in the database
     *
     * @throws QUI\Calendar\Exception\Database - Couldn't fetch external calendars' data from the database
     */
    public static function getExternalCalendars()
    {
        try {
            $calendarsRaw = QUI::getDataBase()->fetch([
                'from'  => self::tableCalendars(),
                'where' => [
                    'isExternal' => 1
                ]
            ]);
        } catch (QUI\Database\Exception $Exception) {
            QUI\System\Log::writeException($Exception);
            throw new QUI\Calendar\Exception\Database();
        }

        $calendars = [];
        foreach ($calendarsRaw as $calendarData) {
            // Only return calendars the user can edit
            try {
                $Calendar = new ExternalCalendar($calendarData['id']);
                $Calendar->checkPermission($Calendar::PERMISSION_EDIT_CALENDAR);
            } catch (QUI\Calendar\Exception $ex) {
                continue;
            }

            $calendars[] = $Calendar;
        }

        // Return array with new indexes starting at 0
        return $calendars;
    }


    /**
     * Returns the calendar with the given ID
     *
     * @param int $calendarID
     *
     * @return ExternalCalendar|InternalCalendar - internal or external calendar
     *
     * @throws Exception - If calendar not found
     * @throws QUI\Calendar\Exception\Database - Couldn't fetch calendar's data from the database
     */
    public static function getCalendar($calendarID)
    {
        try {
            $calendarRaw = QUI::getDataBase()->fetch([
                'from'  => self::tableCalendars(),
                'where' => [
                    'id' => $calendarID
                ],
                'limit' => 1
            ]);
        } catch (QUI\Database\Exception $Exception) {
            QUI\System\Log::writeException($Exception);
            throw new QUI\Calendar\Exception\Database();
        }

        if (!isset($calendarRaw[0])) {
            throw new QUI\Calendar\Exception([
                'quiqqer/calendar',
                'exception.calendar.not_found'
            ]);
        }

        $calendarRaw = $calendarRaw[0];

        if ($calendarRaw['isExternal'] == 1) {
            $Calendar = new ExternalCalendar($calendarID);
        } else {
            $Calendar = new InternalCalendar($calendarID);
        }

        return $Calendar;
    }
}
