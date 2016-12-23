/**
 * Panel that displays a calendar/scheduler
 *
 * @module 'package/quiqqer/calendar/bin/CalendarPanel'
 * @author www.pcsg.de (Jan Wennrich)
 *
 * @require 'qui/QUI'
 * @require 'qui/controls/windows/Confirm'
 * @require 'qui/controls/desktop/Panel',
 * @require 'qui/controls/buttons/Seperator',
 * @require 'qui/utils/Functions',
 * @require 'package/quiqqer/calendar/bin/Calendars',
 * @require 'package/quiqqer/calendar-controls/bin/Scheduler',
 * @require 'Ajax',
 * @require 'Locale'
 * @require 'Mustache'
 *
 * @require 'text!package/quiqqer/calendar/bin/CalendarPanel.html'
 */
define('package/quiqqer/calendar/bin/CalendarPanel', [

    'qui/QUI',
    'qui/controls/windows/Confirm',
    'qui/controls/desktop/Panel',
    'qui/controls/buttons/Seperator',
    'qui/utils/Functions',
    'package/quiqqer/calendar/bin/Calendars',
    'package/quiqqer/calendar-controls/bin/Scheduler',
    'Ajax',
    'Locale',
    'Mustache',

    'text!package/quiqqer/calendar/bin/CalendarPanel.html'

], function (QUI, QUIConfirm, QUIPanel, QUIButtonSeperator, QUIFunctionUtils, Calendars, Scheduler, QUIAjax, QUILocale, Mustache, template)
{
    "use strict";

    var lg = 'quiqqer/calendar';

    return new Class({
        Extends: QUIPanel,
        Type   : 'package/quiqqer/calendar/bin/CalendarPanel',

        calendarData: null,

        schedulerReady: false,

        Scheduler: null,

        ChangeEvent: null,
        AddEvent   : null,
        DeleteEvent: null,

        Binds: [
            '$onCreate',
            '$onResize',
            '$onInject',
            '$onClose',
            '$onButtonEditCalendarClick',
            '$onButtonDeleteCalendarClick'
        ],

        /**
         * Constructor of the class
         *
         * @param options - constructor options
         */
        initialize: function (options)
        {
            this.parent(options);
            this.calendarData = options.calendarData;

            this.addEvents({
                onCreate: this.$onCreate,
                onResize: this.$onResize,
                onInject: this.$onInject
            });
        },


        initScheduler: function ()
        {
            var self = this;

            return new Promise(function (resolve)
            {
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

                    // Default event length 60 minutes
                    self.Scheduler.config.event_duration = 60;

                    // Remove all events from calendar (if another scheduler was opened previously)
                    self.Scheduler.clearAll();

                    // Container to display the scheduler in
                    self.Scheduler.init(self.getContent().getElement('.dhx_cal_container'));

                    // Parses the calendar iCal string into the scheduler
                    Calendars.getCalendarAsIcal(self.calendarData.id).then(function (result)
                    {
                        self.Scheduler.parse(result, 'ical');
                    });

                    // Run when an event is edited in the scheduler
                    self.ChangeEvent = self.Scheduler.attachEvent('onEventChanged', function (id, ev)
                    {
                        Calendars.editEvent(
                            self.calendarData.id,
                            ev.id,
                            ev.text,
                            ev.description,
                            ev.start_date.getTime() / 1000,
                            ev.end_date.getTime() / 1000
                        )
                    });

                    // Run when an event is added to the scheduler
                    self.AddEvent = self.Scheduler.attachEvent('onEventAdded', function (id, ev)
                    {
                        Calendars.addEvent(
                            self.calendarData.id,
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
                    self.DeleteEvent = self.Scheduler.attachEvent('onEventDeleted', function (id)
                    {
                        Calendars.deleteEvent(self.calendarData.id, id);
                    });

                    self.schedulerReady = true;
                    resolve();
                });
            });

        },


        isSchedulerReady: function ()
        {
            var self = this;
            return new Promise(function (resolve)
            {
                if (self.schedulerReady) {
                    resolve();
                } else {
                    self.initScheduler().then(function ()
                    {
                        resolve();
                    });
                }
            });
        },

        /**
         * Event: fired when the window is added to DOM
         */
        $onCreate: function ()
        {
            var self = this;

            this.addButton({
                name     : 'addEvent',
                text     : QUILocale.get(lg, 'panel.button.add.event.text'),
                textimage: 'fa fa-plus',
                events   : {
                    onClick: this.$onButtonAddEventClick
                }
            });

            this.addButton(new QUIButtonSeperator());

            this.addButton({
                name     : 'editCalendar',
                text     : QUILocale.get(lg, 'panel.button.edit.calendar.text'),
                textimage: 'fa fa-pencil',
                events   : {
                    onClick: this.$onButtonEditCalendarClick
                }
            });

            this.addButton({
                name  : 'deleteCalendar',
                icon  : 'fa fa-trash',
                events: {
                    onClick: this.$onButtonDeleteCalendarClick
                }
            });

            var Content = this.getContent();

            Content.set({
                html: Mustache.render(template)
            });

            self.initScheduler();
        },

        /**
         * Updates the scheduler size when the window is resized
         * event : on resize
         */
        $onResize: function ()
        {
            this.updateSchedularView()
        },


        /**
         * Removes all Events (add,change,delete) from Scheduler
         *
         * event : fired when panel is closed/destroyed
         */
        $onDestroy: function ()
        {
            if (this.schedulerReady) {
                this.Scheduler.detachEvent(this.AddEvent);
                this.Scheduler.detachEvent(this.ChangeEvent);
                this.Scheduler.detachEvent(this.DeleteEvent);
            }
        },

        /**
         * event: fired when panel is added to DOM
         */
        $onInject: function ()
        {
            this.updateSchedularView()
        },

        /**
         * Updates events in the scheduler
         */
        updateSchedularView: function ()
        {
            if (this.schedulerReady) {
                this.Scheduler.update_view();
            }
        },

        /**
         * Event: fired when edit calendar button is clicked
         * Opens the edit calendar window
         */
        $onButtonEditCalendarClick: function ()
        {
            var self = this;
            require(['package/quiqqer/calendar/bin/AddEditCalendarWindow'], function (CalendarWindow)
            {
                new CalendarWindow({
                    calendar: self.calendarData,
                    title   : QUILocale.get(lg, 'calendar.window.edit.calendar.title'),
                    events  : {
                        onClose: function ()
                        {
                            // TODO: Refresh panel title
                        }
                    }
                }).open();
            });
        },


        /**
         * Event: Fired when the add event button is clicked
         * Opens the dialog to add an event
         */
        $onButtonAddEventClick: function ()
        {
            require(['package/quiqqer/calendar/bin/AddEventWindow'], function (AddEventWindow)
            {
                var self = this;
                var aeWindow = new AddEventWindow();
                aeWindow.addEvent('onSubmit', function (Window)
                {
                    var Content = Window.getContent();

                    var title = Content.getElement('[name=eventtitle]').value;
                    var desc = Content.getElement('[name=eventdesc]').value;
                    var start = Content.getElement('[name=eventstart]').value;
                    var end = Content.getElement('[name=eventend]').value;

                    this.Loader.show();

                    this.isSchedulerReady().then(function ()
                    {
                        self.Scheduler.addEvent({
                            start_date: start,
                            end_date  : end,
                            text      : title
                        });

                        self.Loader.hide();
                        aeWindow.close();
                    });

                });
                aeWindow.open();
            });
        },


        /**
         * Opens the dialog to remove a calendar
         */
        $onButtonDeleteCalendarClick: function ()
        {
            var self = this;
            var ids = [self.calendarData.id];
            new QUIConfirm({
                icon       : 'fa fa-remove',
                title      : QUILocale.get(lg, 'calendar.window.delete.calendar.title'),
                text       : QUILocale.get(lg, 'calendar.window.delete.calendar.text'),
                information: QUILocale.get(lg, 'calendar.window.delete.calendar.information', {
                    ids: ids
                }),
                events     : {
                    onSubmit: function (Win)
                    {
                        console.log(ids);
                        Win.Loader.show();
                        Calendars.deleteCalendars(ids).then(function ()
                        {
                            Win.close();
                            self.destroy();
                        });
                    }
                }
            }).open();
        },


        /**
         * Shows the calendar events as a list
         */
        $onButtonShowAsListClick: function (Panel)
        {
            require(['controls/grid/Grid'], function (Grid)
            {
                console.log('Show as list click');

                var Content = Panel.getContent();

                Content.getElement('.dhx_cal_container').setStyle('display', 'none');

                var Container = new Element('div', {
                    'class': 'box',
                    styles : {
                        width : '100%',
                        height: '100%'
                    }
                }).inject(Content);

                // creates grid
                Panel.$Grid = new Grid(Container, {
                    columnModel      : [{
                        header   : QUILocale.get('quiqqer/system', 'id'),
                        dataIndex: 'id',
                        dataType : 'string',
                        width    : 50
                    }, {
                        header   : QUILocale.get(lg, 'calendar.window.addevent.event.title'),
                        dataIndex: 'title',
                        dataType : 'string',
                        width    : 150
                    }, {
                        header   : QUILocale.get(lg, 'calendar.window.addevent.event.start'),
                        dataIndex: 'start',
                        dataType : 'string',
                        width    : 75
                    }, {
                        header   : QUILocale.get(lg, 'calendar.window.addevent.event.end'),
                        dataIndex: 'end',
                        dataType : 'string',
                        width    : 50
                    }],
                    multipleSelection: true,
                    pagination       : true
                });
            });
        }
    });
});