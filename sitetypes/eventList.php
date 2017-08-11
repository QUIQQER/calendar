<?php

/**
 * Event-List
 */

$ChildrenList = new QUI\Controls\ChildrenList(array(
    'showTitle'       => false,
    'Site'            => $Site,
    'limit'           => $Site->getAttribute('eventList.settings.max'),
    'showContent'     => false,
    'itemtype'        => 'http://schema.org/ItemList',
    'child-itemtype'  => 'http://schema.org/ListItem',
    'displayTemplate' => dirname(__FILE__) . '/eventListControlTemplate.html',
    'displayCss'      => dirname(__FILE__) . '/eventListControlTemplate.css'
));

try {
    $ChildrenList->checkLimit();
} catch (QUI\Exception $Exception) {
    QUI\System\Log::addWarning($Exception->getMessage());
}

$Engine->assign(array(
    'ChildrenList' => $ChildrenList
));
