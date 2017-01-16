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
            var calID = this.getAttribute('calendarid');

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


        attachEvents: function ()
        {
            var self = this;

            // Run when an event is edited in the scheduler
            this.ChangeEvent = this.Scheduler.attachEvent('onEventChanged', function (id, ev)
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
            this.AddEvent = this.Scheduler.attachEvent('onEventAdded', function (id, ev)
            {
                Calendars.addEvent(
                    self.calID,
                    ev.text,
                    ev.text,
                    ev.start_date.getTime() / 1000,
                    ev.end_date.getTime() / 1000
                ).then(function (result)
                {
                    console.log(result);
                    if (result == null) {
                        return;
                    }
                    self.Scheduler.changeEventId(id, parseInt(result));
                });
            });

            // Run when an event is deleted from scheduler
            this.DeleteEvent = this.Scheduler.attachEvent('onEventDeleted', function (id)
            {
                Calendars.deleteEvent(self.calID, id);
            });
        }
    });
});