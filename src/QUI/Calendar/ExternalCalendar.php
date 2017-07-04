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
            throw new Exception('Calendar with ID ' . $this->getId() . ' is internal but was created as external');
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
            if (!self::isUrlReachable($this->externalUrl)) {
                $msg = QUI::getLocale()->get(
                    'quiqqer/calendar',
                    'message.calendar.external.error.url.unreachable'
                );
                throw new Exception($msg);
            }
            $icalData = file_get_contents($this->externalUrl);

            $Package     = QUI::getPackage('quiqqer/calendar');
            $Config      = $Package->getConfig();
            $cachingTime = $Config->getValue('general', 'caching_time');

            QUI\Cache\Manager::set($this->icalCacheKey, $icalData, $cachingTime);

            return $icalData;
        }
    }


    /**
     * Determines whether a URL is reachable or not
     *
     * @param string $url - An URL
     *
     * @return boolean
     */
    public static function isUrlReachable($url)
    {
        $validUrl = false;
        try {
            list($status) = get_headers($url);
            if (strpos($status, '200') !== false) {
                // url returns HTTP code 200 -> everything is fine
                $validUrl = true;
            }
        } catch (\Exception $exception) {
        }

        return $validUrl;
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


    public function setExternalUrl($externalUrl)
    {
        if (!self::isUrlReachable($externalUrl)) {
            $msg = QUI::getLocale()->get(
                'quiqqer/calendar',
                'message.calendar.external.error.url.invalid'
            );
            throw new Exception($msg);
        }

        if ($this->externalUrl !== $externalUrl) {
            $this->externalUrl = $externalUrl;

            QUI::getDataBase()->update(
                Handler::tableCalendars(),
                ['externalUrl' => $externalUrl],
                ['id' => $this->getId()]
            );

            // Clear cache entry since the URL has changed -> different data
            QUI\Cache\Manager::clear($this->icalCacheKey);
        }
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


    /**
     * @inheritdoc
     */
    public function getUpcomingEvents($amount = -1)
    {
        $this->checkPermission(self::PERMISSION_VIEW_CALENDAR);

        $ICal = new ICal();
        $ICal->initString($this->getIcalData());

        $eventsRaw = $ICal->eventsFromRange();  // gets event from now on
        $events    = array();

        if ($amount == -1) {
            $amount = PHP_INT_MAX;
        }

        $count = 0;
        foreach ($eventsRaw as $key => $Event) {
            if ($count >= $amount) {
                break;
            }

            $start = $this->timestampToSchedulerFormat((int)$ICal->iCalDateToUnixTimestamp($Event->dtstart));
            $end   = $this->timestampToSchedulerFormat((int)$ICal->iCalDateToUnixTimestamp($Event->dtend));

            $events[] = new Event($Event->summary, $Event->description, $start, $end, $Event->uid, $this->getId());
            $count++;
        }

        return $events;
    }
}
