<?php

namespace QUI\Calendar;

use ICal\ICal;
use QUI;

/**
 * Class Calendar
 * one Calendar
 *
 * @package QUI\Calendar
 */
class ExternalCalendar extends AbstractCalendar
{
    /**
     * URL to the external calendar data
     *
     * @var string
     */
    protected $externalUrl;


    /**
     * Cache key under which the iCal data is cached
     *
     * @var string
     */
    protected $icalCacheKey;


    /**
     * Prefix for accessing ical data in cache.
     * Append the calendar id to this prefix for the complete cache key
     */
    const CALENDAR_ICAL_CACHE_KEY_PREFIX = 'calendar-ical-';


    /**
     * @inheritdoc
     */
    protected function construct($data)
    {
        if ($data['isExternal'] == 0) {
            throw new Exception('Calendar is not external');
        }

        parent::construct($data);
        $this->externalUrl = $data['externalUrl'];

        $this->icalCacheKey = self::CALENDAR_ICAL_CACHE_KEY_PREFIX . $this->getId();
    }


    public function getIcalData()
    {
        try {
            return QUI\Cache\Manager::get($this->icalCacheKey);
        } catch (\Exception  $exception) {
            // No data cached
            $icalData = file_get_contents($this->externalUrl);
            QUI\Cache\Manager::set($this->icalCacheKey, $icalData, 60 * 60);

            return $icalData;
        }
    }


    /**
     * @inheritdoc
     */
    public function toICal()
    {
        $this->checkPermission(self::PERMISSION_VIEW_CALENDAR);

        return $this->getIcalData();
    }


    /**
     * @inheritdoc
     */
    public function toJSON()
    {
        $this->checkPermission(self::PERMISSION_VIEW_CALENDAR);

        return json_encode($this->getEvents());
    }


    /**
     * @inheritdoc
     */
    public function getEvents()
    {
        $this->checkPermission(self::PERMISSION_VIEW_CALENDAR);

        $ICal = new ICal();
        $ICal->initString($this->getIcalData());

        $eventsRaw = $ICal->events();
        $events    = array();

        foreach ($eventsRaw as $key => $Event) {
            $start = $this->timestampToSchedulerFormat((int)$ICal->iCalDateToUnixTimestamp($Event->dtstart));
            $end   = $this->timestampToSchedulerFormat((int)$ICal->iCalDateToUnixTimestamp($Event->dtend));

            $events[] = new Event($Event->summary, $Event->description, $start, $end, $Event->uid, $this->getId());
        }

        return $events;
    }
}
