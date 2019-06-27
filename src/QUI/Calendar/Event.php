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
     * @var int - The events start time/date in format YYYY-MM-DD HH:mm
     * @todo Turn this into a DateTime-object
     */
    public $start_date;

    /**
     * @var int - The events end time/date in format YYYY-MM-DD HH:mm
     * @todo Turn this into a DateTime-object
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
     * @var string - Link to further information about the event
     */
    public $url;

    /**
     * Event constructor.
     *
     * @param string $title
     * @param string $desc
     * @param string $start - The events start time/date in format YYYY-MM-DD HH:mm
     * @param string $end   - The events end time/date in format YYYY-MM-DD HH:mm
     * @param int    $id
     * @param int    $calendarid
     * @param string $url   - Link to further information about the event
     */
    public function __construct($title, $desc, $start, $end, $id = -1, $calendarid = -1, $url = "")
    {
        $this->text        = $title;
        $this->description = $desc;
        $this->start_date  = $start;
        $this->end_date    = $end;
        $this->id          = $id;
        $this->calendar_id = $calendarid;
        $this->url         = $url;
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
            self::timestampToSchedulerFormat($data['start']),
            self::timestampToSchedulerFormat($data['end']),
            $data['eventid'],
            $data['calendarid'],
            $data['url']
        );
    }


    /**
     * Converts a UNIX timestamp to the format for DHTMLX Scheduler
     *
     * @param $timestamp int - A unix timestamp
     *
     * @return false|string  - The converted timestamp or false on error
     */
    public static function timestampToSchedulerFormat($timestamp)
    {
        return date("Y-m-d H:i", $timestamp);
    }


    public function toArray()
    {
        return get_object_vars($this);
    }
}
