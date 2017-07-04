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
        // TODO: get calendar ID from Brick setting
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

        $Engine = QUI::getTemplateManager()->getEngine();
        $Engine->assign('events', $events);

        $template = dirname(__FILE__) . '/EventList.html';
        $cssFile  = dirname(__FILE__) . '/EventList.css';

        $this->addCSSFile($cssFile);
        $html = $Engine->fetch($template);

        return $html;
    }
}
