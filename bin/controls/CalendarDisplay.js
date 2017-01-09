/**
 * Control that displays a calendar
 *
 * @module 'package/quiqqer/calendar/bin/controls/CalendarDisplay'
 * @author www.pcsg.de (Jan Wennrich)
 *
 * @require 'qui/QUI' *
 * @require 'package/quiqqer/calendar/bin/Calendars'
 * @require 'package/quiqqer/calendar-controls/bin/Scheduler'
 *
 */
define('package/quiqqer/calendar/bin/controls/CalendarDisplay', [

    'qui/QUI',
    'qui/controls/Control',
    'package/quiqqer/calendar/bin/Calendars',
    'package/quiqqer/calendar-controls/bin/Scheduler'

], function (QUI, QUIControl, Calendars, Scheduler)
{
    "use strict";

    var lg = 'quiqqer/calendar';

    return new Class({

        Extends: QUIControl,
        Type   : 'package/quiqqer/calendar/bin/controls/CalendarDisplay',

        id    : null,
        calIDs: [],

        schedulerReady: false,

        Scheduler: null,

        ChangeEvent: null,
        AddEvent   : null,
        DeleteEvent: null,

        Binds: [
            '$onImport'
        ],

        /**
         * Constructor of the class
         *
         * @param options - constructor options
         */
        initialize: function (options)
        {
            this.parent(options);

            this.addEvents({
                onImport: this.$onImport
            });
        },


        $onImport: function ()
        {
            try {
                this.calIDs = JSON.parse(this.getAttribute('calendarids'));
            } catch (Exception) {
                // TODO: show error invalid calendar IDs
                console.error('Invalid calendar ID(s)');
                return;
            }

            // All IDs numeric?
            if (this.calIDs.some(isNaN)) {
                // TODO: show error non numeric calendar IDs
                console.error('Non numeric calendar ID(s)');
                return;
            }

            this.initScheduler(this.getElm());
        },

        /**
         *
         * @param Element - The element to create the Scheduler in
         * @return Promise
         */
        initScheduler: function (Element)
        {
            var self = this;

            return new Promise(function (resolve)
            {
                if (self.schedulerReady) {
                    resolve();
                }

                // Load scheduler extensions
                Promise.all([
                    Scheduler.loadExtension('agenda_view'),
                    Scheduler.loadExtension('cookie')
                ]).then(function (Scheduler)
                {
                    // Get last scheduler object (the one with all loaded extensions)
                    Scheduler = Scheduler[Scheduler.length - 1];

                    // Get the real Scheduler instance
                    self.Scheduler = Scheduler.getScheduler();

                    // Read-Only mode
                    self.Scheduler.config.readonly = true;

                    // Default event length 60 minutes
                    self.Scheduler.config.event_duration = 60;

                    // Remove all events from calendar (if another scheduler was opened previously)
                    self.Scheduler.clearAll();

                    // Container to display the scheduler in
                    self.Scheduler.init(Element);

                    // Parse events from all calendars in Scheduler
                    self.calIDs.forEach(function (calID)
                    {
                        Calendars.getCalendarAsIcal(calID).then(function (result)
                        {
                            self.Scheduler.parse(result, 'ical');

                        });
                    });
                });
            });
        }
    });
});