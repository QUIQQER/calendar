define('package/quiqqer/calendar/bin/AddEventWindow', [

    'qui/QUI',
    'qui/controls/windows/Confirm',
    'Ajax',
    'Locale',
    'Mustache',
    'text!package/quiqqer/calendar/bin/AddEventWindow.html',
    'css!package/quiqqer/calendar/bin/AddEventWindow.css'

], function (QUI, QUIConfirm, QUIAjax, QUILocale, Mustache, template)
{
    "use strict";

    var lg = 'quiqqer/calendar';

    return new Class({
        Extends: QUIConfirm,
        Type   : 'package/quiqqer/calendar/bin/AddEventWindow',

        Binds: [],

        options: {
            title    : QUILocale.get(lg, 'calendar.window.addevent.title'),
            icon     : 'fa fa-calendar',
            maxWidth : 600,
            maxHeight: 400,
            autoclose: false
        },

        initialize: function (options)
        {
            this.parent(options);

            this.addEvents({
                onOpen: this.$onOpen
            });
        },

        $onOpen: function ()
        {
            this.getContent().set({
                html: Mustache.render(template, {
                    title: QUILocale.get(lg, 'calendar.window.addevent.event.title'),
                    desc : QUILocale.get(lg, 'calendar.window.addevent.event.desc'),
                    start: QUILocale.get(lg, 'calendar.window.addevent.event.start'),
                    end  : QUILocale.get(lg, 'calendar.window.addevent.event.end'),
                    tip  : QUILocale.get(lg, 'calendar.window.addevent.tip')
                })
            });
        }
    });
});