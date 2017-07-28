<?php

/**
 * This file contains QUI\Calendar\Bricks\EventList
 */

namespace QUI\Calendar\Bricks;

use QUI;

/**
 * Class CalendarDisplay
 *
 * @package QUI\Calendar
 * @author www.pcsg.de (Jan Wennrich)
 */
class EventList extends QUI\Control
{
    /**
     * @param array $attributes
     */
    public function __construct($attributes = array())
    {
        parent::__construct($attributes);

        $this->setAttribute('class', 'quiqqer-calendar-brick-event-list');
    }

    // TODO: Refactor so that Events are retrieved directly from DB -> no sorting and trimming needed
    public function getBody()
    {
        $calendarIDs = $this->getAttribute('calendarIDs');
        $amount      = $this->getAttribute('amount');

        // Collect all events
        $events = array();
        foreach (explode(',', $calendarIDs) as $calendarID) {
            if (QUI\Calendar\Handler::isExternalCalendar($calendarID)) {
                $Calendar = new QUI\Calendar\ExternalCalendar($calendarID);
            } else {
                $Calendar = new QUI\Calendar\InternalCalendar($calendarID);
            }

            $calendarEvents = $Calendar->getUpcomingEvents($amount);
            foreach ($calendarEvents as $Event) {
                $Date                  = new \DateTime($Event->start_date);
                $Event->formattedDate  = $Date->format('d');
                $Event->formattedMonth = $Date->format('M');
                $Event->formattedTime  = $Date->format('H:i');
            }

            $events = array_merge($events, $calendarEvents);
        }

        // Sort all events by start date
        uasort($events, function ($event1, $event2) {
            if ($event1->start_date == $event2->start_date) {
                return 0;
            }

            return ($event1->start_date < $event2->start_date) ? -1 : 1;
        });

        // Only return requested amount of events
        if (isset($amount) && $amount != false && $amount > -1) {
            $events = array_slice($events, 0, $amount);
        }

        $Engine = QUI::getTemplateManager()->getEngine();
        $Engine->assign('events', $events);

        $template = dirname(__FILE__) . '/EventList.html';
        $cssFile  = dirname(__FILE__) . '/EventList.css';

        $this->addCSSFile($cssFile);
        $html = $Engine->fetch($template);

        return $html;
    }
}
