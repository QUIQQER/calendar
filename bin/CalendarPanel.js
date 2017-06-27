/**
 * Panel that displays a calendar/scheduler
 *
 * @module 'package/quiqqer/calendar/bin/CalendarPanel'
 * @author www.pcsg.de (Jan Wennrich)
 *
 * @require 'qui/QUI'
 * @require 'qui/controls/windows/Confirm'
 * @require 'qui/controls/desktop/Panel',
 * @require 'qui/controls/buttons/Separator',
 * @require 'qui/utils/Functions',
 * @require 'package/quiqqer/calendar/bin/Calendars',
 * @require 'package/quiqqer/calendar/bin/controls/CalendarDisplay',
 * @require 'package/quiqqer/calendar/bin/controls/CalendarEditDisplay',
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
    'qui/controls/buttons/Separator',
    'qui/utils/Functions',
    'package/quiqqer/calendar/bin/Calendars',
    'package/quiqqer/calendar/bin/controls/CalendarDisplay',
    'package/quiqqer/calendar/bin/controls/CalendarEditDisplay',
    'package/quiqqer/calendar-controls/bin/Scheduler',
    'Ajax',
    'Locale',
    'Mustache',

    'text!package/quiqqer/calendar/bin/CalendarPanel.html'

], function (QUI, QUIConfirm, QUIPanel, QUIButtonSeparator, QUIFunctionUtils, Calendars, CalendarDisplay, CalendarEditDisplay, Scheduler, QUIAjax, QUILocale, Mustache, template)
{
    "use strict";

    var lg = 'quiqqer/calendar';

    return new Class({
        Extends: QUIPanel,
        Type   : 'package/quiqqer/calendar/bin/CalendarPanel',

        calendarData: null,

        schedulerReady: false,

        Scheduler: null,

        Binds: [
            '$onCreate',
            '$onResize',
            'editCalendarClick',
            'deleteCalendarClick',
            'addEventClick',
            'serialize',
            'unserialize',
            'exportIcalClick'
        ],

        /**
         * Constructor of the class
         *
         * @param options - constructor options
         */
        initialize: function (options)
        {
            this.parent(options);

            if (this.calendarData === null) {
                this.calendarData = options.calendarData;
            }

            this.addEvents({
                onCreate: this.$onCreate,
                onResize: this.$onResize
            });
        },


        /**
         * Event: Fired when Panel gets resized
         */
        $onResize: function ()
        {
            if (!this.Scheduler) {
                return;
            }

            var Content = this.getContent();

            if (!Content) {
                return;
            }

            var size = Content.getSize();

            this.Scheduler.setDimensions(size.x - 40, size.y - 40);

            this.Scheduler.$onResize();
        },


        /**
         * Event: fired when the window is added to DOM
         */
        $onCreate: function ()
        {
            var self = this;

            if (!self.calendarData.isExternal) {
                this.addButton({
                    name     : 'addEvent',
                    text     : QUILocale.get(lg, 'panel.button.add.event.text'),
                    textimage: 'fa fa-plus',
                    events   : {
                        onClick: this.addEventClick
                    }
                });

                this.addButton(new QUIButtonSeparator());
            }

            this.addButton({
                name     : 'editCalendar',
                text     : QUILocale.get(lg, 'panel.button.edit.calendar.text'),
                textimage: 'fa fa-pencil',
                events   : {
                    onClick: this.editCalendarClick
                }
            });

            this.addButton(new QUIButtonSeparator());

            this.addButton({
                name     : 'exportIcal',
                text     : QUILocale.get(lg, 'panel.button.ical.export.text'),
                textimage: 'fa fa-download',
                events   : {
                    onClick: this.exportIcalClick
                }
            });

            this.addButton({
                name  : 'deleteCalendar',
                icon  : 'fa fa-trash',
                events: {
                    onClick: this.deleteCalendarClick
                },
                styles: {
                    float: 'right'
                }
            });

            var Content = this.getContent();

            if(self.calendarData.isExternal) {
                this.Scheduler = new CalendarDisplay([self.calendarData.id]);
            } else {
                this.Scheduler = new CalendarEditDisplay(self.calendarData.id);
            }
            this.Scheduler.inject(Content);
        },


        /**
         * Removes all Events (add,change,delete) from Scheduler
         *
         * Event : fired when panel is closed/destroyed
         */
        $onDestroy: function ()
        {
            // Does nothing
        },


        /**
         * Called from Panel Handler.
         * Stores the data of the current panel in the workspace so the panel can be displayed after page reload.
         * @return {{type, attributes, calendarData: *}}
         */
        serialize: function ()
        {
            return {
                type        : this.getType(),
                attributes  : this.getAttributes(),
                calendarData: this.calendarData
            };
        },


        /**
         * Import the saved data from the workspace to display the panel after page reload
         *
         * @param {Object} data
         * @return {Object} this (package/quiqqer/calendar/bin/CalendarPanel)
         */
        unserialize: function (data)
        {
            this.setAttributes(data.attributes);
            this.calendarData = data.calendarData;
            return this;
        },


        /**
         * Event: fired when edit calendar button is clicked
         * Opens the edit calendar window
         */
        editCalendarClick: function ()
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
        addEventClick: function ()
        {
            var self = this;
            require(['package/quiqqer/calendar/bin/AddEventWindow'], function (AddEventWindow)
            {
                var aeWindow = new AddEventWindow();
                aeWindow.addEvent('onSubmit', function (Window)
                {
                    var Content = Window.getContent();
                    var title = Content.getElement('[name=eventtitle]').value;
                    var desc = Content.getElement('[name=eventdesc]').value;
                    var start = Content.getElement('[name=eventstart]').value;
                    var end = Content.getElement('[name=eventend]').value;
                    this.Loader.show();
                    self.Scheduler.addEventToScheduler({
                        start_date: start,
                        end_date  : end,
                        text      : title
                    });
                    self.Loader.hide();
                    aeWindow.close();
                });
                aeWindow.open();
            });
        },


        /**
         * Downloads the calendar as an iCal file
         */
        exportIcalClick: function ()
        {
            var downloadFile = URL_OPT_DIR + 'quiqqer/calendar/bin/iCalExport.php?calendar=' + this.calendarData.id,
                iframeId     = Math.floor(Date.now() / 1000),
                Frame        = new Element('iframe', {
                    id             : 'download-iframe-' + iframeId,
                    src            : downloadFile,
                    styles         : {
                        left    : -1000,
                        height  : 10,
                        position: 'absolute',
                        top     : -1000,
                        width   : 10
                    },
                    'data-iframeid': iframeId
                }).inject(document.body);
        },


        /**
         * Opens the dialog to remove a calendar
         */
        deleteCalendarClick: function ()
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
                        Win.Loader.show();
                        Calendars.deleteCalendars(ids).then(function ()
                        {
                            Win.close();
                            self.destroy();
                        });
                    }
                }
            }).open();
        }

    });
});