/**
 * @module package/quiqqer/calendar/bin/Panel
 */
define('package/quiqqer/calendar/bin/Panel', [

    'qui/QUI',
    'qui/controls/desktop/Panel',
    'Ajax',
    'Locale'

], function (QUI, QUIPanel, QUIAjax, QUILocale)
{
    "use strict";

    var lg = 'quiqqer/calendar';

    return new Class({
        Extends: QUIPanel,
        Type   : 'package/quiqqer/calendar/bin/Panel',

        Binds: [
            '$onCreate',
            '$onInject',
            '$onButtonAddEventClick'
        ],

        initialize: function (options)
        {
            this.parent(options);

            this.setAttributes({
                'icon': 'fa fa-calendar'
            });

            this.addEvents({
                onCreate: this.$onCreate,
                onInject: this.$onInject
            });
        },

        /**
         * event : on create
         */
        $onCreate: function ()
        {
            this.addButton({
                text     : QUILocale.get(lg, 'panel.button.add.text'),
                textimage: 'fa fa-plus',
                events   : {
                    onClick: this.$onButtonAddEventClick
                }
            });
        },

        /**
         *
         */
        $onInject: function ()
        {
            QUIAjax.get('package_quiqqer_calendar_ajax_getList', function (result)
            {
                console.info(result);
            }, {
                'package': 'quiqqer/calendar'
            });
        },

        $onButtonAddEventClick: function ()
        {
            
        }
    });
});