<?php

namespace QUI\Calendar;

use QUI\Calendar\Exception\Database;
use QUI\Calendar\Exception\NoPermission;
use QUI\System\Log;

class EventManager
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
            $data = \QUI::getDataBase()->fetch(array(
                'from'  => Handler::tableCalendarsEvents(),
                'where' => array(
                    'eventid' => $id
                ),
                'limit' => 1
            ));
        } catch (\QUI\Database\Exception $Exception) {
            Log::writeException($Exception);
            throw new Database();
        }

        if (empty($data)) {
            return null;
        }

        $Event = Event::fromDatabaseArray($data[0]);

        try {
            $Calendar = Handler::getCalendar($Event->calendar_id);
            $Calendar->checkPermission($Calendar::PERMISSION_VIEW_CALENDAR);
        } catch (Exception $ex) {
            return null;
        }

        return $Event;
    }


    /**
     * Returns the specified amount of events for a calendar id
     *
     * @param int $id - ID of a calendar of which upcoming events should be retrieved
     * @param bool $limit - Maximum amount of events to get
     *
     * @return Event[] - An array of upcoming events
     *
     * @throws NoPermission - Current user isn't allowed to view the calendar
     * @throws \QUI\Exception - Calendar with the given ID doesn't exist or is not accessible
     *
     * @deprecated - Use getUpcomingEvents() of a AbstractCalendar instance instead
     */
    public static function getUpcomingEventsForCalendarId($id, $limit = false)
    {
        return Handler::getCalendar($id)->getUpcomingEvents($limit);
    }


    /**
     * Returns the specified amount of events for a calendar
     *
     * @param AbstractCalendar $Calendar - Calendar of which upcoming events should be retrieved
     * @param bool $limit - Maximum amount of events to get
     *
     * @return Event[] - An array of upcoming events
     *
     * @deprecated - Use getUpcomingEvents() of a AbstractCalendar instance instead
     */
    public function getUpcomingEventsForCalendar(AbstractCalendar $Calendar, $limit = false)
    {
        return $Calendar->getUpcomingEvents($limit);
    }


    /**
     * Returns the specified amount of events for an array of calendar ids
     *
     * @param int[] $ids - Array of calendar IDs of which upcoming events should be retrieved
     * @param int|bool $limit - Maximum amount of events to get
     *
     * @return Event[] - An array of upcoming events
     */
    public static function getUpcomingEventsForCalendarIds(array $ids, $limit = false)
    {
        /** @var Event[] $events */
        $events = [];
        foreach ($ids as $calendarID) {
            try {
                $calendarsEvents = Handler::getCalendar($calendarID)->getUpcomingEvents($limit);
                $events          = array_merge($events, $calendarsEvents);
            } catch (\QUI\Exception $exception) {
                continue;
            }
        }

        usort($events, function ($EventA, $EventB) {
            /** @var Event $EventA */
            /** @var Event $EventB */
            return strcmp($EventA->start_date, $EventB->start_date);
        });

        if (is_int($limit) && $limit > 0) {
            $events = array_slice($events, 0, $limit);
        }

        return $events;
    }


    /**
     * Returns the specified amount of events for an array of calendar ids
     *
     * @param AbstractCalendar[] $calendars - Array of calendars of which upcoming events should be retrieved
     * @param int|bool $limit - Maximum amount of events to get
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
     */
    public static function getAllEvents()
    {
        try {
            $eventsDataRaw = \QUI::getDataBase()->fetch(array(
                'from' => Handler::tableCalendarsEvents()
            ));
        } catch (\QUI\Database\Exception $Exception) {
            Log::writeException($Exception);
            throw new Database();
        }

        $events = array();
        foreach ($eventsDataRaw as $key => $eventData) {
            $events[] = Event::fromDatabaseArray($eventData);
        }

        // Return array with new indexes starting at 0
        return array_values($events);
    }
}
