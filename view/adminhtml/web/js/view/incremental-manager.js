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
    'Unbxd_ProductFeed/js/model/incremental',
    'Magento_Ui/js/modal/modal',
    'Magento_Ui/js/modal/alert',
    'Magento_Ui/js/modal/confirm',
    'mage/translate'
], function (
    $,
    ko,
    Element,
    _,
    actionManager,
    incrementalModal,
    modal,
    alert,
    confirm,
    $t
) {
    'use strict';

    return Element.extend({
        modalWindow: null,
        isLoading: ko.observable(false),

        defaults: {
            template: 'Unbxd_ProductFeed/incremental-manager',
            jsonConfig: {},
            formKey: '',
            productIds: '',
            statefull: {
                productIds: true
            }
        },

        /**
         * Initializes
         *
         * @returns {exports}
         */
        initialize: function () {
            var self = this;

            this._super();
            actionManager.registerCallback(function (data) {
                self.isLoading(false);
            });

            return this;
        },

        /**
         * Initializes observable properties.
         *
         * @returns {exports}
         */
        initObservable: function () {
            this._super()
                .track([
                    'productIds'
                ]);

            this._productIds = ko.pureComputed({
                read: ko.getObservable(this, 'productIds'),

                /**
                 * validates textarea field prior to updating 'value' property.
                 */
                write: function (value) {
                    this.productIds = value;
                    this._productIds.notifySubscribers(value);
                },

                owner: this
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

            if (incrementalModal.modalWindow == null) {
                config = {
                    buttons: [
                        {
                            text: $t('Synchronize'),
                            class: 'action primary',
                            click: function (event) {
                                self.synchronize(event);
                            }
                        },
                    ]
                };
                incrementalModal.createPopUp(config, element);
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
                    content: $t('Incremental Manager Is Unavailable.')
                });
            }
        },

        /**
         * @returns {boolean}
         * @private
         */
        _validateProductIds: function() {
            var self = this,
                idsString = self.productIds,
                candidateString;

            if (_.isEmpty(idsString)) {
                alert({
                    content: $.mage.__('Please provide at least one product ID to perform this operation.')
                });
                return false;
            }

            // replace all not valid chars with '|' - custom identifier for detect if string contain not valid chars
            candidateString = idsString.replace(/[^0-9,]/g, "|");

            if (candidateString.indexOf('|') >= 0) {
                alert({
                    content: $t('Please provide a valid product IDs. ' + '<br/>'
                        + 'String ' + '<strong>' + idsString + '</strong>' + ' is not valid.')
                });
                return false;
            }

            return true;
        },

        /**
         * @param event
         * @returns {boolean}
         */
        synchronize: function (event) {
            var self = this,
                isActionAllow = self.jsonConfig.isActionAllow,
                url = self.jsonConfig.url.incrementalSync,
                store = self.jsonConfig.store;

            event.stopPropagation();

            if (!isActionAllow) {
                alert({
                    content: $.mage.__('Please provide authorization keys to perform this operation.'),
                });

                return false;
            }

            if (!this._validateProductIds()) {
                return false;
            }

            confirm({
                content: $.mage.__('Are you sure do you want to synchronize product with next ID(s): ' + "<br/>" +
                    '<strong>' + this.productIds + '</strong>'),
                actions: {
                    /** @inheritdoc */
                    confirm: function () {
                        self.isLoading(true);
                        actionManager(url, 'POST', {
                            'form_key': self.formKey,
                            'ids': self.productIds,
                            'store': store
                        });
                    },

                    /** @inheritdoc */
                    always: function (e) {
                        e.stopImmediatePropagation();
                    }
                }
            });
        }
    });
});