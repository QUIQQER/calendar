<?php

namespace QUI\Calendar;

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
     * @param \DateTime $start - The events start time/date in format YYYY-MM-DD HH:mm
     * @param \DateTime $end   - The events end time/date in format YYYY-MM-DD HH:mm
     */
    public function __construct(string $title, \DateTime $start, \DateTime $end)
    {
        $this->title      = $title;
        $this->start_date = $start;
        $this->end_date   = $end;
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
     * @return array
     */
    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
