<?php

/**
 * Edits an event
 *
 * @param int    $calendarID         - The calendar the event to edit is in
 * @param String $title              - The new title of the event
 * @param String $desc               - The new description of the event
 * @param int    $start              - The new start time of the event as UNIX timestamp
 * @param int    $end                - The new end time of the event as UNIX timestamp
 * @param string $recurrenceInterval - The interval of recurrence. Null if event shouldn't be recurring.
 * @param int    $recurrenceEnd      - Unix timestamp when the recurrence ends. Null if event isn't recurring or the recurrence has no end
 */

use QUI\Calendar\Event;
use QUI\Calendar\Event\EventManager;
use QUI\Calendar\Event\RecurringEvent;
use QUI\Calendar\Handler;

QUI::$Ajax->registerFunction(
    'package_quiqqer_calendar_ajax_editEvent',
    function ($eventID, $title, $desc, $start, $end, $eventurl, $recurrenceInterval, $recurrenceEnd) {
        $Event = EventManager::getEventById($eventID);

        if (!$Event) {
            return false;
        }

        $Calendar = Handler::getCalendar($Event->getCalendarId());
        $Calendar->checkInternal();

        $StartDate = new \DateTime();
        $EndDate   = clone $StartDate;

        $StartDate->setTimestamp($start);
        $EndDate->setTimestamp($end);

        // The event may have been from a different type before than it should be now
        // So we create a default recurring or normal event, if recurrence interval is set
        // The event's ID and calendar-id have to be set again, this is very important (happens on the end)!
        if ($recurrenceInterval) {
            $Event = new RecurringEvent($title, $StartDate, $EndDate, $recurrenceInterval);

            if ($recurrenceEnd) {
                $RecurrenceEnd = new \DateTime();
                $RecurrenceEnd->setTimestamp($recurrenceEnd);

                $Event->setRecurrenceEnd($RecurrenceEnd);
            }
        } else {
            $Event = new Event($title, $StartDate, $EndDate);
        }

        $Event->setId($eventID)
            ->setCalendarId($Calendar->getId())
            ->setDescription($desc)
            ->setUrl($eventurl);

        $Calendar->updateEvent($Event);
    },
    ['eventID', 'title', 'desc', 'start', 'end', 'eventurl', 'recurrenceInterval', 'recurrenceEnd']
);
