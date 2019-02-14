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
     *
     * @throws Exception - Given calendar data doesn't belong to an external calendar
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


    /**
     * Returns a string in iCal format containing the calendar's events
     *
     * @return string
     *
     * @throws QUI\Exception - Calendar's URL is not reachable or contains invalid iCal
     */
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

            // TODO: Issue #21
            $icalData = file_get_contents($this->externalUrl);

            if (!self::isValidIcal($icalData)) {
                $msg = QUI::getLocale()->get(
                    'quiqqer/calendar',
                    'exception.ical.invalid'
                );
                throw new Exception($msg);
            }

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
     *
     * @throws QUI\Calendar\Exception\NoPermission - Current user isn't allowed to view the calendar
     */
    public function toICal()
    {
        $this->checkPermission(self::PERMISSION_VIEW_CALENDAR);

        return $this->getIcalData();
    }


    /**
     * @inheritdoc
     *
     * @throws QUI\Calendar\Exception\NoPermission - Current user isn't allowed to view the calendar
     */
    public function toJSON()
    {
        $this->checkPermission(self::PERMISSION_VIEW_CALENDAR);

        return json_encode($this->getEvents());
    }


    /**
     * Sets the URL to an iCal file that the calendar should use
     *
     * @param string $externalUrl - URL to an iCal file
     *
     * @throws Exception - URL is not reachable/valid
     * @throws Exception\NoPermission - User is not permitted to edit the calendar
     */
    public function setExternalUrl($externalUrl)
    {
        $this->checkPermission(self::PERMISSION_EDIT_CALENDAR);

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
     *
     * @throws QUI\Calendar\Exception\NoPermission - Current user isn't allowed to view the calendar
     */
    public function getEvents()
    {
        $this->checkPermission(self::PERMISSION_VIEW_CALENDAR);

        $ICal = new ICal();
        $ICal->initString($this->getIcalData());

        $eventsRaw = $ICal->events();
        $events    = array();

        foreach ($eventsRaw as $key => $Event) {
            $start = Event::timestampToSchedulerFormat((int)$ICal->iCalDateToUnixTimestamp($Event->dtstart));
            $end   = Event::timestampToSchedulerFormat((int)$ICal->iCalDateToUnixTimestamp($Event->dtend));

            $events[] = new Event($Event->summary, $Event->description, $start, $end, $Event->uid, $this->getId());
        }

        return $events;
    }


    /**
     * @inheritdoc
     *
     * @throws QUI\Calendar\Exception\NoPermission - Current user isn't allowed to view the calendar
     * @throws \Exception - Something went wrong converting the given date to timestamps
     */
    public function getEventsForDate(\DateTime $Date, $ignoreTime)
    {
        $this->checkPermission(self::PERMISSION_VIEW_CALENDAR);

        $timestampStart = $Date->format(DATE_ISO8601);
        $timestampEnd   = $timestampStart;

        if ($ignoreTime) {
            $DateImmutable = \DateTimeImmutable::createFromMutable($Date);

            $timestampStart = $DateImmutable->setTime(0, 0, 0)->format(DATE_ISO8601);
            $timestampEnd   = $DateImmutable->setTime(23, 59, 59)->format(DATE_ISO8601);
        }

        $ICal = new ICal();
        $ICal->initString($this->getIcalData());

        $eventsRaw = $ICal->eventsFromRange($timestampStart, $timestampEnd);

        $Events = new EventCollection();
        foreach ($eventsRaw as $eventData) {
            $start = Event::timestampToSchedulerFormat((int)$ICal->iCalDateToUnixTimestamp($eventData->dtstart));
            $end   = Event::timestampToSchedulerFormat((int)$ICal->iCalDateToUnixTimestamp($eventData->dtend));

            $Event = new Event(
                $eventData->summary,
                $eventData->description,
                $start,
                $end,
                $eventData->uid,
                $this->getId()
            );
            $Events->append($Event);
        }

        return $Events;
    }


    /**
     * @inheritdoc
     *
     * @throws QUI\Calendar\Exception\NoPermission - Current user isn't allowed to view the calendar
     * @throws \Exception - Something went wrong converting the given date to timestamps
     */
    public function getEventsBetweenDates(\DateTime $StartDate, \DateTime $EndDate, $ignoreTime)
    {
        $this->checkPermission(self::PERMISSION_VIEW_CALENDAR);

        $timestampStartDate = $StartDate->format(DATE_ISO8601);
        $timestampEndDate   = $EndDate->format(DATE_ISO8601);

        if ($ignoreTime) {
            $StartDateImmutable = \DateTimeImmutable::createFromMutable($StartDate);
            $timestampStartDate = $StartDateImmutable->setTime(0, 0, 0)->format(DATE_ISO8601);

            $EndDateImmutable = \DateTimeImmutable::createFromMutable($EndDate);
            $timestampEndDate = $EndDateImmutable->setTime(23, 59, 59)->format(DATE_ISO8601);
        }

        $ICal = new ICal();
        $ICal->initString($this->getIcalData());

        $eventsRaw = $ICal->eventsFromRange($timestampStartDate, $timestampEndDate);

        $Events = new EventCollection();
        foreach ($eventsRaw as $eventData) {
            $start = Event::timestampToSchedulerFormat((int)$ICal->iCalDateToUnixTimestamp($eventData->dtstart));
            $end   = Event::timestampToSchedulerFormat((int)$ICal->iCalDateToUnixTimestamp($eventData->dtend));

            $Event = new Event(
                $eventData->summary,
                $eventData->description,
                $start,
                $end,
                $eventData->uid,
                $this->getId()
            );
            $Events->append($Event);
        }

        return $Events;
    }


    /**
     * @inheritdoc
     *
     * @throws QUI\Calendar\Exception\NoPermission - Current user isn't allowed to view the calendar
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

            $start = Event::timestampToSchedulerFormat((int)$ICal->iCalDateToUnixTimestamp($Event->dtstart));
            $end   = Event::timestampToSchedulerFormat((int)$ICal->iCalDateToUnixTimestamp($Event->dtend));

            $events[] = new Event($Event->summary, $Event->description, $start, $end, $Event->uid, $this->getId());
            $count++;
        }

        return $events;
    }

    /**
     * Checks if an iCal string is valid
     *
     * @param string $icalString - The iCal string to check
     * @return boolean - Is the iCal string valid
     */
    public static function isValidIcal($icalString)
    {
        $icalString = trim($icalString);

        // Does file start with "BEGIN:VCALENDAR" and end with "END:VCALENDAR"?
        $isValid = (
            strpos($icalString, "BEGIN:VCALENDAR") === 0 &&
            strpos($icalString, "END:VCALENDAR") === strlen($icalString) - strlen("END:VCALENDAR")
        );

        return $isValid;
    }


    /**
     * @inheritdoc
     */
    public function isInternal()
    {
        return false;
    }
}
