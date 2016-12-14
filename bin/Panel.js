/**
 * @module package/quiqqer/calendar/bin/Panel
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
            '$onInject',
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
                onInject: this.$onInject,
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

            this.addButton({
                name     : 'editCalendar',
                text     : QUILocale.get(lg, 'panel.button.edit.marked_calendars.text'),
                textimage: 'fa fa-pencil',
                events   : {
                    onClick: this.$onButtonEditCalendarClick
                }
            });
            this.getButtons('editCalendar').disable();

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
                onRefresh: function () {
                    self.loadCalendars();
                },

                onDblClick: function(data) {
                    var rowData = self.$Grid.getDataByRow(data.row);
                    self.openCalendar(rowData);
                },

                onClick: function (data) {
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
        loadCalendars: function () {
            var self = this;

            QUIAjax.get('package_quiqqer_calendar_ajax_getCalendars', function (result) {
                if (!self.$Grid) {
                    return;
                }

                self.$Grid.setData({
                    data: result
                });
            }, {
                'package': 'quiqqer/calendar'
            });
            return this;
        },

        /**
         * Run when the Panel is inserted into the page.
         */
        $onInject: function () {},

        /**
         * event : on resize
         */
        $onResize: function () {
            if (!this.$Grid) {
                return;
            }

            var Content = this.getContent(),
                size    = Content.getSize();

            this.$Grid.setHeight(size.y - 40);
            this.$Grid.setWidth(size.x - 40);
        },

        /**
         * Adds a new event to a calendar
         */
        $onButtonAddEventClick: function ()
        {
            require(['package/quiqqer/calendar/bin/AddEventWindow'], function (AddEventWindow) {
                var aeWindow = new AddEventWindow();
                aeWindow.open();
            });
        },

        /**
         * Adds a new calendar.
         */
        $onButtonAddCalendarClick: function ()
        {
            var self = this;
            require(['package/quiqqer/calendar/bin/AddEditCalendarWindow'], function (CalendarWindow) {
                new CalendarWindow({
                    title: QUILocale.get(lg, 'calendar.window.add.calendar.title'),
                    events: {
                        onClose: function () {
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
        editCalendar: function(calendar)
        {
            var self = this;
            require(['package/quiqqer/calendar/bin/AddEditCalendarWindow'], function (CalendarWindow) {
                new CalendarWindow({
                    calendar: calendar,
                    title: QUILocale.get(lg, 'calendar.window.edit.calendar.title'),
                    events: {
                        onClose: function () {
                            self.loadCalendars();
                        }
                    }
                }).open();
            });
            return this;
        },


        $onButtonEditCalendarClick: function()
        {
            if (!this.$Grid) {
                return this;
            }

            var data = this.$Grid.getSelectedData();

            if (!data.length) {
                return this;
            }

            this.editCalendar(data[0]);
        },

        /**
         * Open the delete marked calendars window and delete all marked calendars
         *
         * @return {self}
         */
        deleteMarkedCalendars: function ()
        {
            if (!this.$Grid) {
                return this;
            }

            var self = this,
                data = this.$Grid.getSelectedData();

            if (!data.length) {
                return this;
            }

            var ids = data.map(function (o) {
                return o.id;
            });

            console.log(ids);

            new QUIConfirm({
                icon       : 'fa fa-remove',
                title      : QUILocale.get(lg, 'calendar.window.delete.calendar.title'),
                text       : QUILocale.get(lg, 'calendar.window.delete.calendar.text'),
                information: QUILocale.get(lg, 'calendar.window.delete.calendar.information', {
                    ids: ids.join(', ')
                }),
                events     : {
                    onSubmit: function (Win) {
                        Win.Loader.show();
                        Calendars.deleteCalendars(ids).then(function()
                        {
                            Win.close();
                            self.loadCalendars();
                        });
                    }
                }
            }).open();

            return this;
        },


        /**
         * Opens a calendar in a new panel
         *
         * @param calendar The calendar to open
         */
        openCalendar: function(calendar) {
            var self = this;

            require([
                'package/quiqqer/calendar/bin/CalendarPanel',
                'utils/Panels'
            ], function (CalendarPanel, Utils) {
                var panels = QUI.Controls.getByType('package/quiqqer/calendar/bin/CalendarPanel');
                if( panels[0] !== undefined) {
                    panels[0].destroy();
                }

                Utils.openPanelInTasks( new CalendarPanel({
                    title: calendar.name,
                    calendarData: calendar,
                    icon : 'fa fa-calendar',
                    events     : {
                        onDestroy: function ()
                        {
                            self.loadCalendars();
                        }
                    }
                }) );
            });

            return this;
        }
    });
});