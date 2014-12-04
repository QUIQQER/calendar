/**
 * Datepicker for QUIQQER
 *
 * @module URL_OPT_DIR/quiqqer/calendar/bin/Calendar
 *
 * @author Arian Stolwijk (MooTools Datepicker)
 * @author www.pcsg.de (Henning Leutz - QUIQQER / AMD / moofx adaptation )
 */

define([

    'package/quiqqer/calendar/bin/Source/Picker',
    'package/quiqqer/calendar/bin/Source/Picker.Attach',
    'package/quiqqer/calendar/bin/Source/Picker.Date',

    'css!package/quiqqer/calendar/bin/Source/datepicker_dashboard/datepicker_dashboard.css'

], function(Picker, Attach, DatePicker)
{
    "use strict";

    return DatePicker;
});