<?php

namespace QUI\Calendar;

use QUI;
use QUI\Calendar\Exception\Database;
use QUI\Calendar\Exception\NoPermission;
use QUI\Permissions\Permission;
use QUI\System\Log;

class Manager
{
    /**
     * Returns the event for a given ID or null if not found
     *
     * @param int $id - An event id
     *
     * @return null|Event The event or null if event not found or no permission to view the events calendar
     *
     * @throws Database - Couldn't fetch event's data from the database
     */
    public static function getEventById($id)
    {
        try {
            $data = QUI::getDataBase()->fetch(
                [
                    'from'  => Handler::tableCalendarsEvents(),
                    'where' => [
                        'eventid' => $id
                    ],
                    'limit' => 1
                ]
            );
        } catch (\QUI\Database\Exception $Exception) {
            Log::writeException($Exception);
            throw new Database();
        }

        if (empty($data)) {
            return null;
        }

        $Event = Event\Utils::createEventFromDatabaseArray($data[0]);

        try {
            $Calendar = Handler::getCalendar($Event->calendar_id);
            $Calendar->checkPermission($Calendar::PERMISSION_VIEW_CALENDAR);
        } catch (Exception $ex) {
            return null;
        }

        return $Event;
    }


    /**
     * Returns the specified amount of events for an array of calendar ids
     *
     * @param int[]    $ids   - Array of calendar IDs of which upcoming events should be retrieved
     * @param int|bool $limit - Maximum amount of events to get
     *
     * @return Event[] - An array of upcoming events
     */
    public static function getUpcomingEventsForCalendarIds(array $ids, $limit = false)
    {
        /**
         * @var Event[] $events
         */
        $events = [];
        foreach ($ids as $calendarID) {
            try {
                $calendarsEvents = Handler::getCalendar($calendarID)->getUpcomingEvents($limit)->toArray();
                $events          = array_merge($events, $calendarsEvents);
            } catch (\QUI\Exception $exception) {
                continue;
            }
        }

        usort(
            $events,
            function ($EventA, $EventB) {
                /**
                 * @var Event $EventA
                 */
                /**
                 * @var Event $EventB
                 */
                return strcmp($EventA->start_date, $EventB->start_date);
            }
        );

        if (is_int($limit) && $limit > 0) {
            $events = array_slice($events, 0, $limit);
        }

        return $events;
    }


    /**
     * Returns the specified amount of events for an array of calendar ids
     *
     * @param AbstractCalendar[] $calendars - Array of calendars of which upcoming events should be retrieved
     * @param int|bool           $limit     - Maximum amount of events to get
     *
     * @return Event[] - An array of upcoming events
     */
    public static function getUpcomingEventsForCalendars(array $calendars, $limit = false)
    {
        $ids = [];
        foreach ($calendars as $Calendar) {
            $ids[] = $Calendar->getId();
        }

        return self::getUpcomingEventsForCalendarIds($ids, $limit);
    }


    /**
     * Returns all events from the database
     *
     * @return array
     *
     * @throws Database - Could not fetch event's data from the database
     * @throws NoPermission
     */
    public static function getAllEvents()
    {
        try {
            Permission::checkPermission(AbstractCalendar::PERMISSION_IS_ADMIN);
        } catch (\QUI\Permissions\Exception $Exception) {
            throw new NoPermission();
        }

        try {
            $eventsDataRaw = QUI::getDataBase()->fetch(
                [
                    'from' => Handler::tableCalendarsEvents()
                ]
            );
        } catch (\QUI\Database\Exception $Exception) {
            Log::writeException($Exception);
            throw new Database();
        }

        $events = [];
        foreach ($eventsDataRaw as $key => $eventData) {
            $events[] = Event\Utils::createEventFromDatabaseArray($eventData);
        }

        // Return array with new indexes starting at 0
        return array_values($events);
    }
}
