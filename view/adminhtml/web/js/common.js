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
    'underscore',
    'Unbxd_ProductFeed/js/action/request',
    'Magento_Ui/js/modal/alert',
    'Magento_Ui/js/modal/confirm',
    'mage/translate'
], function (
    $,
    _,
    actionManager,
    alert,
    confirm,
    $t
) {
        'use strict';

        $.widget('custom.unbxdManager', {
            options: {
                config: {},
                formKey: '',
                triggers: {
                    checkCron: 'unbxd_check_cron',
                    fullSync: 'unbxd_full_sync',
                    generateProductFeed: 'unbxd_generate_product_feed',
                    generateSearchCsv: 'unbxd_generate_csv_data'
                },
                params: {
                    'isAjax': true
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

                });

                return this;
            },

            /**
             * @private
             */
            _create: function () {
                $('#' + this.options.triggers.checkCron)
                    .on('click', $.proxy(this._checkCron, this));
                $('#' + this.options.triggers.fullSync)
                    .on('click', $.proxy(this._full, this));
                $('#' + this.options.triggers.generateProductFeed)
                    .on('click', $.proxy(this._generate, this));
                $('#' + this.options.triggers.generateSearchCsv)
                    .on('click', $.proxy(this._generateSearchDataCsv, this));
            },

            /**
             * Retrieve cron jobs
             *
             * @param event
             * @returns {boolean}
             * @private
             */
            _checkCron: function (event) {
                var self = this,
                    target = $(event.currentTarget),
                    actionUrl = self.options.config.url.cronJobs,
                    params = {
                        'form_key': this.formKey
                    };

                $.extend(params, self.options.params);
                actionManager(actionUrl, 'POST', params);

                return true;
            },

            /**
             * @param event
             * @returns {boolean}
             * @private
             */
            _full: function (event) {
                var self = this,
                    target = $(event.currentTarget),
                    isActionAllow = self.options.config.isActionAllow,
                    isGeneralCronConfigured = self.options.config.isGeneralCronConfigured,
                    actionUrl = self.options.config.url.fullSync,
                    params = {
                        'form_key': this.formKey,
                        'store': self.options.config.store
                    };

                if (!isActionAllow) {
                    alert({
                        content: $.mage.__('Please provide authorization keys to perform this operation.'),
                    });

                    return false;
                }

                confirm({
                    title: $.mage.__('Confirmation'),
                    content: $.mage.__('Are you sure do you want to run full catalog product synchronization with ' +
                        '<a href="http://unbxd.com"><strong>Unbxd</strong></a> service?' + '<br/><br/>' +
                        '<strong>' + 'NOTE: ' + '</strong>' + 'This operation will be added to queue. ' +
                        'Please make sure the related cron job is configured ' +
                        'to perform this operation.'),
                    actions: {
                        /** @inheritdoc */
                        confirm: function () {
                            $.extend(params, self.options.params);
                            actionManager(actionUrl, 'POST', params);
                        },

                        /** @inheritdoc */
                        always: function (e) {
                            e.stopImmediatePropagation();
                        }
                    }
                });

                return false;
            },

            /**
             * @param event
             * @returns {boolean}
             * @private
             */
            _generate: function (event) {
                var self = this,
                    actionUrl = self.options.config.url.generate,
                    params = {
                        'form_key': this.formKey,
                        'store': self.options.config.store
                    };

                confirm({
                    title: $.mage.__('Confirmation'),
                    content: $.mage.__('Are you sure do you want to generate product feed? Generating may take ' +
                        'some time depending on the catalog size.'),
                    actions: {
                        /** @inheritdoc */
                        confirm: function () {
                            $.extend(params, self.options.params);
                            actionManager(actionUrl, 'POST', params);
                        },

                        /** @inheritdoc */
                        always: function (e) {
                            e.stopImmediatePropagation();
                        }
                    }
                });
            },
            _generateSearchDataCsv: function (event) {
                var self = this,
                    actionUrl = self.options.config.url.searchdata,
                    params = {
                        'form_key': this.formKey,
                        'store': self.options.config.store
                    };

                confirm({
                    title: $.mage.__('Confirmation'),
                    content: $.mage.__('Are you sure do you want to generate product Search data?.'),
                    actions: {
                        /** @inheritdoc */
                        confirm: function () {
                            $.extend(params, self.options.params);
                            actionManager(actionUrl, 'POST', params);
                        },

                        /** @inheritdoc */
                        always: function (e) {
                            e.stopImmediatePropagation();
                        }
                    }
                });
            }

        });

        return $.custom.unbxdManager;
    });
