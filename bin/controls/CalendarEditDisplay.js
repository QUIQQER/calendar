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
    'package/quiqqer/calendar/bin/AddEventWindow',
    'package/quiqqer/calendar/bin/classes/ColorHelper',
    'package/quiqqer/calendar-controls/bin/Scheduler',
    'qui/controls/loader/Loader',

    'Locale',

    'package/bin/mustache/mustache',
    'text!package/quiqqer/calendar/bin/controls/CalendarDisplay.html'

], function (QUI, QUIControl, Calendars, AddEventWindowControl, ColorHelper, Scheduler, QUILoader, QUILocale, Mustache, displayTemplate) {
    "use strict";

    var lg = 'quiqqer/calendar';

    return new Class({

        Extends: QUIControl,
        Type   : 'package/quiqqer/calendar/bin/controls/CalendarEditDisplay',

        calID: Number,

        calendarData: Object,

        schedulerReady: Boolean,

        Scheduler: Scheduler,

        Loader: null,

        BeforeDragEventInCalendarEvent: null,

        BeforeChangeEventInCalendarEvent: null,
        ChangeEventInCalendarEvent      : null,

        AddEventToCalendarEvent: null,

        BeforeDeleteEventFromCalendarEvent: null,
        DeleteEventFromCalendarEvent      : null,

        Binds: [
            '$onInject',
            '$onResize',
            'parseEventsIntoScheduler'
        ],

        /**
         * Constructor of the class
         *
         * @param options - constructor options
         */
        initialize: function (options) {
            this.parent(options);

            if (!this.getAttribute('extensions')) {
                this.setAttribute('extensions', ['agenda_view', 'recurring']);
            }

            this.setAttribute('canUserEditEvents', false);
            this.setAttribute('canUserDeleteEvents', false);

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
        $onInject: function () {
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

            this.Loader = new QUILoader().inject(this.getElm().getParent());

            this.initScheduler(this.getElm());
        },


        /**
         * Initialize the scheduler
         *
         * @param Element - The element to create the Scheduler in
         * @return Promise - Resolves when Scheduler is initialized
         */
        initScheduler: function (Element) {
            var self = this;
            var CH = new ColorHelper();

            return new Promise(function (resolve) {
                // If scheduler already initiated return/resolve
                if (self.schedulerReady) {
                    resolve();
                }

                Element.set({
                    html: Mustache.render(displayTemplate)
                });

                var extensions = self.getAttribute('extensions').map(function (extension) {
                    return Scheduler.loadExtension(extension);
                });

                // Load scheduler extensions
                Promise.all(extensions).then(function (Scheduler) {
                    // Get last scheduler object (the one with all loaded extensions)
                    Scheduler = Scheduler[Scheduler.length - 1];

                    // Get the real Scheduler instance
                    self.Scheduler = Scheduler.getScheduler();

                    self.Scheduler.config.readonly = false;

                    // Can the current User edit the calendar?
                    // Throws error if not editable
                    Calendars.canUserEditCalendar(self.calID).catch(function () {
                        console.log('User cant edit this calendar');
                        self.Scheduler.config.readonly = true;
                    });

                    Calendars.canUserEditCalendarsEvents(self.calID).then(function (result) {
                        self.setAttribute('canUserEditEvents', result);
                    });

                    Calendars.canUserDeleteCalendarsEvents(self.calID).then(function (result) {
                        self.setAttribute('canUserDeleteEvents', result);
                    });

                    // Set date format
                    self.Scheduler.config.xml_date = "%Y-%m-%d %H:%i";

                    // Default event length 60 minutes
                    self.Scheduler.config.event_duration = 60;

                    // Always use UTC since we store unix timestamps (UTC)
                    self.Scheduler.config.server_utc = true;

                    self.Scheduler.showLightbox = function (eventId) {
                        var Event = self.Scheduler.getEvent(eventId);

                        if (Event.event_pid) {
                            Event = self.Scheduler.getEvent(Event.event_pid);
                        }

                        // Dirty-way to check if the event's recurrence has no end; Scheduler uses year 9999 for that
                        // See: http://disq.us/p/1jtwydk
                        if (Event.recurring && Event.end_date && Event.end_date.getFullYear() === 9999) {
                            Event.end_date = null;
                        }

                        var AddEventWindow = new AddEventWindowControl({event: Event});

                        AddEventWindow.addEvent('onSubmit', function () {
                            Event = AddEventWindow.getEventForSchedulerFromValues();
                            self.Scheduler.updateEvent(Event.id);

                            var values = AddEventWindow.getValues();
                            Calendars.editEvent(
                                Event.id,
                                values.text,
                                values.description,
                                values.StartDate.getTime() / 1000,
                                values.EndDate.getTime() / 1000,
                                values.url,
                                values.recurrenceInterval,
                                values.RecurrenceEndDate ? values.RecurrenceEndDate.getTime() / 1000 : null
                            );

                            AddEventWindow.close();
                        });

                        AddEventWindow.open();
                    };

                    // Remove all events from calendar (if another scheduler was opened previously)
                    self.Scheduler.clearAll();

                    // Container to display the scheduler in
                    self.Scheduler.init(Element.getElementById('calendar'));

                    self.attachEvents();

                    self.Loader.show();

                    Calendars.getCalendar(self.calID).then(function (calendarData) {
                        self.calendarData = calendarData;

                        var calendarColor = calendarData.color;
                        var textColor = CH.getSchedulerTextColor(calendarColor);

                        Calendars.getEventsForScheduler(self.calID).then(function (events) {
                            // Set events colors
                            events.forEach(function (event) {
                                event.color = calendarColor;
                                event.textColor = textColor;
                            });

                            self.parseEventsIntoScheduler(events).then(function () {
                                self.schedulerReady = true;
                                self.Loader.hide();
                            });
                        }).catch(function (error) {
                            console.error('Error getting events:', error);
                        });
                    }).catch(function (error) {
                        console.error('Error getting calendar data:', error);
                    });
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
        parseEventsIntoScheduler: function (events) {
            var self = this;
            return new Promise(function (resolve) {
                self.Scheduler.parse(events, 'json');
                resolve();
            });
        },


        /**
         * Updates the scheduler size when the window is resized
         * event : on resize
         */
        $onResize: function () {
            if (this.schedulerReady) {
                this.Scheduler.update_view();
            }
        },


        /**
         * Sets width and height of the display
         *
         * @param {Number} width
         * @param {Number} height
         */
        setDimensions: function (width, height) {
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
         * @param {Date|string} data.start_date - Start date and time of the event, if string in format '%Y-%m-%d %H:%i'
         * @param {Date|string} data.end_date   - End date and time of the event, if string in format '%Y-%m-%d %H:%i'
         */
        addEventToScheduler: function (data) {
            this.Scheduler.addEvent(data);
        },


        /**
         * Attaches add, edit, delete Event events to the Scheduler
         */
        attachEvents: function () {
            var self = this;


            this.BeforeDragEventInCalendarEvent = this.Scheduler.attachEvent('onBeforeDrag', function (eventId) {
                if (!eventId) {
                    return true;
                }

                var Event = self.Scheduler.getEvent(eventId);

                if (Event && Event.event_pid && Event.event_pid !== 0) {
                    QUI.getMessageHandler().then(function (MH) {
                        MH.addAttention(QUILocale.get(lg, 'exception.calendar.event.drag'));
                    });
                    return false;
                }

                return true;
            });


            this.BeforeChangeEventInCalendarEvent = this.Scheduler.attachEvent('onBeforeEventChanged', function (SchedulerEvent) {
                if (self.getAttribute('canUserEditEvents')) {
                    return true;
                }

                QUI.getMessageHandler().then(function (MH) {
                    MH.addError(QUILocale.get(lg, 'exception.calendar.permission.message.general', {
                        permission: QUILocale.get(lg, 'permission.quiqqer.calendar.event.edit')
                    }));
                });

                return false;
            });

            // Run when an event is edited in the scheduler
            this.ChangeEventInCalendarEvent = this.Scheduler.attachEvent('onEventChanged', function (id, ev) {
                Calendars.editEvent(ev.id, ev.text, ev.description, ev.start_date.getTime() / 1000, ev.end_date.getTime() / 1000, ev.url);
            });

            // Run when an event is added to the scheduler
            this.AddEventToCalendarEvent = this.Scheduler.attachEvent('onEventAdded', function (id, ev) {
                // The event was added, by deleting a event from a recurring-event-series
                // See: http://disq.us/p/10qarki
                if (ev.event_pid) {
                    return;
                }

                Calendars.addEvent(
                    self.calID,
                    ev.text,
                    ev.description,
                    ev.start_date.getTime() / 1000,
                    ev.end_date.getTime() / 1000,
                    ev.url
                ).then(function (result) {
                    if (result == null) {
                        return;
                    }

                    // Change color of event to calendar defined color
                    ev.color = self.calendarData.color;
                    ev.textColor = self.calendarData.textColor;
                    self.Scheduler.updateEvent(id);

                    self.Scheduler.changeEventId(id, parseInt(result));

                }).catch(function () {
                    // Remove the event from the Scheduler without triggering any callbacks.
                    // As presented by Daniel P. Henry on DHTMLX Scheduler docs (http://disq.us/p/1i73xt6)
                    var temp = self.Scheduler._events[id];
                    self.Scheduler._select_id = null;
                    delete self.Scheduler._events[id];
                    self.Scheduler.event_updated(temp);
                });
            });

            // Run before an event is deleted from the scheduler
            this.BeforeDeleteEventFromCalendarEvent = this.Scheduler.attachEvent('onBeforeEventDelete', function () {
                if (self.getAttribute('canUserDeleteEvents')) {
                    return true;
                }

                QUI.getMessageHandler().then(function (MH) {
                    MH.addError(QUILocale.get(lg, 'exception.calendar.permission.message.general', {
                        permission: QUILocale.get(lg, 'permission.quiqqer.calendar.event.delete')
                    }));
                });

                return false;
            });

            // Run when an event is deleted from scheduler
            this.DeleteEventFromCalendarEvent = this.Scheduler.attachEvent('onEventDeleted', function (id) {
                Calendars.deleteEvent(self.calID, id);
            });
        },


        detachEvents: function () {
            if (this.schedulerReady) {
                this.Scheduler.detachEvent(this.BeforeDragEventInCalendarEvent);

                this.Scheduler.detachEvent(this.AddEventToCalendarEvent);

                this.Scheduler.detachEvent(this.BeforeChangeEventInCalendarEvent);
                this.Scheduler.detachEvent(this.ChangeEventInCalendarEvent);

                this.Scheduler.detachEvent(this.BeforeDeleteEventFromCalendarEvent);
                this.Scheduler.detachEvent(this.DeleteEventFromCalendarEvent);
            }
        }
    });
});