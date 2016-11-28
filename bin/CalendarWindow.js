define('package/quiqqer/calendar/bin/CalendarWindow', [

    'qui/QUI',
    'qui/controls/windows/Confirm',
    'Ajax',
    'Locale',
    'Mustache',
    'text!package/quiqqer/calendar/bin/CalendarWindow.html',
    'css!package/quiqqer/calendar/bin/CalendarWindow.css'

], function (QUI, QUIConfirm, QUIAjax, QUILocale, Mustache, template)
{
    "use strict";

    var lg = 'quiqqer/calendar';

    return new Class({
        Extends  : QUIConfirm,
        Type     : 'package/quiqqer/calendar/bin/CalendarWindow',

        Binds: [
            '$onSubmit',
            '$onInject'
        ],

        options: {
            title      : QUILocale.get(lg, 'calendar.window.addcalendar.title'),
            icon       : 'fa fa-calendar',
            calendarID : null, // if you want to edit a calendar
            maxWidth   : 450,
            maxHeight  : 300,
            autoclose  : false
        },

        initialize: function (options)
        {
            this.parent(options);

            this.addEvents({
                onOpen: this.$onOpen,
                onSubmit: this.$onSubmit
            });
        },

        $onInject: function ()
        {
            var Content = this.getContent();

            Content.set({
                html: Mustache.render(template, {
                    test: 1
                })
            });

            if(this.getAttribute('calendarID')) {
                QUIAjax.get('package_quiqqer_calendar_ajax_getCalendar', function (result)
                {
                    Content.getElement('[name=calendarname').value = result.calendarname;
                    Content.getElement('[name=isGlobal').checked = result.isGlobal;
                }, {
                    'package'  : 'quiqqer/calendar',
                    calendarID : this.getAttribute('calendarID')
                });
            }
        },

        /**
         * event: on submit event
         */
        $onSubmit: function (values)
        {
            var Content = this.getContent();

            var calendarName = Content.getElement('[name=calendarname]').value;
            var userid = null;

            if(!Content.getElement('[name=isGlobal]').checked) {
                userid = USER.id;
            }

            this.Loader.show();

            if(this.getAttribute('calendarID')) {
                var calenderID = this.getAttribute('calendarID');
                this.editCalendar(calenderID, calendarName, userid).then(function ()
                {
                    this.close();
                }.bind(this)).catch(function ()
                {
                    this.Loader.hide();
                }.bind(this));
            } else {
                this.createCalendar(calendarName, userid).then(function ()
                {
                    this.close();
                }.bind(this)).catch(function ()
                {
                    this.Loader.hide();
                }.bind(this));
            }
        },

        /**
         * Create a new calendar
         *
         * @returns {Promise}
         */
        createCalendar: function (calendarName, userid)
        {
            return new Promise(function (resolve, reject)
            {
                QUIAjax.post('package_quiqqer_calendar_ajax_createCalendar', resolve, {
                    'package': 'quiqqer/calendar',
                    'userid' : userid,
                    'name'   : calendarName,
                    onError  : reject
                });
            });
        },


        /**
         * Edits a calendar
         *
         * @param calendarID
         * @param calendarName
         * @param userid
         */
        editCalendar: function(calendarID, calendarName, userid)
        {
            return new Promise(function (resolve, reject)
            {
                QUIAjax.post('package_quiqqer_calendar_ajax_editCalendar', resolve, {
                    'package'    : 'quiqqer/calendar',
                    'calendarID' : calendarID,
                    'userid'     : userid,
                    'name'       : calendarName,
                    onError      : reject
                });
            });
        }
    });
});