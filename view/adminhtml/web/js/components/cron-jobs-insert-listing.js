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
    'Magento_Ui/js/lib/view/utils/async',
    'uiRegistry',
    'underscore',
    'Magento_Ui/js/form/components/insert-listing'
], function ($, registry, _, InsertListing) {
    'use strict';

    return InsertListing.extend({
        defaults: {
            clearJobsUrl: '',
            separateLayoutUrl: '',
            configureGroupsUrl: '',
            groupCode: '',
            groupName: '',
            groupSortOrder: 10,
            formProvider: '',
            modules: {
                form: '${ $.formProvider }',
                modal: '${ $.parentName }'
            },
        },

        /**
         * Render jobs
         */
        render: function () {
            this._super();
        },

        /**
         * Clear jobs
         */
        clearJobs: function () {
            window.location.href = this.clearJobsUrl;
        },

        /**
         * Separate layout listing location
         */
        separateLayout: function () {
            window.location.href = this.separateLayoutUrl;
        },

        /**
         * Configure cron groups location
         */
        configureGroups: function () {
            window.location.href = this.configureGroupsUrl;
        }
    });
});
