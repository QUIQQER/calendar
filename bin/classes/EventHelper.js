/**
 * Helper class for event operations.
 *
 * @module package/quiqqer/calendar/bin/classes/EventHelper
 * @author www.pcsg.de (Jan Wennrich)
 *
 */
define('package/quiqqer/calendar/bin/classes/EventHelper', [], function () {
    "use strict";

    return new Class({

        Type: 'package/quiqqer/calendar/bin/classes/EventHelper',

        /**
         * Converts a date object to a string to be used with the DHTMLX scheduler.
         *
         * @param {Date} Date
         *
         * @return {String}
         */
        convertDateToSchedulerFormat: function (Date) {
            // getMonth returns zero for january
            return Date.getFullYear() + "-" + (Date.getMonth() + 1) + "-" + Date.getDate() +
                   " " +
                   Date.getHours() + ":" + Date.getMinutes();
        },


        /**
         * Returns if the given start and end dates represent a whole-day-event
         *
         * @param {Object} Event
         *
         * @return {boolean}
         */
        isWholeDayEvent: function (Event) {
            var EndDate = this.getSchedulerEventEndDate(Event);
            var differenceInSeconds = (EndDate.getTime() - Event.start_date.getTime()) / 1000;
            var secondsPerDay = 86400;

            return differenceInSeconds == secondsPerDay;
        },


        /**
         * Returns the scheduler's events recurrence format as QUIQQER-usable recurrence interval
         *
         * @example 'week_1___' returns 'week'
         *
         * @param {string} pattern
         */
        convertRecurrencePatternToInterval: function (pattern) {
            return pattern.split('_')[0];
        },


        /**
         * Returns the Date an event ends.
         * The DHTMLX scheduler stores the event's length in seconds instead of it's end, when an event is recurring
         *
         * @param Event
         *
         * @return {Date}
         */
        getSchedulerEventEndDate: function (Event) {
            if (!Event.recurring) {
                return Event.end_date;
            }

            var Result = new Date();
            Result.setTime(Event.start_date.getTime() + (Event.event_length * 1000));

            return Result;
        }
    });
});
