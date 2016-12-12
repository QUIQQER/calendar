define('package/quiqqer/calendar/bin/classes/Handler', [

    'qui/QUI',
    'qui/classes/DOM',
    'Ajax'

], function (QUI, QUIDOM, QUIAjax)
{
    "use strict";

    var lg = 'quiqqer/calendar';

    return new Class({

        Extends: QUIDOM,
        Type   : 'package/quiqqer/calendar/bin/CalendarWindow',

        initialize: function ()
        {
        },



        addCalendar: function (userid, calendarName)
        {
            return new Promise(function (resolve, reject)
            {
                QUIAjax.post('package_quiqqer_calendar_ajax_createCalendar', resolve, {
                    'package': 'quiqqer/calendar',
                    'userid' : userid,
                    'name'   : calendarName,
                    onError  : reject
                });
            });
        },



        editCalendar: function(calendarID, userid, calendarName)
        {
            return new Promise(function (resolve, reject)
            {
                QUIAjax.post('package_quiqqer_calendar_ajax_editCalendar', resolve, {
                    'package'    : 'quiqqer/calendar',
                    'calendarID' : calendarID,
                    'userid'     : userid,
                    'name'       : calendarName,
                    onError      : reject
                });
            });
        },




        /**
         * Returns the calendar for the given ID as an ical string.
         *
         * @param {int} calendarID
         * @returns {Promise} - The iCal String
         */
        getCalendarAsIcal: function(calendarID)
        {
            return new Promise(function (resolve, reject)
            {
                QUIAjax.get('package_quiqqer_calendar_ajax_getCalendarAsIcal', function(result)
                {
                    resolve(result);
                }, {
                    'package'    : 'quiqqer/calendar',
                    'calendarID' : calendarID,
                    onError      : reject
                });
            });
        },

        /**
         * Edits an event values
         *
         * @param {int} cID - ID of the calendar where the event is in
         * @param {int} eID - ID of the event
         * @param {String} eTitle - new event title
         * @param {String} eDesc - new event description
         * @param {int} eStart - new event start (unix timestamp)
         * @param {int} eEnd - new event end (unix timestamp)
         * @returns {Promise}
         */
        editEvent: function (cID, eID, eTitle, eDesc, eStart, eEnd)
        {
            return new Promise(function (resolve, reject)
            {
                QUIAjax.post('package_quiqqer_calendar_ajax_editEvent', function (result)
                {
                    resolve(result);
                },
                {
                    'package'    : 'quiqqer/calendar',
                    'calendarID' : cID,
                    'eventID'    : eID,
                    'title'      : eTitle,
                    'desc'       : eDesc,
                    'start'      : eStart,
                    'end'        : eEnd,
                    onError      : reject
                });
            });
        },


        /**
         * Adds an event to a calendar
         *
         * @param {int} cID - The calendar ID to add the event to
         * @param {String} title - The event title
         * @param {String} desc - The event description
         * @param {int} start - The event start as UNIX timestamp
         * @param {int} end - The event end as UNIX timestamp
         * @returns {Promise} - Promise with the new event's ID
         */
        addEvent: function (cID, title, desc, start, end)
        {
            return new Promise(function (resolve, reject)
            {
                QUIAjax.post('package_quiqqer_calendar_ajax_addEvent', function (result)
                {
                    resolve(result);
                },
                {
                    'package'    : 'quiqqer/calendar',
                    'calendarID' : cID,
                    'title'      : title,
                    'desc'       : desc,
                    'start'      : start,
                    'end'        : end,
                    onError      : reject
                });
            });
        },

        /**
         * Deletes an event
         *
         * @param {int} cID - The ID of the calendar where the event is in
         * @param {int} eID - The event to delete ID
         * @returns {Promise}
         */
        deleteEvent: function (cID, eID)
        {
            return new Promise(function (resolve, reject)
            {
                QUIAjax.post('package_quiqqer_calendar_ajax_removeEvent', function () {},
                {
                    'package'   : 'quiqqer/calendar',
                    'calendarID': cID,
                    'eventID'   : eID,
                    onError     : reject
                });
            });
        }
    });
});
