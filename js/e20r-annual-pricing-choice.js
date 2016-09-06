/**
 * Copyright (C) 2016  Thomas Sjolshagen - Eighty / 20 Results by Wicked Strong Chicks, LLC
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */

jQuery.noConflict();

var e20rAnnualSubscription = {
    init: function () {
        "use strict";

        this.levels_found = jQuery('input.pmpro-level-id');
        this.select_annual = jQuery('#e20r-annual-pricing-choice');
        this.select_monthly = jQuery('#e20r-monthly-pricing-choice');
        this.annual_levels = this._convert_ints(e20r_annual_pricing.levels); // The Level IDs for the annual level types
        this.free_levels = this._convert_ints( e20r_annual_pricing.free_levels );
        this.level_map = e20r_annual_pricing.level_map;

        var self = this;

        this.select_annual.on('click', function () {
            self.show_annual();
        });

        this.select_monthly.on('click', function () {
            self.show_monthly();
        });

        if (e20r_annual_pricing.default === 'annually') {
            self.show_annual();
        } else {
            self.show_monthly(this.select_monthly);
        }


    },
    show_monthly: function ($hide_option) {
        "use strict";

        var self = this;

        if (!( $hide_option instanceof jQuery )) {
            $hide_option = jQuery($hide_option);
        }

        window.console.log("Requesting that we show monthly subscription options");

        self.levels_found.each(function () {

            var level = jQuery(this);
            var level_id = parseInt(level.val());
            var row = self.select_layout_type(level);

            window.console.log("Row in show_monthly: ", row);

            var is_annual = jQuery.inArray(level_id, self.annual_levels);
            var is_free = jQuery.inArray( level_id, self.free_levels );

            // Show the monthly row because the level ID isn't in the array of annual levels
            if ( -1 === is_annual ) {
                row.show();
            }

            // hide row because the level ID IS in the array of annual levels
            if (-1 < is_annual && true === self.has_monthly( level_id ) && -1 === is_free ) {
                row.hide();
            }
        });
    },
    show_annual: function ($hide_option) {
        "use strict";

        var self = this;

        if (!( $hide_option instanceof jQuery )) {
            $hide_option = jQuery($hide_option);
        }

        window.console.log("Requesting that we show annual subscription options if there is a mapping");

        self.levels_found.each(function () {

            var level = jQuery(this);
            var level_id = parseInt(level.val());
            var row = self.select_layout_type(level);

            var is_annual = jQuery.inArray(level_id, self.annual_levels);
            var is_free = jQuery.inArray( level_id, self.free_levels );

            // Show row because the level ID IS in the array of annual levels
            if (-1 < is_annual && true === self.has_monthly( level_id ) ) {

                row.show();
            }

            // Hide row because the level ID IS NOT in the array of annual levels
            if (-1 === is_annual && true === self.has_monthly( level_id ) && -1 === is_free ) {
                row.hide();
            }
        });
    },
    has_monthly: function (level_id) {
        "use strict";

        var self = this;

        if ( self.level_map[level_id] !== null ) {
            return true;
        }

        return false;
    },
    select_layout_type: function (level) {
        "use strict";

        var is_table = jQuery('table#pmpro_levels_table, table#pmpro_levels');
        var is_div = jQuery('div#pmpro_levels');

        var $row;

        if (is_table.length !== 0) {
            window.console.log("Selecting a table row");
            $row = level.closest('tr');
        }

        if (is_div.length !== 0) {
            window.console.log("Selecting a div");
            $row = level.closest('div.pmpro_level');
        }

        return $row;
    },
    _convert_ints: function ($array) {
        "use strict";

        var int_array = [];

        for (var i = 0; i < $array.length; i++) {
            int_array[i] = parseInt($array[i]);
        }

        return int_array;
    }
};

jQuery(document).ready(function () {
    "use strict";
    window.console.log("Loading Levels page & initiating the Annual Subscription handler");
    e20rAnnualSubscription.init();
});