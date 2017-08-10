/**
 * Select for events
 *
 * @module package/quiqqer/calendar/bin/controls/eventSelect/EventSelect
 * @author www.pcsg.de (Jan Wennrich)
 *
 * @require qui/QUI
 * @require qui/controls/elements/Select
 * @require Ajax
 * @require Locale
 */
define('package/quiqqer/calendar/bin/controls/eventSelect/EventSelect', [

    'qui/QUI',
    'qui/controls/elements/Select',

    'Ajax',
    'Locale'


], function (QUI, QUIElementSelect, QUIAjax, QUILocale) {
    "use strict";

    return new Class({

        Extends: QUIElementSelect,
        Type   : 'package/quiqqer/calendar/bin/controls/eventSelect/EventSelect',

        Binds: [
            'eventSearch',
            '$onSearchButtonClick',
            '$onImport'
        ],


        initialize: function (options) {
            this.parent(options);

            this.setAttribute('Search', this.eventSearch);
            this.setAttribute('icon', 'fa fa-clock-o');
            this.setAttribute('child', 'package/quiqqer/calendar/bin/controls/eventSelect/EventSelectItem');
            this.setAttribute('searchbutton', true);

            this.setAttribute(
                'placeholder', QUILocale.get('quiqqer/calendar', 'calendar.control.eventSelect.placeholder')
            );

            this.addEvents({
                onSearchButtonClick: this.$onSearchButtonClick
            });
        },


        $onImport: function (options) {
            this.parent(options);

            this.setAttribute('multiple', false);
            this.setAttribute('max', 1);
        },


        /**
         * Search calendars
         *
         * @param {String} value
         * @returns {Promise}
         */
        eventSearch: function (value) {
            return new Promise(function (resolve, reject) {
                QUIAjax.get('package_quiqqer_calendar_ajax_eventSearch', function(result) {
                    resolve(result);
                }, {
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
        $onSearchButtonClick: function (self, Btn) {
            Btn.setAttribute('icon', 'fa fa-spinner fa-spin');

            require([
                'package/quiqqer/calendar/bin/controls/search/event/Window'
            ], function (Window) {
                new Window({
                    autoclose: true,
                    multiple : false,
                    events   : {
                        onSubmit: function (Win, data) {
                            var self = this;
                            data.forEach(function (event) {
                                self.addItem(event.id);
                            });
                        }.bind(this)
                    }
                }).open();

                Btn.setAttribute('icon', 'fa fa-search');
            }.bind(this));
        }
    });
});