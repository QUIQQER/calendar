<?php

namespace QUI\Calendar;

use DateTime;
use DateTimeImmutable;
use ICal\ICal;
use QUI;
use QUI\Calendar\Event\EventUtils;
use QUI\Calendar\Event\EventCollection;

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
    protected function construct(array $data): void
    {
        if ($data['isExternal'] == 0) {
            throw new Exception('Calendar with ID ' . $this->getId() . ' is internal but was created as external');
        }

        parent::construct($data);
        $this->externalUrl = $data['externalUrl'];

        $this->icalCacheKey = self::CALENDAR_ICAL_CACHE_KEY_PREFIX . $this->getId();
    }


    /**
     * @inheritdoc
     *
     * @throws QUI\Calendar\Exception\NoPermissionException - Current user isn't allowed to view the calendar
     * @throws QUI\Exception - External calendar's URL can not be reached or is invalid
     */
    public function toICal(): string
    {
        $this->checkPermission(self::PERMISSION_VIEW_CALENDAR);

        try {
            return QUI\Cache\Manager::get($this->icalCacheKey);
        } catch (\Exception  $exception) {
            // No data cached

            if (!QUI\Utils\Request\Url::isReachable($this->externalUrl)) {
                throw new Exception(['quiqqer/calendar', 'message.calendar.external.error.url.unreachable']);
            }

            try {
                $icalData = QUI\Utils\Request\Url::get($this->externalUrl);
            } catch (QUI\Exception $Exception) {
                throw new Exception(['quiqqer/calendar', 'message.calendar.external.error.url.unreachable']);
            }

            if (!self::isValidIcal($icalData)) {
                throw new Exception(['quiqqer/calendar', 'exception.ical.invalid']);
            }

            $Package     = QUI::getPackage('quiqqer/calendar');
            $Config      = $Package->getConfig();
            $cachingTime = $Config->getValue('general', 'caching_time');

            QUI\Cache\Manager::set($this->icalCacheKey, $icalData, $cachingTime);

            return $icalData;
        }
    }


    /**
     * @inheritdoc
     *
     * @throws QUI\Calendar\Exception\NoPermissionException - Current user isn't allowed to view the calendar
     * @throws QUI\Exception - Calendar's iCal could not be loaded (URL is not reachable or content invalid)
     */
    public function toJSON(): string
    {
        $this->checkPermission(self::PERMISSION_VIEW_CALENDAR);

        return json_encode($this->getEvents());
    }


    /**
     * Sets the URL to an iCal file that the calendar should use
     *
     * @param string $externalUrl - URL to an iCal file
     *
     * @return void
     *
     * @throws Exception - URL is not reachable/valid
     * @throws QUI\Calendar\Exception\DatabaseException - Could not update the external URL in the database
     * @throws Exception\NoPermissionException - User is not permitted to edit the calendar
     */
    public function setExternalUrl(string $externalUrl): void
    {
        $this->checkPermission(self::PERMISSION_EDIT_CALENDAR);

        if (!QUI\Utils\Request\Url::isReachable($externalUrl)) {
            $msg = QUI::getLocale()->get(
                'quiqqer/calendar',
                'message.calendar.external.error.url.invalid'
            );
            throw new Exception($msg);
        }

        if ($this->externalUrl !== $externalUrl) {
            $this->externalUrl = $externalUrl;

            try {
                QUI::getDataBase()->update(
                    Handler::tableCalendars(),
                    ['externalUrl' => $externalUrl],
                    ['id' => $this->getId()]
                );
            } catch (\QUI\Database\Exception $Exception) {
                QUI\System\Log::writeException($Exception);
                throw new QUI\Calendar\Exception\DatabaseException();
            }

            // Clear cache entry since the URL has changed -> different data
            QUI\Cache\Manager::clear($this->icalCacheKey);
        }
    }


    /**
     * @inheritdoc
     *
     * @throws QUI\Calendar\Exception\NoPermissionException - Current user isn't allowed to view the calendar
     * @throws QUI\Exception - Calendar's iCal could not be loaded (URL is not reachable or content invalid)
     * @throws \Exception - Event has invalid date format
     */
    public function getEvents(): EventCollection
    {
        $this->checkPermission(self::PERMISSION_VIEW_CALENDAR);

        $ICal = new ICal();
        $ICal->initString($this->toICal());

        $eventsRaw = $ICal->events();
        $Events    = new EventCollection();
        foreach ($eventsRaw as $IcalEvent) {
            /** @var \ICal\Event $IcalEvent */
            $Event = EventUtils::createEventFromIcsParserEventData($IcalEvent);
            $Event->setCalendarId($this->getId());

            $Events->append($Event);
        }

        return $Events;
    }


    /**
     * @inheritdoc
     *
     * @throws QUI\Calendar\Exception\NoPermissionException - Current user isn't allowed to view the calendar
     * @throws \Exception - Something went wrong converting the given date to timestamps
     */
    public function getEventsForDate(DateTime $Date, bool $ignoreTime): EventCollection
    {
        $this->checkPermission(self::PERMISSION_VIEW_CALENDAR);

        $timestampStart = $Date->format(DATE_ISO8601);
        $timestampEnd   = $timestampStart;

        if ($ignoreTime) {
            $DateImmutable = DateTimeImmutable::createFromMutable($Date);

            $timestampStart = $DateImmutable->setTime(0, 0, 0)->format(DATE_ISO8601);
            $timestampEnd   = $DateImmutable->setTime(23, 59, 59)->format(DATE_ISO8601);
        }

        $ICal = new ICal();
        $ICal->initString($this->toICal());

        $eventsRaw = $ICal->eventsFromRange($timestampStart, $timestampEnd);

        $Events = new QUI\Calendar\Event\EventCollection();
        foreach ($eventsRaw as $IcalEvent) {
            /** @var \ICal\Event $IcalEvent */
            $Event = EventUtils::createEventFromIcsParserEventData($IcalEvent);
            $Event->setCalendarId($this->getId());

            $Events->append($Event);
        }

        return $Events;
    }

    /**
     * @inheritDoc
     *
     * @param DateTime $IntervalStart
     * @param DateTime $IntervalEnd
     * @param bool     $ignoreTime
     * @param int      $limit
     *
     * @return Event\EventCollection
     *
     * @throws QUI\Calendar\Exception\NoPermissionException - Current user isn't allowed to view the calendar
     * @throws \Exception - Something went wrong converting the given date to timestamps
     */
    public function getEventsBetweenDates(
        DateTime $IntervalStart,
        DateTime $IntervalEnd,
        bool $ignoreTime,
        int $limit
    ): EventCollection {

        $this->checkPermission(self::PERMISSION_VIEW_CALENDAR);

        $timestampStartDate = $IntervalStart->format(DATE_ISO8601);
        $timestampEndDate   = $IntervalEnd->format(DATE_ISO8601);

        if ($ignoreTime) {
            $StartDateImmutable = DateTimeImmutable::createFromMutable($IntervalStart);
            $timestampStartDate = $StartDateImmutable->setTime(0, 0, 0)->format(DATE_ISO8601);

            $EndDateImmutable = DateTimeImmutable::createFromMutable($IntervalEnd);
            $timestampEndDate = $EndDateImmutable->setTime(23, 59, 59)->format(DATE_ISO8601);
        }

        $ICal = new ICal();
        $ICal->initString($this->toICal());

        $eventsRaw = $ICal->eventsFromRange($timestampStartDate, $timestampEndDate);

        $Events = new EventCollection();
        foreach ($eventsRaw as $IcalEvent) {
            /** @var \ICal\Event $IcalEvent */
            $Event = EventUtils::createEventFromIcsParserEventData($IcalEvent);
            $Event->setCalendarId($this->getId());

            $Events->append($Event);
        }

        return $Events;
    }


    /**
     * @inheritDoc
     *
     * @param int $amount
     *
     * @return EventCollection
     *
     * @throws QUI\Calendar\Exception\NoPermissionException - Current user isn't allowed to view the calendar
     * @throws QUI\Exception - Calendar's iCal could not be loaded (URL is not reachable or content invalid)
     * @throws \Exception - Invalid date format in an event
     */
    public function getUpcomingEvents(int $amount = -1): EventCollection
    {
        $this->checkPermission(self::PERMISSION_VIEW_CALENDAR);

        $ICal = new ICal();
        $ICal->initString($this->toICal());

        try {
            $eventsRaw = $ICal->eventsFromRange();  // gets events from now on
        } catch (\Exception $Exception) {
            // We should never end up here, but just to be save...
            $eventsRaw = [];
        }

        if ($amount == -1) {
            $amount = PHP_INT_MAX;
        }

        $count  = 0;
        $Events = new EventCollection();
        foreach ($eventsRaw as $key => $IcalEvent) {
            if ($count >= $amount) {
                break;
            }

            /** @var \ICal\Event $IcalEvent */
            $Event = EventUtils::createEventFromIcsParserEventData($IcalEvent);
            $Event->setCalendarId($this->getId());

            $Events->append($Event);

            $count++;
        }

        return $Events;
    }

    /**
     * Checks if an iCal string is valid
     *
     * @param string $icalString - The iCal string to check
     *
     * @return boolean - Is the iCal string valid
     */
    public static function isValidIcal(string $icalString): bool
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
    public function isInternal(): bool
    {
        return false;
    }
}
