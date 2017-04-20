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
     * Event constructor.
     * @param string $title
     * @param string $desc
     * @param int $start
     * @param int $end
     */
    public function __construct($title, $desc, $start, $end)
    {
        $this->title = $title;
        $this->desc  = $desc;
        $this->start = $start;
        $this->end   = $end;
    }
}
