<?php

use QUI\Calendar\Handler;

$CurrentDate = new \DateTime('now');
$startDate   = new \DateTime(\date('d.m.Y', \strtotime("-2 days")));
$endDate     = new \DateTime(\date('d.m.Y', \strtotime("7 days")));
$Locale      = QUI::getLocale();

$EventCollection = new \QUI\Calendar\Event\EventCollection();
$calendarIDs     = $Site->getAttribute('calendar.settings.ids');

if (!\is_array($calendarIDs)) {
    $calendarIDs = \explode(',', $calendarIDs);
}

foreach ($calendarIDs as $calendarID) {
    try {
        $eventsCurrent = Handler::getCalendar($calendarID)->getEventsBetweenDates($startDate, $endDate, true, 100);
        $EventCollection->merge($eventsCurrent);
    } catch (QUI\Exception $Exception) {
        QUI\System\Log::writeException($Exception);
    }
}

$EventCollection->sortByStartDate();

$todaysEventPosition     = 0;
$isEventForTodayExisting = false;

foreach ($EventCollection as $index => $Event) {
    /**
     * @var \DateTime $EventStartDate
     */
    $EventStartDate = $Event->getStartDate();

    if ($EventStartDate < $CurrentDate) {
        $Event->eventTimeStatus = 'past';
        ++$todaysEventPosition;
        continue;
    }

    if ($EventStartDate->format('Y-m-d') == $CurrentDate->format('Y-m-d')) {
        $Event->eventTimeStatus  = 'now';
        $isEventForTodayExisting = true;
        continue;
    }

    $Event->eventTimeStatus = 'future';
}

// no event today?
if (!$isEventForTodayExisting) {
    $EmptyEvent = new QUI\Calendar\Event(
        $Locale->get('quiqqer/calendar', 'quiqqer.frontend.calendar.emptyEvent.title'),
        $CurrentDate,
        $CurrentDate
    );

    $EmptyEvent->setDescription(
        $Locale->get('quiqqer/calendar', 'quiqqer.frontend.calendar.emptyEvent.desc')
    );

    $EmptyEvent->eventTimeStatus = 'now';

    $EventCollection->insert($EmptyEvent, $todaysEventPosition);
}


$Engine->assign([
    'events'  => $EventCollection,
    'currDay' => $CurrentDate
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
