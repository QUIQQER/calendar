/**
 * Handler that handles Ajax communication for calendars.
 *
 * @module package/quiqqer/calendar/bin/classes/Handler
 * @author www.pcsg.de (Jan Wennrich)
 *
 * @require 'qui/QUI',
 * @require 'qui/classes/DOM',
 * @require 'Ajax'
 */
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

        /**
         * Creates a new calendar
         *
         * @param {int|null} userid     - The ID of the owner. Null for global calendar.
         * @param {String} calendarName - The name of the calendar
         *
         * @return {Promise} - Resolves when calendar was created, rejects on error
         */
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

        /**
         * Edits a calendars values
         *
         * @param {int} calendarID      - The ID of the calendar to edit.
         * @param {int|null} userid     - The user ID of the new owner. Null for global calendar.
         * @param {String} calendarName - The new name of the calendar.
         *
         * @return {Promise} - Resolves when calendar was created, rejects on error
         */
        editCalendar: function (calendarID, userid, calendarName)
        {
            return new Promise(function (resolve, reject)
            {
                QUIAjax.post('package_quiqqer_calendar_ajax_editCalendar', resolve, {
                    'package'   : 'quiqqer/calendar',
                    'calendarID': calendarID,
                    'userid'    : userid,
                    'name'      : calendarName,
                    onError     : reject
                });
            });
        },


        /**
         * Returns the calendar for the given ID as an iCal string.
         *
         * @param {int} calendarID - The calendar ID of which to iCal string should be returned
         *
         * @returns {Promise} - Resolves with the iCal string, rejects on error
         */
        getCalendarAsIcal: function (calendarID)
        {
            return new Promise(function (resolve, reject)
            {
                QUIAjax.get('package_quiqqer_calendar_ajax_getCalendarAsIcal', function (result)
                {
                    resolve(result);
                }, {
                    'package'   : 'quiqqer/calendar',
                    'calendarID': calendarID,
                    onError     : reject
                });
            });
        },

        /**
         * Edits an event values
         *
         * @param {int} cID       - ID of the calendar where the event is in
         * @param {int} eID       - ID of the event
         * @param {String} eTitle - The new event title
         * @param {String} eDesc  - The new event description
         * @param {int} eStart    - The new event start (unix timestamp)
         * @param {int} eEnd      - The new event end (unix timestamp)
         *
         * @returns {Promise} - Resolves when event was edited, rejects on error
         */
        editEvent: function (cID, eID, eTitle, eDesc, eStart, eEnd)
        {
            return new Promise(function (resolve, reject)
            {
                QUIAjax.post('package_quiqqer_calendar_ajax_editEvent', resolve,
                    {
                        'package'   : 'quiqqer/calendar',
                        'calendarID': cID,
                        'eventID'   : eID,
                        'title'     : eTitle,
                        'desc'      : eDesc,
                        'start'     : eStart,
                        'end'       : eEnd,
                        onError     : reject
                    });
            });
        },


        /**
         * Adds an event to a calendar
         *
         * @param {int} cID      - The calendar ID to add the event to
         * @param {String} title - The event title
         * @param {String} desc  - The event description
         * @param {int} start    - The event start as UNIX timestamp
         * @param {int} end      - The event end as UNIX timestamp
         *
         * @returns {Promise} - Resolves with the assigned event id, rejects on error
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
                        'package'   : 'quiqqer/calendar',
                        'calendarID': cID,
                        'title'     : title,
                        'desc'      : desc,
                        'start'     : start,
                        'end'       : end,
                        onError     : reject
                    });
            });
        },

        /**
         * Deletes an event
         *
         * @param {int} cID - The ID of the calendar where the event is in
         * @param {int} eID - The event to delete ID
         *
         * @returns {Promise} - Resolves when event was removed, rejects on error
         */
        deleteEvent: function (cID, eID)
        {
            return new Promise(function (resolve, reject)
            {
                QUIAjax.post('package_quiqqer_calendar_ajax_removeEvent', resolve,
                    {
                        'package'   : 'quiqqer/calendar',
                        'calendarID': cID,
                        'eventID'   : eID,
                        onError     : reject
                    });
            });
        },

        /**
         * Deletes calendars with the given IDs
         *
         * @param {array} ids - Array with IDs of calendars to delete
         *
         * @return {Promise} - Resolves when calendars where deleted, rejects on error
         */
        deleteCalendars: function (ids)
        {
            return new Promise(function (resolve, reject)
            {
                QUIAjax.post('package_quiqqer_calendar_ajax_delete', resolve, {
                    'package': 'quiqqer/calendar',
                    ids      : JSON.encode(ids),
                    onError  : reject
                });
            })
        }

    });
});
