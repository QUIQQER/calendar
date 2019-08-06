document.addEventListener('DOMContentLoaded', function () {
    "use strict";

    require(['qui/QUI'], function (QUI) {
        // do not scroll immediately
        (function () {
            scrollToCurrentEvent(QUI);
        }).delay(1000);
    });

    /**
     * Scroll to current (today's) event
     */
    function scrollToCurrentEvent (QUI) {
        var CurrentEventElm = document.getElement('.quiqqer-eventList-entry.status-now');

        if (!CurrentEventElm) {
            return;
        }

        var pos        = CurrentEventElm.getPosition().y + 100, // offset 100px
            winHeight  = QUI.getWindowSize().y,
            bodyScroll = QUI.getScroll().y;

        if (pos < winHeight + bodyScroll) {
            return;
        }

        if (CurrentEventElm) {
            new Fx.Scroll(window, {
                offset: {
                    x: 0,
                    y: pos - 50 - (winHeight / 2)
                }
            }).toTop();
        }
    }
});