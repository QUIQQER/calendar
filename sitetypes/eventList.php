<?php

use QUI\Calendar\Handler;

$currDay   = new DateTime('now');
$startDate = new DateTime(date('d.m.Y', strtotime("-2 days")));
$endDate   = new DateTime(date('d.m.Y', strtotime("7 days")));

$events      = [];
$calendarIDs = $Site->getAttribute('calendar.settings.ids');

if (!is_array($calendarIDs)) {
    $calendarIDs = explode(',', $calendarIDs);
}

foreach ($calendarIDs as $calendarID) {
    try {
        $eventsCurrent = Handler::getCalendar($calendarID)->getEventsBetweenDates($startDate, $endDate, true);
        $events        = \array_merge($events, $eventsCurrent->toArray());
    } catch (QUI\Exception $Exception) {
        QUI\System\Log::addDebug($Exception->getMessage());
    }
}

\usort($events, function ($a, $b) {
    return \strtotime($a->start_date) - \strtotime($b->start_date);
});

foreach ($events as $event) {
    $eventDate = new DateTime($event->start_date);

    if ($eventDate->format('Y-m-d') < $currDay->format('Y-m-d')) {
        $event->eventTimeStatus = 'past';
    } else {
        if ($eventDate->format('Y-m-d') > $currDay->format('Y-m-d')) {
            $event->eventTimeStatus = 'future';
        } else {
            $event->eventTimeStatus = 'now';
        }
    }
}

$Engine->assign([
    'events'  => $events,
    'currDay' => $currDay
]);
