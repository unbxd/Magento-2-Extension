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
    'ko',
    'uiElement',
    'underscore',
    'Unbxd_ProductFeed/js/action/request',
    'Unbxd_ProductFeed/js/model/modal',
    'Magento_Ui/js/modal/modal',
    'Magento_Ui/js/modal/alert',
    'Magento_Ui/js/modal/confirm',
    'mage/translate'
], function (
    $,
    ko,
    Element,
    _,
    action,
    modalManager,
    modal,
    alert,
    confirm,
    $t
) {
    'use strict';

    var config = window.logViewer;

    return Element.extend({
        modalWindow: null,
        isLoading: ko.observable(false),

        defaults: {
            template: 'Unbxd_ProductFeed/log-viewer',
            formKey: config.hasOwnProperty('formKey') ? config.formKey : FORM_KEY,
            url: config.url,
            fileLocation: config.file.location,
            fileContent: config.file.content,
            fileSize: config.file.size
        },

        /**
         * Initializes
         *
         * @returns {exports}
         */
        initialize: function () {
            var self = this;

            this._super();
            action.registerCallback(function (data, response) {
                if (!_.isEmpty(response)) {
                    self.isLoading(false);
                    var content = response.hasOwnProperty('updatedContent')
                        ? response.updatedContent
                        : '';
                    $('#log_content').html(content);
                    self.scrollLogContent();
                }
            });

            return this;
        },

        /**
         * Init modal window
         * @param element
         */
        setModalElement: function (element) {
            var self = this,
                config;

            if (modalManager.modalWindow == null) {
                config = {
                    buttons: [
                        {
                            text: $t('Download Log'),
                            class: 'action primary',
                            click: function (event) {
                                window.location.href = self.url.downloadFile;
                            }
                        },
                        {
                            text: $t('Refresh Log Content'),
                            class: 'action primary',
                            click: function (event) {
                                self._refreshContent(event);
                            }
                        },
                        {
                            text: $t('Flush Log Content'),
                            class: 'action primary',
                            click: function (event) {
                                self._flushContent(event);
                            }
                        }
                    ]
                };
                modalManager.createPopUp(config, element);
            }
        },

        /**
         * Show modal window
         */
        showModal: function () {
            if (this.modalWindow) {
                $(this.modalWindow).modal('openModal');
            } else {
                alert({
                    content: $t('Log Viewer Is Unavailable.')
                });
            }
        },

        /**
         * @param event
         */
        scrollLogContent: function(event) {
            var logContainer = event ? event : document.getElementById('log_content');

            if (logContainer) {
                var dh = logContainer.scrollHeight,
                    ch = logContainer.clientHeight;

                if (dh > ch) {
                    logContainer.scrollTop = dh - ch;
                }
            }
        },

        /**
         * Refresh log file content
         *
         * @param event
         * @returns {boolean}
         * @private
         */
        _refreshContent: function (event) {
            var url = this.url.refreshContent,
                target = $(event.currentTarget);

            this.isLoading(true);

            action(url, 'POST', {
                'form_key': this.formKey
            });

            return false;
        },

        /**
         * Flush log file content
         *
         * @param event
         * @returns {boolean}
         * @private
         */
        _flushContent: function (event) {
            var url = this.url.flushContent,
                target = $(event.currentTarget);

            this.isLoading(true);

            action(url, 'POST', {
                'form_key': this.formKey
            });

            return false;
        }
    });
});
