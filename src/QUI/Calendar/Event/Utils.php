<?php

/**
 * @author PCSG (Jan Wennrich)
 */

namespace QUI\Calendar\Event;

use QUI\Calendar\Event;
use QUI\Calendar\Exception;

/**
 * Class Utils
 *
 * @package QUI\Calendar\Event
 */
class Utils
{
    /**
     * Creates single events from the given EventCollection's recurring events and adds them back to the collection.
     *
     * @param Collection $EventCollection
     * @param int        $limit
     *
     * @return void
     *
     * @throws \Exception
     */
    public static function inflateRecurringEvents(Collection &$EventCollection, $limit = 1000): void
    {
        $inflatedEvents = [];

        foreach ($EventCollection as $Event) {
            // If there is no recurrence for the current event, don't do anything
            if (!($Event instanceof RecurringEvent)) {
                continue;
            }

            // Determine the end of the recurrence, if none is set, use the maximum integer
            $recurrenceEndTimestamp = PHP_INT_MAX;
            if ($Event->getRecurrenceEnd()) {
                $recurrenceEndTimestamp = $Event->getRecurrenceEnd()->getTimestamp();
            }

            // The start and end of the current event. Using a DateTime object here to add the recurrence interval later
            $CurrentEventDateStart = $Event->getStartDate();
            $CurrentEventDateEnd   = $Event->getEndDate();

            $recurrenceInterval = $Event->getRecurrenceInterval();

            // Generate the events
            $eventCounter = 0;
            while (($CurrentEventDateStart->getTimestamp() < $recurrenceEndTimestamp) && (++$eventCounter <= $limit)) {
                // Add the recurrence interval to generate the next event
                $CurrentEventDateStart->modify("+ 1 {$recurrenceInterval}");
                $CurrentEventDateEnd->modify("+ 1 {$recurrenceInterval}");

                // For some odd reason we have to use 'clone' here
                // Otherwise using modify above edits all Dates
                $CurrentEvent = new Event(
                    $Event->getTitle(),
                    clone $CurrentEventDateStart,
                    clone $CurrentEventDateEnd
                );

                if (!empty($Event->getUrl())) {
                    $CurrentEvent->setUrl($Event->getUrl());
                }

                if (!empty($Event->getDescription())) {
                    $CurrentEvent->setDescription($Event->getDescription());
                }

                if (!empty($Event->getCalendarId())) {
                    $CurrentEvent->setCalendarId($Event->getCalendarId());
                }

                if (!empty($Event->getId())) {
                    $CurrentEvent->setId($Event->getId());
                }

                $inflatedEvents[] = $CurrentEvent;
            }
        }

        $result = \array_merge($EventCollection->toArray(), $inflatedEvents);

        // The events might be out of order so we have to sort them again
        \usort($result, [static::class, 'sortEventArrayByStartDateComparisonFunction']);

        // Return only the first x elements depending on the given limit
        $result = \array_slice($result, 0, $limit);

        // The value is passed by reference (&) so we have to overwrite it
        $EventCollection = new Collection($result);
    }

    /**
     * Creates an event object from an array of database data. See param for required field names.
     *
     * @param array $data - Array of data with the following field names: title, desc, start, end, eventid, calendarid
     *
     * @return Event|RecurringEvent
     *
     * @throws \Exception - Invalid date values given.
     */
    public static function createEventFromDatabaseArray($data): Event
    {
        $requiredKeys = [
            'title',
            'desc',
            'start',
            'end'
        ];

        // check if all required keys exist
        if (count(array_diff($requiredKeys, array_keys($data))) !== 0) {
            $message = 'Not all required keys where set when trying to create an event from an database array.';
            throw new Exception\InvalidArgumentException($message);
        }

        $StartDate = new \DateTime();
        $StartDate->setTimestamp($data['start']);

        $EndDate = new \DateTime();
        $EndDate->setTimestamp($data['end']);

        if (isset($data['recurrence_interval']) && !empty($data['recurrence_interval'])) {
            $Event = new RecurringEvent(
                $data['title'],
                $StartDate,
                $EndDate,
                $data['recurrence_interval']
            );
        } else {
            $Event = new Event(
                $data['title'],
                $StartDate,
                $EndDate
            );
        }

        if (isset($data['eventid']) && !empty($data['eventid'])) {
            $Event->setId($data['eventid']);
        }

        if (isset($data['desc']) && !empty($data['desc'])) {
            $Event->setDescription($data['desc']);
        }

        if (isset($data['calendarid']) && !empty($data['calendarid'])) {
            $Event->setCalendarId($data['calendarid']);
        }

        if (isset($data['url']) && !empty($data['url'])) {
            $Event->setUrl($data['url']);
        }

        if (isset($data['recurrence_end']) && !empty($data['recurrence_end'])) {
            $RecurrenceEnd = new \DateTime($data['recurrence_end']);
            $Event->setRecurrenceEnd($RecurrenceEnd);
        }

        return $Event;
    }

    /**
     * Converts a UNIX timestamp to the format for DHTMLX Scheduler
     *
     * @param $timestamp int - A unix timestamp
     *
     * @return false|string  - The converted timestamp or false on error
     */
    public static function timestampToSchedulerFormat($timestamp)
    {
        return \date("Y-m-d H:i", $timestamp);
    }


    /**
     * Function to be used to sort events by start date.
     * Can be passed to usort() for example.
     *
     * @param Event $EventA
     * @param Event $EventB
     *
     * @return int
     */
    protected static function sortEventArrayByStartDateComparisonFunction(Event $EventA, Event $EventB)
    {
        /**
         * @var Event $EventA
         */
        $StartDateEventA = $EventA->getStartDate();

        /**
         * @var Event $EventB
         */
        $StartDateEventB = $EventB->getStartDate();

        if ($StartDateEventA == $StartDateEventB) {
            return 0;
        }

        return ($StartDateEventA < $StartDateEventB) ? -1 : 1;
    }
}
