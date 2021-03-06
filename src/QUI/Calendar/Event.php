<?php

namespace QUI\Calendar;

use QUI\Calendar\Event\EventUtils;

class Event
{
    /**
     * @var string - The events title
     */
    protected $title;

    /**
     * @var string - The events description
     */
    protected $description;

    /**
     * @var \DateTime $start_date - The events start time/date
     */
    protected $start_date;

    /**
     * @var \DateTime $end_date - The events end time/date
     */
    protected $end_date;

    /**
     * @var int
     */
    protected $id;

    /**
     * @var int
     */
    protected $calendar_id;

    /**
     * @var string - Link to further information about the event
     */
    protected $url;

    /**
     * Event constructor.
     *
     * @param string    $title
     * @param \DateTime $StartDate - The events start time/date in format YYYY-MM-DD HH:mm
     * @param \DateTime $EndDate   - The events end time/date in format YYYY-MM-DD HH:mm
     */
    public function __construct(string $title, \DateTime $StartDate, \DateTime $EndDate)
    {
        $this->title      = $title;
        $this->start_date = $StartDate;
        $this->end_date   = $EndDate;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @param string $title
     *
     * @return Event
     */
    public function setTitle(string $title): Event
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @return string
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @param string $description
     *
     * @return Event
     */
    public function setDescription(string $description): Event
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getStartDate(): \DateTime
    {
        return $this->start_date;
    }

    /**
     * @param \DateTime $start_date
     *
     * @return Event
     */
    public function setStartDate(\DateTime $start_date): Event
    {
        $this->start_date = $start_date;

        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getEndDate(): \DateTime
    {
        return $this->end_date;
    }

    /**
     * @param \DateTime $end_date
     *
     * @return Event
     */
    public function setEndDate(\DateTime $end_date): Event
    {
        $this->end_date = $end_date;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * @param int $id
     *
     * @return Event
     */
    public function setId(int $id): Event
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getCalendarId(): ?int
    {
        return $this->calendar_id;
    }

    /**
     * @param int $calendar_id
     *
     * @return Event
     */
    public function setCalendarId(int $calendar_id): Event
    {
        $this->calendar_id = $calendar_id;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getUrl(): ?string
    {
        return $this->url;
    }

    /**
     * @param string $url
     *
     * @return Event
     */
    public function setUrl(string $url): Event
    {
        $this->url = $url;

        return $this;
    }

    /**
     * Returns the event's data in an multidimensional array formatted for usage with the database.
     * The array's first dimension represents the table name.
     * The array's second dimension represent the tables column names.
     * The array's third dimension holds the event's data
     *
     * @return array
     */
    public function toArrayForDatabase(): array
    {
        $result[Handler::tableCalendarsEvents()] = [
            'eventid'    => $this->getId(),
            'title'      => $this->getTitle(),
            'desc'       => $this->getDescription(),
            'start'      => $this->getStartDate()->getTimestamp(),
            'end'        => $this->getEndDate()->getTimestamp(),
            'calendarid' => $this->getCalendarId(),
            'url'        => $this->getUrl()
        ];

        return $result;
    }


    /**
     * Converts the event to a format that can be used with the DHTMLX scheduler
     *
     * @return array
     */
    public function toSchedulerFormat(): array
    {
        return [
            'calID'       => $this->getCalendarId(),
            'id'          => $this->getId(),
            'text'        => $this->getTitle(),
            'description' => $this->getDescription(),
            'start_date'  => EventUtils::datetimeToSchedulerFormat($this->getStartDate()),
            'end_date'    => EventUtils::datetimeToSchedulerFormat($this->getEndDate()),
            'url'         => $this->getUrl(),
        ];
    }
}
