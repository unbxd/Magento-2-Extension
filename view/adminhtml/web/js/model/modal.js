/**
 * @author andy
 * @email andyworkbase@gmail.com
 * @team MageCloud
 */

define([
    'jquery',
    'Magento_Ui/js/modal/modal'
], function ($, modal) {
    'use strict';

    return {
        modalWindow: null,

        /**
         * Create popUp window for provided element
         *
         * @param config
         * @param {HTMLElement} element
         */
        createPopUp: function (config, element) {
            $.extend(config, {
                'title': 'Log Viewer',
                'type': 'slide',
                'modalClass': 'log-viewer-container',
                'responsive': true,
                'innerScroll': true,
                'trigger': '#log_viewer'
            });

            this.modalWindow = element;
            modal(config, $(this.modalWindow));
        },

        /**
         * Show modal window
         */
        showModal: function () {
            $(this.modalWindow).modal('openModal');
        },

        /**
         * Show modal window
         */
        closeModal: function () {
            $(this.modalWindow).modal('closeModal');
        }
    };
});
