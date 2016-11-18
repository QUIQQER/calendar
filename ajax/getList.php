<?php

/**
 * Return the Cronlist
 *
 * @return array
 */

QUI::$Ajax->registerFunction(
    'package_quiqqer_calendar_ajax_getList',
    function () {


        return 'test';
    },
    false,
    'Permission::checkAdminUser'
);
