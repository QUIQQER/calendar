require([
    'Menu',
    'qui/controls/contextmenu/Item',
    'package/quiqqer/calendar/bin/Calendars'
], function (Menu, QUIMenuItem, Calendars)
{
    QUI.addEvent('onQuiqqerLoaded', function ()
    {
        var MenuBar           = Menu.getChildren(),
            Extras            = MenuBar.getChildren('extras'),
            CalendarsMenuItem = Extras.getChildren('calendar');

        Extras.appendChild(CalendarsMenuItem);

        Calendars.getAsArray().then(function (CalendarData)
        {
            var click = function ()
            {
                Calendars.openCalendar(this);
            };

            for (var i = 0, len = CalendarData.length; i < len; i++) {
                CalendarsMenuItem.appendChild(
                    new QUIMenuItem({
                        text    : CalendarData[i].name,
                        icon    : 'icon-calendar',
                        events  : {
                            onClick: click.bind(CalendarData[i])
                        }
                    })
                );
            }
        });
    });
});