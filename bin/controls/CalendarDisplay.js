/**
 * Control that displays a calendar
 *
 * @module 'package/quiqqer/calendar/bin/controls/CalendarDisplay'
 * @author www.pcsg.de (Jan Wennrich)
 *
 * @require 'qui/QUI' *
 * @require 'qui/controls/Control',
 * @require 'package/quiqqer/calendar/bin/Calendars'
 * @require 'package/quiqqer/calendar-controls/bin/Scheduler'
 *
 * @require 'Mustache'
 *
 */
define('package/quiqqer/calendar/bin/controls/CalendarDisplay', [

    'qui/QUI',
    'qui/controls/Control',
    'package/quiqqer/calendar/bin/Calendars',
    'package/quiqqer/calendar-controls/bin/Scheduler',

    'package/bin/mustache/mustache',
    'text!package/quiqqer/calendar/bin/controls/CalendarDisplay.html'

], function (QUI, QUIControl, Calendars, Scheduler, Mustache, displayTemplate)
{
    "use strict";

    var lg = 'quiqqer/calendar';

    return new Class({

        Extends: QUIControl,
        Type   : 'package/quiqqer/calendar/bin/controls/CalendarDisplay',

        calIDs: Array,

        schedulerReady: Boolean,

        Scheduler: Scheduler,

        Binds: [
            '$onInject'
        ],

        /**
         * Constructor of the class
         *
         * @param options - constructor options
         */
        initialize: function (options)
        {
            this.parent(options);

            this.schedulerReady = false;

            this.addEvents({
                onInject: this.$onInject
            });
        },


        $onInject: function ()
        {
            try {
                this.calIDs = JSON.parse(this.getAttribute('calendarids'));
            } catch (Exception) {
                // TODO: show error invalid calendar IDs
                console.error('Invalid calendar ID(s). Must be array in JSON format');
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
                // If scheduler already initiated return/resolve
                if (self.schedulerReady) {
                    resolve();
                }

                Element.set({
                    html: Mustache.render(displayTemplate)
                });

                // Load scheduler extensions
                Promise.all([
                    Scheduler.loadExtension('agenda_view')
                ]).then(function (Scheduler)
                {
                    // Get last scheduler object (the one with all loaded extensions)
                    Scheduler = Scheduler[Scheduler.length - 1];

                    // Get the real Scheduler instance
                    self.Scheduler = Scheduler.getScheduler();

                    // Set date format
                    self.Scheduler.config.xml_date = "%Y-%m-%d %H:%i";

                    // Read-Only mode
                    self.Scheduler.config.readonly = true;

                    // Default event length 60 minutes
                    self.Scheduler.config.event_duration = 60;

                    // Remove all events from calendar (if another scheduler was opened previously)
                    self.Scheduler.clearAll();

                    // Container to display the scheduler in
                    self.Scheduler.init(Element.getElementById('calendar'));

                    // Parse events from all calendars in Scheduler
                    self.calIDs.forEach(function (calID)
                    {
                        var color = self.getRandomColor();
                        Calendars.getEventsAsJson(calID).then(function (result)
                        {
                            var events = JSON.parse(result);
                            events.forEach(function (event)
                            {
                                event.color = color;
                            });
                            self.Scheduler.parse(JSON.stringify(events), 'json');
                        });
                    });

                    self.schedulerReady = true;
                    resolve();
                });
            });
        },

        /**
         * Generates a random rgb(X, Y, Z) string
         * @return {string} - The random color string
         */
        getRandomColor: function ()
        {
            var color_r = Math.floor(Math.random() * 255),
                color_g = Math.floor(Math.random() * 255),
                color_b = Math.floor(Math.random() * 255);
            return 'rgb(' + color_r + ',' + color_g + ',' + color_b + ')';
        }

    });
});