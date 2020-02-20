/**
 * Copyright (c) 2020 Unbxd Inc.
 */

/**
 * Init development:
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
                'title': 'Incremental Synchronization',
                'type': 'slide',
                'modalClass': 'incremental-synchronization-container',
                'responsive': true,
                'innerScroll': true,
                'trigger': '#unbxd_incremental_sync'
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
