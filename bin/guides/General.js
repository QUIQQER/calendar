define('package/quiqqer/bricks/bin/guides/General', [

    'package/quiqqer/tour/bin/classes/Tour',
    'utils/Panels',
    'Locale',
    'Projects'

], function (Tour, PanelUtils, QUILocale, Projects) {
    "use strict";

    var lg = 'quiqqer/calendar';

    var Calendar     = new Tour();

    Calendar.addStep({
        title  : QUILocale.get(lg, 'general.title.1'),
        text   : QUILocale.get(lg, 'general.text.1'),
        buttons: [{
            text  : QUILocale.get(lg, 'button.cancel'),
            action: function () {
                Calendar.cancel();
            }
        }, {
            text  : QUILocale.get(lg, 'button.next'),
            action: function () {
                Calendar.next();
            }
        }]
    });
});