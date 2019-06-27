<?php

/**
 * This file contains QUI\Calendar\Bricks\CalendarDisplay
 */

namespace QUI\Calendar\Bricks;

use QUI;

/**
 * Class CalendarDisplay
 *
 * @package QUI\Calendar
 * @author  www.pcsg.de (Jan Wennrich)
 */
class CalendarDisplay extends QUI\Control
{
    public function getBody()
    {
        $height = '85vh';
        if ($this->getAttribute('height')) {
            $height = $this->getAttribute('height');
        }

        $this->setStyles([
            'height' => $height
        ]);

        $this->setJavaScriptControl('package/quiqqer/calendar/bin/controls/CalendarDisplay');
        $this->setJavaScriptControlOption('calendarids', $this->getAttribute('calendarIDs'));
        $this->setJavaScriptControlOption('view', $this->getAttribute('view'));

        $this->setAttribute('class', 'quiqqer-calendar-brick-calendar-display');

        return;
    }
}
