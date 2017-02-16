/**
 * Control that displays an editable calendar
 *
 * @module 'package/quiqqer/calendar/bin/controls/CalendarEditDisplay'
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
define('package/quiqqer/calendar/bin/controls/CalendarEditDisplay', [

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
        Type   : 'package/quiqqer/calendar/bin/controls/CalendarEditDisplay',

        calID: Number,

        schedulerReady: Boolean,

        Scheduler: Scheduler,

        ChangeEventInCalendarEvent: null,
        AddEventToCalendarEvent   : null,
        DeleteEventFromCalendarEvent: null,

        Binds: [
            '$onInject',
            '$onResize'
        ],

        /**
         * Constructor of the class
         *
         * @param options - constructor options
         */
        initialize: function (options)
        {
            this.parent(options);

            this.calID = options;

            this.schedulerReady = false;

            this.addEvents({
                onInject: this.$onInject,
                onResize: this.$onResize
            });
        },


        /**
         * Determines the calendar ID and starts initializing the Scheduler
         *
         * Fired when Class is inserted in HTML via qui-data Attribute
         */
        $onInject: function ()
        {
            var calID;
            if (this.calID !== undefined) {
                calID = this.calID;
            } else {
                calID = this.getAttribute('calendarid');
            }

            // Is ID numeric?
            if (isNaN(calID)) {
                // TODO: show error non numeric calendar IDs
                console.error('Non numeric calendar ID');
                return;
            }

            this.calID = calID;

            this.initScheduler(this.getElm());
        },


        /**
         * Initialize the scheduler
         *
         * @param Element - The element to create the Scheduler in
         * @return Promise - Resolves when Scheduler is initialized
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

                    // Can the current User edit the calendar?
                    // Throws error if not editable
                    Calendars.canUserEditCalendar(self.calID).catch(function()
                    {
                        self.Scheduler.config.readonly = true;
                    });

                    // Set date format
                    self.Scheduler.config.xml_date = "%Y-%m-%d %H:%i";

                    // Default event length 60 minutes
                    self.Scheduler.config.event_duration = 60;

                    // Remove all events from calendar (if another scheduler was opened previously)
                    self.Scheduler.clearAll();

                    // Container to display the scheduler in
                    self.Scheduler.init(Element.getElementById('calendar'));

                    self.attachEvents();

                    Calendars.getEventsAsJson(self.calID).then(function (result)
                    {
                        var events = JSON.parse(result);
                        self.Scheduler.parse(JSON.stringify(events), 'json');
                        self.schedulerReady = true;
                        resolve();
                    }).catch(function ()
                    {
                        console.error('Error getting events');
                    });
                });
            });
        },


        /**
         * Updates the scheduler size when the window is resized
         * event : on resize
         */
        $onResize: function ()
        {
            if(this.schedulerReady) {
                this.Scheduler.update_view();
            }
        },


        /**
         * Sets width and height of the display
         *
         * @param {Number} width
         * @param {Number} height
         */
        setDimensions: function(width, height)
        {
            if (width < 0 || height < 0) {
                return;
            }
            this.getElm().setAttribute('style', 'width: ' + width + 'px; height: ' + height + 'px;');
        },


        /**
         * Adds an event to the Scheduler
         *
         * @param {Object} data            - Object with the parameters text, start_date, end_date
         * @param {String} data.text       - Description of the event
         * @param {String} data.start_date - Start date and time of the event in format '%d-%m-%Y %H:%i'
         * @param {String} data.end_date   - End date and time of the event in format '%d-%m-%Y %H:%i'
         */
        addEventToScheduler: function (data)
        {
            this.Scheduler.addEvent(data);
        },


        /**
         * Attaches add, edit, delete Event events to the Scheduler
         */
        attachEvents: function ()
        {
            var self = this;

            // Run when an event is edited in the scheduler
            this.ChangeEventInCalendarEvent = this.Scheduler.attachEvent('onEventChanged', function (id, ev)
            {
                Calendars.editEvent(
                    self.calID,
                    ev.id,
                    ev.text,
                    ev.description,
                    ev.start_date.getTime() / 1000,
                    ev.end_date.getTime() / 1000
                );
            });

            // Run when an event is added to the scheduler
            this.AddEventToCalendarEvent = this.Scheduler.attachEvent('onEventAdded', function (id, ev)
            {
                Calendars.addEvent(
                    self.calID,
                    ev.text,
                    ev.text,
                    ev.start_date.getTime() / 1000,
                    ev.end_date.getTime() / 1000
                ).then(function (result)
                {
                    if (result == null) {
                        return;
                    }
                    self.Scheduler.changeEventId(id, parseInt(result));
                });
            });

            // Run when an event is deleted from scheduler
            this.DeleteEventFromCalendarEvent = this.Scheduler.attachEvent('onEventDeleted', function (id)
            {
                Calendars.deleteEvent(self.calID, id);
            });
        },


        detachEvents: function()
        {
            if(this.schedulerReady) {
                this.Scheduler.detachEvent(this.AddEventToCalendarEvent);
                this.Scheduler.detachEvent(this.ChangeEventInCalendarEvent);
                this.Scheduler.detachEvent(this.DeleteEventFromCalendarEvent);
            }
        }
    });
});