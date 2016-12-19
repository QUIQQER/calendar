/**
 * Main panel that lists all calendars in a grid
 *
 * @module package/quiqqer/calendar/bin/Panel
 * @author www.pcsg.de (Jan Wennrich)
 *
 * @require 'qui/QUI'
 * @require 'qui/controls/buttons/Seperator',
 * @require 'qui/controls/desktop/Panel'
 * @require 'qui/controls/windows/Confirm'
 * @require 'Locale'
 * @require 'controls/grid/Grid'
 */
define('package/quiqqer/calendar/bin/Panel', [

    'qui/QUI',
    'qui/controls/desktop/Panel',
    'qui/controls/windows/Confirm',
    'qui/controls/buttons/Seperator',
    'package/quiqqer/calendar/bin/Calendars',
    'Ajax',
    'Locale',
    'controls/grid/Grid'

], function (QUI, QUIPanel, QUIConfirm, QUIButtonSeperator, Calendars, QUIAjax, QUILocale, Grid)
{
    "use strict";

    var lg = 'quiqqer/calendar';

    return new Class({
        Extends: QUIPanel,
        Type   : 'package/quiqqer/calendar/bin/Panel',

        Binds: [
            '$onCreate',
            '$onResize',
            '$onButtonAddEventClick',
            '$onButtonEditCalendarClick',
            '$onButtonAddCalendarClick',
            'deleteMarkedCalendars',
            'editCalendar'
        ],

        options: {
            title: QUILocale.get(lg, 'panel.title'),
            icon : 'fa fa-calendar'
        },

        initialize: function (options)
        {
            this.parent(options);

            this.setAttributes({
                'icon': 'fa fa-calendar'
            });

            this.addEvents({
                onCreate: this.$onCreate,
                onResize: this.$onResize
            });
        },

        /**
         * event : on create
         */
        $onCreate: function ()
        {
            var self = this;

            this.addButton({
                name     : 'addCalendar',
                text     : QUILocale.get(lg, 'panel.button.add.calendar.text'),
                textimage: 'fa fa-plus',
                events   : {
                    onClick: this.$onButtonAddCalendarClick
                }
            });

            this.addButton(new QUIButtonSeperator());

            // Button to delete selected calendars. Activated if one calendar is selected in grid
            this.addButton({
                name     : 'editCalendar',
                text     : QUILocale.get(lg, 'panel.button.edit.marked_calendars.text'),
                textimage: 'fa fa-pencil',
                events   : {
                    onClick: this.$onButtonEditCalendarClick
                }
            });
            this.getButtons('editCalendar').disable();

            // Button to delete selected calendars. Activated if more than one calendar is selected in grid
            this.addButton({
                name     : 'deleteCalendar',
                text     : QUILocale.get(lg, 'panel.button.delete.marked_calendars.text'),
                textimage: 'fa fa-trash',
                events   : {
                    onClick: this.deleteMarkedCalendars
                }
            });
            this.getButtons('deleteCalendar').disable();

            var Content   = this.getContent(),

                Container = new Element('div', {
                    'class': 'box',
                    styles : {
                        width : '100%',
                        height: '100%'
                    }
                }).inject(Content);

            // creates grid
            this.$Grid = new Grid(Container, {
                columnModel      : [{
                    header   : QUILocale.get('quiqqer/system', 'id'),
                    dataIndex: 'id',
                    dataType : 'string',
                    width    : 50
                }, {
                    header   : QUILocale.get(lg, 'calendar.title'),
                    dataIndex: 'name',
                    dataType : 'string',
                    width    : 150
                }, {
                    header   : QUILocale.get(lg, 'calendar.userid'),
                    dataIndex: 'userid',
                    dataType : 'string',
                    width    : 75
                }, {
                    header   : QUILocale.get(lg, 'calendar.isglobal'),
                    dataIndex: 'isglobal',
                    dataType : 'boolean',
                    width    : 50
                }],
                multipleSelection: true,
                pagination       : true
            });

            this.$Grid.addEvents({
                onRefresh: function ()
                {
                    self.loadCalendars();
                },

                // On double click opens the calendar
                onDblClick: function (data)
                {
                    var rowData = self.$Grid.getDataByRow(data.row);
                    Calendars.openCalendar(rowData);
                },

                // On single click select calendar and (de-)activate buttons
                onClick: function (data)
                {
                    var delButton  = self.getButtons('deleteCalendar'),
                        editButton = self.getButtons('editCalendar'),
                        selected   = self.$Grid.getSelectedIndices().length;

                    if (selected == 1) {
                        editButton.enable();
                    } else {
                        editButton.disable();
                    }

                    if (selected) {
                        delButton.enable();
                    } else {
                        delButton.disable();
                    }
                }
            });

            this.loadCalendars();
        },

        /**
         * Load the calendars into the grid.
         */
        loadCalendars: function ()
        {
            var self = this;

            QUIAjax.get('package_quiqqer_calendar_ajax_getCalendars', function (result)
            {
                if (!self.$Grid) {
                    return;
                }

                self.$Grid.setData({
                    data: result
                });
            }, {
                'package': 'quiqqer/calendar'
            });
        },

        /**
         * event : on resize
         */
        $onResize: function ()
        {
            if (!this.$Grid) {
                return;
            }

            var Content = this.getContent(),
                size    = Content.getSize();

            this.$Grid.setHeight(size.y - 40);
            this.$Grid.setWidth(size.x - 40);
        },

        /**
         * Creates a new calendar.
         */
        $onButtonAddCalendarClick: function ()
        {
            var self = this;
            require(['package/quiqqer/calendar/bin/AddEditCalendarWindow'], function (CalendarWindow)
            {
                new CalendarWindow({
                    title : QUILocale.get(lg, 'calendar.window.add.calendar.title'),
                    events: {
                        onClose: function ()
                        {
                            self.loadCalendars();
                        }
                    }
                }).open();
            });
        },


        /**
         * Edits the calendar with the given ID
         *
         * @param calendar - The calendar to edit
         */
        editCalendar: function (calendar)
        {
            var self = this;
            require(['package/quiqqer/calendar/bin/AddEditCalendarWindow'], function (CalendarWindow)
            {
                new CalendarWindow({
                    calendar: calendar,
                    title   : QUILocale.get(lg, 'calendar.window.edit.calendar.title'),
                    events  : {
                        onClose: function ()
                        {
                            self.loadCalendars();
                        }
                    }
                }).open();
            });
            return this;
        },


        /**
         * event: fired when edit calendar button is clicked.
         */
        $onButtonEditCalendarClick: function ()
        {
            if (!this.$Grid) {
                return;
            }

            var data = this.$Grid.getSelectedData();

            if (!data.length) {
                return;
            }

            this.editCalendar(data[0]);
        },

        /**
         * Open the delete marked calendars window and delete all marked calendars
         */
        deleteMarkedCalendars: function ()
        {
            if (!this.$Grid) {
                return;
            }

            var self = this,
                data = this.$Grid.getSelectedData();

            if (!data.length) {
                return;
            }

            var ids = data.map(function (o)
            {
                return o.id;
            });

            new QUIConfirm({
                icon       : 'fa fa-remove',
                title      : QUILocale.get(lg, 'calendar.window.delete.calendar.title'),
                text       : QUILocale.get(lg, 'calendar.window.delete.calendar.text'),
                information: QUILocale.get(lg, 'calendar.window.delete.calendar.information', {
                    ids: ids.join(', ')
                }),
                events     : {
                    onSubmit: function (Win)
                    {
                        Win.Loader.show();
                        Calendars.deleteCalendars(ids).then(function ()
                        {
                            Win.close();
                            self.loadCalendars();
                        });
                    }
                }
            }).open();
        }
    });
});