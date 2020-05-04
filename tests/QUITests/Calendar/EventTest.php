<?php

namespace QUITests\Calendar;

use QUI;

/**
 * Class EventTest
 */
class EventTest extends \PHPUnit_Framework_TestCase
{
    public function testEventToArray()
    {
        $Event = new QUI\Calendar\Event('Title', '0', '1');

        $this->assertEquals(array(
            'text'        => 'Title',
            'description' => 'Desc',
            'start_date'  => '0',
            'end_date'    => '1',
            'id'          => -1,
            'calendar_id' => -1,
        ), $Event->toArray());
    }
}
