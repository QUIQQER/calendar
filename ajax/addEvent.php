<?php

/**
 * Add an event to a calendar
 *
 * @param int    $calendarID         - The calendar the event is in
 * @param String $title              - The title of the event
 * @param String $desc               - The description of the event
 * @param int    $start              - The start time of the event as UNIX timestamp
 * @param int    $end                - The end time of the event as UNIX timestamp
 * @param string $recurrenceInterval - The interval of recurrence. Null, if event shouldn't be recurring.
 * @param int    $recurrenceEnd      - Unix timestamp when the recurrence ends. Null, if event isn't recurring or the recurrence has no end
 *
 * @return int $eventID   - The ID the event got assigned
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_calendar_ajax_addEvent',
    function ($calendarID, $title, $desc, $start, $end, $eventurl, $recurrenceInterval, $recurrenceEnd) {
        if (is_null($calendarID) || empty($calendarID)) {
            return null;
        }

        $Calendar = \QUI\Calendar\Handler::getCalendar($calendarID);

        $Calendar->checkInternal();

        $StartDate = new \DateTime();
        $EndDate   = clone $StartDate;

        $StartDate->setTimestamp($start);
        $EndDate->setTimestamp($end);

        if ($recurrenceInterval) {
            $Event = new \QUI\Calendar\Event\RecurringEvent($title, $StartDate, $EndDate, $recurrenceInterval);

            if ($recurrenceEnd) {
                $RecurrenceEndDate = new \DateTime();
                $RecurrenceEndDate->setTimestamp($recurrenceEnd);

                $Event->setRecurrenceEnd($RecurrenceEndDate);
            }
        } else {
            $Event = new \QUI\Calendar\Event($title, $StartDate, $EndDate);
        }

        $Event->setDescription($desc)
            ->setUrl($eventurl)
            ->setCalendarId($calendarID);

        $Calendar->addEvent($Event);

        return (int)$Event->getId();
    },
    ['calendarID', 'title', 'desc', 'start', 'end', 'eventurl', 'recurrenceInterval', 'recurrenceEnd']
);
