<?php
# MantisBT - A PHP based bugtracking system

# MantisBT is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 2 of the License, or
# (at your option) any later version.
#
# MantisBT is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with MantisBT.  If not, see <http://www.gnu.org/licenses/>.

/**
 * @package MantisBT
 * @copyright Copyright (C) 2000 - 2002  Kenzaburo Ito - kenito@300baud.org
 * @copyright Copyright (C) 2002 - 2010  MantisBT Team - mantisbt-dev@lists.sourceforge.net
 * @link http://www.mantisbt.org
 *
 * @uses config_api.php
 */

require_once( 'core.php' );
require_api( 'config_api.php' );

function print_config_value( $p_config_key ) {
	echo "config['" . $p_config_key . "'] = '" . addslashes( config_get( $p_config_key ) ) . "';\n";
}

/**
 * WARNING: DO NOT EXPOSE SENSITIVE CONFIGURATION VALUES!
 *
 * All configuration values below are publicly available to visitors of the bug
 * tracker regardless of whether they're authenticated. Server paths should not
 * be exposed. It is OK to expose paths that the user sees directly (short
 * paths) but you do need to be careful in your selections. Consider servers
 * using URL rewriting engines to mask/convert user-visible paths to paths that
 * should only be known internally to the server.
 */

echo "var config = new Array();\n";
print_config_value( 'calendar_js_date_format' );
print_config_value( 'icon_path' );
print_config_value( 'short_path' );