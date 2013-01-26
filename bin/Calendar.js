/**
 * Datepicker for QUIQQER
 *
 * @author Arian Stolwijk (MooTools Datepicker)
 * @author www.pcsg.de (Henning Leutz - QUIQQER / AMD adaptation )
 *
 * @module package/quiqqer/calendar/bin/Calendar
 */

define('package/quiqqer/calendar/bin/Calendar', [

    'package/quiqqer/calendar/bin/Source/Picker.js',
    'package/quiqqer/calendar/bin/Source/Picker.Attach.js',
    'package/quiqqer/calendar/bin/Source/Picker.Date.js',

    'css!package/quiqqer/calendar/bin/Source/datapicker.css'

], function(Picker, Attach, DatePicker)
{
    return DatePicker;
});