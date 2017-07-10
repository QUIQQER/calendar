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


    public function getBody()
    {
        $calendarIDs = $this->getAttribute('calendarIDs');
        $amount      = $this->getAttribute('amount');

        $events = array();
        foreach (explode(',', $calendarIDs) as $calendarID) {
            if (QUI\Calendar\Handler::isExternalCalendar($calendarID)) {
                $Calendar = new QUI\Calendar\ExternalCalendar($calendarID);
            } else {
                $Calendar = new QUI\Calendar\InternalCalendar($calendarID);
            }

            $events = array_merge($events, $Calendar->getUpcomingEvents($amount));
        }

        // Sort all events by start date
        uasort($events, function ($event1, $event2) {
            if ($event1->start_date == $event2->start_date) {
                return 0;
            }

            return ($event1->start_date < $event2->start_date) ? -1 : 1;
        });

        $Engine = QUI::getTemplateManager()->getEngine();
        $Engine->assign('events', $events);

        $template = dirname(__FILE__) . '/EventList.html';
        $cssFile  = dirname(__FILE__) . '/EventList.css';

        $this->addCSSFile($cssFile);
        $html = $Engine->fetch($template);

        return $html;
    }
}
