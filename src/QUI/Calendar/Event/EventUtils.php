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
class EventUtils
{
    /**
     * Creates single events from the given EventCollection's recurring events and adds them to the given collection.
     *
     * @param EventCollection $EventCollection
     * @param int             $maxInflationPerEvent - How many events at max should be spawned from a recurring event? (Default: 100)
     * @param \DateTime       $UntilDate            - When should the recurrence stop? (Default: date from timestamp with value PHP_INT_MAX)
     *
     * @return void
     *
     * @throws \Exception
     */
    public static function inflateRecurringEvents(
        EventCollection &$EventCollection,
        $maxInflationPerEvent = 1000,
        \DateTime $UntilDate = null
    ): void {
        $inflatedEvents = [];

        foreach ($EventCollection as $Event) {
            // If there is no recurrence for the current event, don't do anything
            if (!($Event instanceof RecurringEvent)) {
                continue;
            }

            // Determine the end of the recurrence, if none is set, use the maximum integer
            if (!$UntilDate) {
                $UntilDate->setTimestamp(PHP_INT_MAX);
            } else {
                // UntilDate given and recurrence ends before the UntilDate
                if ($Event->getRecurrenceEnd() && $Event->getRecurrenceEnd() < $UntilDate) {
                    $UntilDate = $Event->getRecurrenceEnd();
                }
            }

            $recurrenceInterval = $Event->getRecurrenceInterval();

            // Increase the start beforehand to maybe ignore the while-loop later.
            // We have to use clone here since DateTime objects behave really weird in PHP.
            // If we don't clone, the modify() modifies $Event which should not happen.
            $CurrentEventDateStart = clone $Event->getStartDate();
            $CurrentEventDateStart->modify("+ 1 {$recurrenceInterval}");

            // Increase the start beforehand to maybe ignore the while-loop later.
            // We have to use clone here since DateTime objects behave really weird in PHP.
            // If we don't clone, the modify() modifies $Event which should not happen.
            $CurrentEventDateEnd = clone $Event->getEndDate();
            $CurrentEventDateEnd->modify("+ 1 {$recurrenceInterval}");

            // Generate the events
            while (($CurrentEventDateStart < $UntilDate) && (count($inflatedEvents) <= $maxInflationPerEvent)) {
                // For some odd reason we have to use 'clone' here.
                // Using modify on a Date-object at the end of this loop would edit all currently instantiated dates.
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

                // Add the recurrence interval to generate the next event
                $CurrentEventDateStart->modify("+ 1 {$recurrenceInterval}");
                $CurrentEventDateEnd->modify("+ 1 {$recurrenceInterval}");
            }
        }

        $result = \array_merge($EventCollection->toArray(), $inflatedEvents);

        // The events might be out of order so we have to sort them again
        \usort($result, [static::class, 'sortEventArrayByStartDateComparisonFunction']);

        // The value is passed by reference (&) so we have to overwrite it
        $EventCollection = new EventCollection($result);
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
        // Column names in the database
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
     * Converts a given DateTime object to the format used by the DHTMLX Scheduler
     *
     * @param \DateTime $DateTime
     *
     * @return string  - The converted timestamp or false on error
     */
    public static function datetimeToSchedulerFormat(\DateTime $DateTime): string
    {
        return $DateTime->format("Y-m-d H:i");
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
    public static function sortEventArrayByStartDateComparisonFunction(Event $EventA, Event $EventB)
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


    /**
     * Creates a quiqqer/calendar event from a johngrogg/ics-parser event
     *
     * @param \ICal\Event $IcalEvent
     *
     * @return Event
     * @throws \Exception ICal Event has invalid dates.
     *
     */
    public static function createEventFromIcsParserEventData(\ICal\Event $IcalEvent): Event
    {
        $Event = new Event(
            $IcalEvent->summary,
            new \DateTime($IcalEvent->dtstart),
            new \DateTime($IcalEvent->dtend)
        );

        if ($IcalEvent->uid) {
            $Event->setId($IcalEvent->uid);
        }

        if ($IcalEvent->description) {
            $Event->setDescription($IcalEvent->description);
        }

        return $Event;
    }
}
