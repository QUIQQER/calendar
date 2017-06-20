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
    'qui/controls/loader/Loader',

    'package/bin/mustache/mustache',
    'text!package/quiqqer/calendar/bin/controls/CalendarDisplay.html'

], function (QUI, QUIControl, Calendars, Scheduler, QUILoader, Mustache, displayTemplate)
{
    "use strict";

    var lg = 'quiqqer/calendar';

    return new Class({

        Extends: QUIControl,
        Type   : 'package/quiqqer/calendar/bin/controls/CalendarDisplay',

        calIDs: [],

        schedulerReady: Boolean,

        Scheduler: Scheduler,

        Binds: [
            '$onInject',
            'parseEventsIntoScheduler'
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

            if (options !== undefined) {
                if (options.some(isNaN)) {
                    console.error('Non numeric calendar ID(s)');
                } else {
                    this.calIDs = options;
                }
            }

            this.addEvents({
                onInject: this.$onInject
            });
        },


        $onInject: function ()
        {
            if (this.calIDs.length < 1) {
                try {
                    this.calIDs = this.$Elm.getProperty('data-qui-options-calendarids').split(',');
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
            }

            this.Loader = new QUILoader().inject(this.getElm().getParent());

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
                        self.Loader.show();

                        var color = self.getRandomColor();
                        Calendars.getEventsAsJson(calID).then(function (result)
                        {
                            var events = JSON.parse(result);
                            events.forEach(function (event)
                            {
                                event.color = color;
                            });
                            self.parseEventsIntoScheduler(JSON.stringify(events)).then(function ()
                            {
                                self.Loader.hide();
                            });
                        }).catch(function (error)
                        {
                        });
                    });

                    self.schedulerReady = true;
                    resolve();
                });
            });
        },


        /**
         * Parses a JSON string of events into the scheduler.
         * Resolves when parsing completed.
         *
         * @param {string} events - events as JSON string
         * @return {Promise} - Resolves when parsing completed.
         */
        parseEventsIntoScheduler: function (events)
        {
            var self = this;
            return new Promise(function (resolve)
            {
                self.Scheduler.parse(events, 'json');
                resolve();
            });
        },


        /**
         * Sets width and height of the display
         *
         * @param {Number} width
         * @param {Number} height
         */
        setDimensions: function (width, height)
        {
            if (width < 0 || height < 0) {
                return;
            }
            this.getElm().setAttribute('style', 'width: ' + width + 'px; height: ' + height + 'px;');
        },


        /**
         * Updates the scheduler size when the window is resized
         * event : on resize
         */
        $onResize: function ()
        {
            if (this.schedulerReady) {
                this.Scheduler.update_view();
            }
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