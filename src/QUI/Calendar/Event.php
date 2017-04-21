<?php

namespace QUI\Calendar;

class Event
{
    /**
     * @var string - The events title
     */
    public $title;

    /**
     * @var string - The events description
     */
    public $desc;

    /**
     * @var int - The events start time/date as a UNIX timestamp
     */
    public $start;

    /**
     * @var int - The events end time/date as a UNIX timestamp
     */
    public $end;

    /**
     * @var int
     */
    private $id;

    /**
     * @var int
     */
    private $calendarid;

    /**
     * Event constructor.
     * @param string $title
     * @param string $desc
     * @param int $start
     * @param int $end
     * @param int $id
     * @param int $calendarid
     */
    public function __construct($title, $desc, $start, $end, $id = -1, $calendarid = -1)
    {
        $this->title      = $title;
        $this->desc       = $desc;
        $this->start      = $start;
        $this->end        = $end;
        $this->id         = $id;
        $this->calendarid = $calendarid;
    }
}
