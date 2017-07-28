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
        $calendarIDs = explode(',', $this->getAttribute('calendarIDs'));
        $amount      = $this->getAttribute('amount');

        $events = QUI\Calendar\EventManager::getUpcomingEventsForCalendarIds($calendarIDs, $amount);

        if (is_null($events)) {
            $events = array();
        }

        // Simple or modern display style?
        if ($this->getAttribute('displayStyle') == "simple") {
            $template = dirname(__FILE__) . '/EventListSimple.html';
            $cssFile  = dirname(__FILE__) . '/EventListSimple.css';
        } else {
            // Format dates for modern display
            foreach ($events as $Event) {
                $Date                  = new \DateTime($Event->start_date);
                $Event->formattedDate  = $Date->format('d');
                $Event->formattedMonth = $Date->format('M');
                $Event->formattedTime  = $Date->format('H:i');
            }

            $template = dirname(__FILE__) . '/EventList.html';
            $cssFile  = dirname(__FILE__) . '/EventList.css';
        }

        $Engine = QUI::getTemplateManager()->getEngine();
        $Engine->assign('events', $events);

        $this->addCSSFile($cssFile);
        $html = $Engine->fetch($template);

        return $html;
    }
}
