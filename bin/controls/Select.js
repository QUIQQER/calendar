/**
 * Select for calendars
 *
 * @module package/quiqqer/calendar/bin/controls/Select
 * @author www.pcsg.de (Jan Wennrich)
 *
 * @require qui/QUI
 * @require qui/controls/elements/Select
 * @require Ajax
 * @require Locale
 */
define('package/quiqqer/calendar/bin/controls/Select', [

    'qui/QUI',
    'qui/controls/elements/Select',

    'Ajax',
    'Locale'


], function (QUI, QUIElementSelect, QUIAjax, QUILocale)
{
    "use strict";

    return new Class({

        Extends: QUIElementSelect,
        Type   : 'package/quiqqer/calendar/bin/controls/Select',

        Binds: [
            'calendarSearch',
            '$onSearchButtonClick',
            '$onImport'
        ],


        initialize: function (options)
        {
            this.parent(options);

            this.setAttribute('Search', this.calendarSearch);
            this.setAttribute('icon', 'fa fa-calendar');
            this.setAttribute('child', 'package/quiqqer/calendar/bin/controls/SelectItem');
            this.setAttribute('searchbutton', true);

            this.setAttribute(
                'placeholder',
                QUILocale.get('quiqqer/calendar', 'calendar.control.select.placeholder')
            );

            this.isEditCalendar = false;

            this.addEvents({
                onSearchButtonClick: this.$onSearchButtonClick
            });
        },


        $onImport: function (options)
        {
            this.parent(options);

            var multiple = true,
                max      = false;

            if (this.getAttribute('is-edit-calendar') === 'true') {
                this.isEditCalendar = true;
                multiple = false;
                max = 1;
            }

            this.setAttribute('multiple', multiple);
            this.setAttribute('max', max);
        },


        /**
         * Search calendars
         *
         * @param {String} value
         * @returns {Promise}
         */
        calendarSearch: function (value)
        {
            return new Promise(function (resolve, reject)
            {
                QUIAjax.get('package_quiqqer_calendar_ajax_search', resolve, {
                    'package': 'quiqqer/calendar',
                    onError  : reject,
                    freeText : value
                });
            });

        },

        /**
         * event : on search button click
         *
         * @param {Object} self - select object
         * @param {Object} Btn - button object
         */
        $onSearchButtonClick: function (self, Btn)
        {
            Btn.setAttribute('icon', 'fa fa-spinner fa-spin');

            require([
                'package/quiqqer/calendar/bin/controls/search/Window'
            ], function (Window)
            {
                new Window({
                    autoclose: true,
                    multiple : !this.isEditCalendar,
                    events   : {
                        onSubmit: function (Win, data)
                        {
                            var self = this;
                            data.forEach(function (calendar)
                            {
                                self.addItem(calendar.id);
                            });
                        }.bind(this)
                    }
                }).open();

                Btn.setAttribute('icon', 'fa fa-search');
            }.bind(this));
        }
    });
});