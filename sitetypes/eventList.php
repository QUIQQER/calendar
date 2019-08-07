<?php

use QUI\Calendar\Handler;

$currDay   = new \DateTime('now');
$startDate = new \DateTime(\date('d.m.Y', \strtotime("-2 days")));
$endDate   = new \DateTime(\date('d.m.Y', \strtotime("7 days")));
$Locale    = QUI::getLocale();

$events      = new \QUI\Calendar\Event\EventCollection();
$calendarIDs = $Site->getAttribute('calendar.settings.ids');

if (!\is_array($calendarIDs)) {
    $calendarIDs = \explode(',', $calendarIDs);
}

foreach ($calendarIDs as $calendarID) {
    try {
        $eventsCurrent = Handler::getCalendar($calendarID)->getEventsBetweenDates($startDate, $endDate, true, 100);
        $events->merge($eventsCurrent);
    } catch (QUI\Exception $Exception) {
        QUI\System\Log::addDebug($Exception->getMessage());
    }
}

$events->sortByStartDate();

$counter          = 0;
$incrementCounter = true;
$todayEvent       = false;

foreach ($events as $event) {
    $eventDate = $event->getStartDate();

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
        $Locale->get('quiqqer/calendar', 'quiqqer.frontend.calendar.emptyEvent.title'),
        $currDay,
        $currDay
    );

    $EmptyEvent->setDescription(
        $Locale->get('quiqqer/calendar', 'quiqqer.frontend.calendar.emptyEvent.desc')
    );

    $EmptyEvent->eventTimeStatus = 'now';
    $toInsert                    = [$EmptyEvent];

    $events->insert($EmptyEvent, $counter);
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
