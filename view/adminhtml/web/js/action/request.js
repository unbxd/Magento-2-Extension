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
    'Magento_Ui/js/modal/alert',
    'mage/translate'
], function (
    $,
    alert,
    $t
) {
    'use strict';

    var callbacks = [],

        /**
         * Perform asynchronous request to server.
         *
         * @param url
         * @param type
         * @param data
         * @param async
         * @param showLoader
         * @param loaderContext
         * @param redirectUrl
         * @param isGlobal
         * @param contentType
         * @param messageContainer
         * @returns {*}
         */
        action = function (
            url,
            type,
            data,
            async,
            showLoader,
            loaderContext,
            redirectUrl,
            isGlobal,
            contentType,
            messageContainer
        ) {
            url = url || (data.hasOwnProperty('url') ? data.url : '');
            type = type || 'POST';
            data = data || {};
            async = async === undefined ? false : async;
            showLoader = showLoader === undefined ? true : showLoader;
            isGlobal = isGlobal === undefined ? true : isGlobal;
            contentType = contentType || 'json';
            messageContainer = messageContainer || {};

            return $.ajax({
                url: url,
                type: type,
                data: data,
                async: async,
                global: isGlobal,
                dataType: contentType,
                showLoader: showLoader
            }).done(function (response) {
                if (response.errors) {
                    alert({
                        content: $t(response.message)
                    });
                    callbacks.forEach(function (callback) {
                        callback(data, response);
                    });
                } else {
                    callbacks.forEach(function (callback) {
                        callback(data, response);
                    });
                    if (redirectUrl) {
                        window.location.href = redirectUrl;
                    } else if (response.redirectUrl) {
                        window.location.href = response.redirectUrl;
                    } else if (response.content) {
                        alert({
                            title: response.hasOwnProperty('title') ? response.title : $t('Response'),
                            type: 'slide',
                            modalClass: 'unbxd-response-modal-container',
                            content: response.content
                        });
                    } else if (response.hasOwnProperty('updatedContent')) {
                        // action(s) will be performed by callbacks
                        return true;
                    } else {
                        // scroll to top of the page to make sure the displayed messages (if any) will be detected by user
                        $("html, body").animate({ scrollTop: 0 }, "slow");
                        window.location.reload();
                    }
                }
            }).fail(function () {
                alert({
                    content: $t('Request failed. Please try again later.')
                });
                callbacks.forEach(function (callback) {
                    callback(data, {});
                });
            });
        };

    /**
     * @param {Function} callback
     */
    action.registerCallback = function (callback) {
        callbacks.push(callback);
    };

    return action;
});
