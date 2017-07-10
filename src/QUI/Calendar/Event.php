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
    public $id;

    /**
     * @var int
     */
    public $calendar_id;

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

    /**
     * Creates an event object from an array of database data. See param for required field names.
     *
     * @param array $data - Array of data with the following field names: title, desc, start, end, eventid, calendarid
     *
     * @return Event
     */
    public static function fromDatabaseArray($data)
    {
        return new self(
            $data['title'],
            $data['desc'],
            $data['start'],
            $data['end'],
            $data['eventid'],
            $data['calendarid']
        );
    }
}
