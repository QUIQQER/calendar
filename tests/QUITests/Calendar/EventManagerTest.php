<?php

namespace QUITests\Calendar;

use QUI;

/**
 * Class EventManagerTest
 */
class EventManagerTest extends \PHPUnit_Framework_TestCase
{
    public function testGetUpcomingEventsForCalendarId()
    {
        $events = QUI\Calendar\Manager::getUpcomingEventsForCalendarId(29, 4);

        writePhpUnitMessage($events);
    }


    public function testGetUpcomingEventsForCalendarIds()
    {
        $events = QUI\Calendar\Manager::getUpcomingEventsForCalendarIds([29,30], 3);

        writePhpUnitMessage($events);
    }
}
