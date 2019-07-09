<?php

use QUI\Calendar\Handler;

$currDay   = new \DateTime('now');
$startDate = new \DateTime(\date('d.m.Y', \strtotime("-2 days")));
$endDate   = new \DateTime(\date('d.m.Y', \strtotime("7 days")));

$events      = [];
$calendarIDs = $Site->getAttribute('calendar.settings.ids');

if (!\is_array($calendarIDs)) {
    $calendarIDs = \explode(',', $calendarIDs);
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

$counter          = 0;
$incrementCounter = true;
$todayEvent       = false;

foreach ($events as $event) {
    $eventDate = new \DateTime($event->start_date);

    // bad code...
    if ($eventDate->format('Y-m-d') < $currDay->format('Y-m-d')) {
        $event->eventTimeStatus = 'past';
    } else {
        if ($eventDate->format('Y-m-d') == $currDay->format('Y-m-d')) {
            $event->eventTimeStatus = 'now';
            $todayEvent             = true;
            $incrementCounter       = false;
        } else {
            if (!$todayEvent) {
                $incrementCounter = false;
            }

            $event->eventTimeStatus = 'future';
        }
    }

    if ($incrementCounter) {
        $counter++;
    }
}

// no event today?
if ($counter) {
    $EmptyEvent = new QUI\Calendar\Event(
        'Kein besonderer Tag heute',
        'Heute gibt es nichts, was besonders ist.',
        $currDay->format('Y-m-d'),
        $currDay->format('Y-m-d')
    );

    $EmptyEvent->eventTimeStatus = 'now';
    $toInsert                    = [$EmptyEvent];

    array_splice($events, $counter, 0, $toInsert);
}


$Engine->assign([
    'events'  => $events,
    'currDay' => $currDay
]);

switch ($Site->getAttribute('calendar.settings.template')) {
    case 'timeline':
        $template = \dirname(__FILE__) . '/eventList.Timeline.html';
        break;
    case 'list':
    default:
        $template = \dirname(__FILE__) . '/eventList.List.html';
        break;
}

$html = $Engine->fetch($template);

$Engine->assign('html', $html);
