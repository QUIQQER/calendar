<?php

/**
 * Edits an event
 *
 * @param int    $calendarID - The calendar the event to edit is in
 * @param String $title      - The new title of the event
 * @param String $desc       - The new description of the event
 * @param int    $start      - The new start time of the event as UNIX timestamp
 * @param int    $end        - The new end time of the event as UNIX timestamp
 */
QUI::$Ajax->registerFunction(
    'package_quiqqer_calendar_ajax_editEvent',
    function ($eventID, $title, $desc, $start, $end, $eventurl) {
        $Event = \QUI\Calendar\Event\EventManager::getEventById($eventID);

        $Calendar = \QUI\Calendar\Handler::getCalendar($Event->getCalendarId());
        $Calendar->checkInternal();

        $StartDate = new \DateTime();
        $EndDate   = clone $StartDate;

        $StartDate->setTimestamp($start);
        $EndDate->setTimestamp($end);

        $Event->setTitle($title)
            ->setDescription($desc)
            ->setUrl($eventurl)
            ->setStartDate($StartDate)
            ->setEndDate($EndDate);

        $Calendar->updateEvent($Event);
    },
    ['eventID', 'title', 'desc', 'start', 'end', 'eventurl']
);
