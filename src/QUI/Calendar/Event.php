<?php

namespace QUI\Calendar;

class Event
{
    /**
     * @var string - The events title
     */
    public $text;

    /**
     * @var string - The events description
     */
    public $description;

    /**
     * @var int - The events start time/date as a UNIX timestamp
     */
    public $start_date;

    /**
     * @var int - The events end time/date as a UNIX timestamp
     */
    public $end_date;

    /**
     * @var int
     */
    private $id;

    /**
     * @var int
     */
    private $calendar_id;

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
        $this->text        = $title;
        $this->description = $desc;
        $this->start_date  = $start;
        $this->end_date    = $end;
        $this->id          = $id;
        $this->calendar_id = $calendarid;
    }
}
