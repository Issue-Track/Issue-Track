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
 * HTML API
 *
 * These functions control the HTML output of each page.
 *
 * This is the call order of these functions, should you need to figure out
 * which to modify or which to leave out:
 *
 * html_page_top
 *   html_page_top1
 *     html_begin
 *     html_head_begin
 *     html_content_type
 *     (Additional META tags: {@see $g_meta_include_file} and {@see robots_meta config})
 *     html_title
 *     html_css
 *     html_rss_link
 *     html_head_javascript
 *   (html_meta_redirect)
 *   html_page_top2
 *     html_page_top2a
 *       html_head_end
 *       html_body_begin
 *       html_top_banner
 *     html_login_info
 *     (print_project_menu_bar)
 *     MantisMenu::printMenu('main')
 *
 * ...Page content here...
 *
 * html_page_bottom
 *   html_page_bottom1
 *     (MantisMenu::printMenu('main'))
 *     html_page_bottom1a
 *       html_bottom_banner
 *       html_footer
 *       html_body_end
 *       html_end
 *
 * @package CoreAPI
 * @subpackage HTMLAPI
 * @copyright Copyright 2000 - 2002  Kenzaburo Ito - kenito@300baud.org
 * @copyright Copyright 2002  MantisBT Team - mantisbt-dev@lists.sourceforge.net
 * @link http://www.mantisbt.org
 *
 * @uses access_api.php
 * @uses authentication_api.php
 * @uses bug_api.php
 * @uses config_api.php
 * @uses constant_inc.php
 * @uses current_user_api.php
 * @uses database_api.php
 * @uses error_api.php
 * @uses event_api.php
 * @uses file_api.php
 * @uses filter_api.php
 * @uses filter_constants_inc.php
 * @uses form_api.php
 * @uses helper_api.php
 * @uses lang_api.php
 * @uses news_api.php
 * @uses php_api.php
 * @uses print_api.php
 * @uses project_api.php
 * @uses rss_api.php
 * @uses string_api.php
 * @uses user_api.php
 * @uses utility_api.php
 */

require_api( 'access_api.php' );
require_api( 'authentication_api.php' );
require_api( 'bug_api.php' );
require_api( 'config_api.php' );
require_api( 'constant_inc.php' );
require_api( 'current_user_api.php' );
require_api( 'database_api.php' );
require_api( 'error_api.php' );
require_api( 'event_api.php' );
require_api( 'file_api.php' );
require_api( 'filter_api.php' );
require_api( 'filter_constants_inc.php' );
require_api( 'form_api.php' );
require_api( 'helper_api.php' );
require_api( 'lang_api.php' );
require_api( 'news_api.php' );
require_api( 'php_api.php' );
require_api( 'print_api.php' );
require_api( 'project_api.php' );
require_api( 'rss_api.php' );
require_api( 'string_api.php' );
require_api( 'user_api.php' );
require_api( 'utility_api.php' );

$g_rss_feed_url = null;

$g_robots_meta = '';

# flag for error handler to skip header menus
$g_error_send_page_header = true;

# flag to skip submenus
$g_skip_submenus = false;

$g_stylesheets_included = array();
$g_scripts_included = array();

/**
 * Sets the url for the rss link associated with the current page.
 * null: means no feed (default).
 * @param string $p_rss_feed_url RSS feed URL.
 * @return void
 */
function html_set_rss_link( $p_rss_feed_url ) {
	if( OFF != config_get( 'rss_enabled' ) ) {
		global $g_rss_feed_url;
		$g_rss_feed_url = $p_rss_feed_url;
	}
}

/**
 * This method must be called before the html_page_top* methods.  It marks the page as not
 * for indexing.
 * @return void
 */
function html_robots_noindex() {
	global $g_robots_meta;
	$g_robots_meta = 'noindex,follow';
}

/**
 * Prints the link that allows auto-detection of the associated feed.
 * @return void
 */
function html_rss_link() {
	global $g_rss_feed_url;

	if( $g_rss_feed_url !== null ) {
		echo '<link rel="alternate" type="application/rss+xml" title="RSS" href="' . string_attribute( $g_rss_feed_url ) . '" />' . "\n";
	}
}

/**
 * Prints a <script> tag to include a JavaScript file.
 * @param string $p_filename Name of JavaScript file (with extension) to include.
 * @return void
 */
function html_javascript_link( $p_filename ) {
	echo "\t", '<script type="text/javascript" src="', helper_mantis_url( 'javascript/' . $p_filename ), '"></script>' . "\n";
}

/**
 * Defines the top of a HTML page
 * @param string $p_page_title   Html page title.
 * @param string $p_redirect_url URL to redirect to if necessary.
 * @return void
 */
function html_page_top( $p_page_title = null, $p_redirect_url = null ) {
	html_page_top1( $p_page_title );
	if( $p_redirect_url !== null ) {
		html_meta_redirect( $p_redirect_url );
	}
	html_page_top2();
}

/**
 * Print the part of the page that comes before meta redirect tags should be inserted
 * @param string $p_page_title Page title.
 * @return void
 */
function html_page_top1( $p_page_title = null ) {
	html_begin();
	html_head_begin();

	html_content_type();
	$t_meta = config_get_global( 'meta_include_file' );
	if( !is_blank( $t_meta ) ) {
		include( $t_meta );
	}
	global $g_robots_meta;
	if( !is_blank( $g_robots_meta ) ) {
		echo "\t", '<meta name="robots" content="', $g_robots_meta, '" />', "\n";
	}

	html_title( $p_page_title );
	html_css();
	html_rss_link();

	$t_favicon_image = config_get( 'favicon_image' );
	if( !is_blank( $t_favicon_image ) ) {
		echo "\t", '<link rel="shortcut icon" href="', helper_mantis_url( $t_favicon_image ), '" type="image/x-icon" />', "\n";
	}

	# Advertise the availability of the browser search plug-ins.
	echo "\t", '<link rel="search" type="application/opensearchdescription+xml" title="MantisBT: Text Search" href="' . string_sanitize_url( 'browser_search_plugin.php?type=text', true ) . '" />' . "\n";
	echo "\t", '<link rel="search" type="application/opensearchdescription+xml" title="MantisBT: Issue Id" href="' . string_sanitize_url( 'browser_search_plugin.php?type=id', true ) . '" />' . "\n";

	html_head_javascript();
}

/**
 * Print the part of the page that comes after meta tags, but before the actual page content
 * @return void
 */
function html_page_top2() {
	global $g_skip_submenus;
	html_page_top2a();

	if( !db_is_connected() ) {
		return;
	}

	if( auth_is_user_authenticated() ) {
		html_login_info();

		if( ON == config_get( 'show_project_menu_bar' ) ) {
			print_project_menu_bar();
			echo '<br />';
		}
	}

	$t_menu_class = config_get( 'menu_class', 'MantisMenu' );
	$t_page =  basename( $_SERVER['PHP_SELF'] );
	$t_plugin_name = null;
	if( $t_page == 'plugin.php' ) {
		$t_page = gpc_get_string( 'page' );
		list( $t_plugin_name, $t_page ) = explode( '/', $t_page );
	}

	$t_menus = call_user_func( array($t_menu_class, 'getMenusForPage' ), $t_page, $t_plugin_name, false );
	foreach( $t_menus AS $t_menu ) {
		if( $t_menu->name == 'main' ) {
			echo '<div class="main-menu">' . "\n";
			$t_menu->include_div = false;
		} else {
			$t_menu->include_div = true;
		}
		if( $t_menu->name == 'main' ) {
			$t_menu->ToString();
			print_bug_jump();
			echo '</div>' . "\n";
		} else {
			if( !$g_skip_submenus ) {
				$t_menu->ToString();
			}
		}
	}

	echo '<div id="content">', "\n";
	event_signal( 'EVENT_LAYOUT_CONTENT_BEGIN' );
}

/**
 * Print the part of the page that comes after meta tags and before the
 *  actual page content, but without login info or menus.  This is used
 *  directly during the login process and other times when the user may
 *  not be authenticated
 * @return void
 */
function html_page_top2a() {
	global $g_error_send_page_header;

	html_head_end();
	html_body_begin();
	$g_error_send_page_header = false;
	html_top_banner();
}

/**
 * Print the part of the page that comes below the page content
 * $p_file should always be the __FILE__ variable. This is passed to show source
 * @param string $p_file Should always be the __FILE__ variable. This is passed to show source.
 * @return void
 */
function html_page_bottom( $p_file = null ) {
	html_page_bottom1( $p_file );
}

/**
 * Print the part of the page that comes below the page content
 * $p_file should always be the __FILE__ variable. This is passed to show source
 * @param string $p_file Should always be the __FILE__ variable. This is passed to show source.
 * @return void
 */
function html_page_bottom1( $p_file = null ) {
	if( !db_is_connected() ) {
		return;
	}

	event_signal( 'EVENT_LAYOUT_CONTENT_END' );
	echo '</div>', "\n";
	if( config_get( 'show_footer_menu' ) ) {
		echo '<br />';
		print_bug_jump();
		$t_menu_class = config_get( 'menu_class', 'MantisMenu' );
		call_user_func( array($t_menu_class, 'printMenu' ), 'main', true );
	}

	html_page_bottom1a( $p_file );
}

/**
 * Print the part of the page that comes below the page content but leave off
 * the menu.  This is used during the login process and other times when the
 * user may not be authenticated.
 * @param string $p_file Should always be the __FILE__ variable.
 * @return void
 */
function html_page_bottom1a( $p_file = null ) {
	if( null === $p_file ) {
		$p_file = basename( $_SERVER['SCRIPT_NAME'] );
	}

	html_bottom_banner();
	html_footer();
	html_body_end();
	html_end();
}

/**
 * (1) Print the document type and the opening <html> tag
 * @return void
 */
function html_begin() {
	echo '<!DOCTYPE html>', "\n";
	echo '<html>', "\n";
}

/**
 * (2) Begin the <head> section
 * @return void
 */
function html_head_begin() {
	echo '<head>', "\n";
}

/**
 * (3) Print the content-type
 * @return void
 */
function html_content_type() {
	echo "\t", '<meta http-equiv="Content-type" content="text/html; charset=utf-8" />', "\n";
}

/**
 * (4) Print the window title
 * @param string $p_page_title Window title.
 * @return void
 */
function html_title( $p_page_title = null ) {
	$t_page_title = string_html_specialchars( $p_page_title );
	$t_title = string_html_specialchars( config_get( 'window_title' ) );
	echo "\t", '<title>';
	if( empty( $t_page_title ) ) {
		echo $t_title;
	} else {
		if( empty( $t_title ) ) {
			echo $t_page_title;
		} else {
			echo $t_page_title . ' - ' . $t_title;
		}
	}
	echo '</title>', "\n";
}

/**
 * Require a CSS file to be in html page headers
 * @param string $p_stylesheet_path Path to CSS style sheet.
 * @return void
 */
function require_css( $p_stylesheet_path ) {
	global $g_stylesheets_included;
	$g_stylesheets_included[$p_stylesheet_path] = $p_stylesheet_path;
}

/**
 * (5) Print the link to include the CSS file
 * @return void
 */
function html_css() {
	global $g_stylesheets_included;
	html_css_link( config_get( 'css_include_file' ) );
	html_css_link( 'jquery-ui-1.10.0.custom.min.css' );
	html_css_link( 'common_config.php' );
	# Add right-to-left css if needed
	if( lang_get( 'directionality' ) == 'rtl' ) {
		html_css_link( config_get( 'css_rtl_include_file' ) );
	}
	foreach( $g_stylesheets_included as $t_stylesheet_path ) {
		html_css_link( $t_stylesheet_path );
	}
}

/**
 * Prints a CSS link
 * @param string $p_filename Filename.
 * @return void
 */
function html_css_link( $p_filename ) {
	echo "\t", '<link rel="stylesheet" type="text/css" href="', string_sanitize_url( helper_mantis_url( 'css/' . $p_filename ), true ), '" />' . "\n";
}


/**
 * (6) Print an HTML meta tag to redirect to another page
 * This function is optional and may be called by pages that need a redirect.
 * $p_time is the number of seconds to wait before redirecting.
 * If we have handled any errors on this page return false and don't redirect.
 *
 * @param string  $p_url      The page to redirect: has to be a relative path.
 * @param integer $p_time     Seconds to wait for before redirecting.
 * @param boolean $p_sanitize Apply string_sanitize_url to passed URL.
 * @return boolean
 */
function html_meta_redirect( $p_url, $p_time = null, $p_sanitize = true ) {
	if( ON == config_get_global( 'stop_on_errors' ) && error_handled() ) {
		return false;
	}

	if( null === $p_time ) {
		$p_time = current_user_get_pref( 'redirect_delay' );
	}

	$t_url = config_get( 'path' );
	if( $p_sanitize ) {
		$t_url .= string_sanitize_url( $p_url );
	} else {
		$t_url .= $p_url;
	}

	$t_url = htmlspecialchars( $t_url );

	echo "\t" . '<meta http-equiv="Refresh" content="' . $p_time . ';URL=' . $t_url . '" />' . "\n";

	return true;
}

/**
 * Require a javascript file to be in html page headers
 * @param string $p_script_path Path to javascript file.
 * @return void
 */
function require_js( $p_script_path ) {
	global $g_scripts_included;
	$g_scripts_included[$p_script_path] = $p_script_path;
}

/**
 * (6a) Javascript...
 * @return void
 */
function html_head_javascript() {
	global $g_scripts_included;

	echo "\t" . '<script type="text/javascript" src="' . helper_mantis_url( 'javascript_config.php' ) . '"></script>' . "\n";
	echo "\t" . '<script type="text/javascript" src="' . helper_mantis_url( 'javascript_translations.php' ) . '"></script>' . "\n";
	html_javascript_link( 'jquery-1.9.1.min.js' );
	html_javascript_link( 'jquery-ui-1.10.0.custom.min.js' );
	html_javascript_link( 'common.js' );
	foreach ( $g_scripts_included as $t_script_path ) {
		html_javascript_link( $t_script_path );
	}
}

/**
 * (7) End the <head> section
 * @return void
 */
function html_head_end() {
	event_signal( 'EVENT_LAYOUT_RESOURCES' );

	echo '</head>', "\n";
}

/**
 * (8) Begin the <body> section
 * @return void
 */
function html_body_begin() {
	$t_centered_page = is_page_name( 'login_page' ) || is_page_name( 'signup_page' ) || is_page_name( 'signup' ) || is_page_name( 'lost_pwd_page' );

	echo '<body>', "\n";

	if( $t_centered_page ) {
		echo '<div id="mantis" class="centered_page">', "\n";
	} else {
		echo '<div id="mantis">', "\n";
	}

	event_signal( 'EVENT_LAYOUT_BODY_BEGIN' );
}

/**
 * (9) Print a user-defined banner at the top of the page if there is one.
 * @return void
 */
function html_top_banner() {
	$t_page = config_get( 'top_include_page' );
	$t_logo_image = config_get( 'logo_image' );
	$t_logo_url = config_get( 'logo_url' );

	if( is_blank( $t_logo_image ) ) {
		$t_show_logo = false;
	} else {
		$t_show_logo = true;
		if( is_blank( $t_logo_url ) ) {
			$t_show_url = false;
		} else {
			$t_show_url = true;
		}
	}

	if( !is_blank( $t_page ) && file_exists( $t_page ) && !is_dir( $t_page ) ) {
		include( $t_page );
	} else if( $t_show_logo ) {
		echo '<div id="banner">';
		if( $t_show_url ) {
			echo '<a id="logo-link" href="', config_get( 'logo_url' ), '">';
		}
		$t_alternate_text = string_html_specialchars( config_get( 'window_title' ) );
		echo '<img id="logo-image" alt="', $t_alternate_text, '" src="' . helper_mantis_url( $t_logo_image ) . '" />';
		if( $t_show_url ) {
			echo '</a>';
		}
		echo '</div>';
	}

	event_signal( 'EVENT_LAYOUT_PAGE_HEADER' );
}

/**
 * (10) Print the user's account information
 * Also print the select box where users can switch projects
 * @return void
 */
function html_login_info() {
	$t_username = current_user_get_field( 'username' );
	$t_access_level = get_enum_element( 'access_levels', current_user_get_access_level() );
	$t_now = date( config_get( 'complete_date_format' ) );
	$t_realname = current_user_get_field( 'realname' );

	# Login information
	echo '<div id="login-info">' . "\n";
	if( current_user_is_anonymous() ) {
		$t_return_page = $_SERVER['SCRIPT_NAME'];
		if( isset( $_SERVER['QUERY_STRING'] ) ) {
			$t_return_page .= '?' . $_SERVER['QUERY_STRING'];
		}

		$t_return_page = string_url( $t_return_page );

		echo "\t" . '<span id="logged-anon-label">' . lang_get( 'anonymous' ) . '</span>' . "\n";
		echo "\t" . '<span id="login-link"><a href="' . helper_mantis_url( 'login_page.php?return=' . $t_return_page ) . '">' . lang_get( 'login_link' ) . '</a></span>' . "\n";
		if( config_get_global( 'allow_signup' ) == ON ) {
			echo "\t" . '<span id="signup-link"><a href="' . helper_mantis_url( 'signup_page.php' ) . '">' . lang_get( 'signup_link' ) . '</a></span>' . "\n";
		}
	} else {
		echo "\t" . '<span id="logged-in-label">' . lang_get( 'logged_in_as' ) . '</span>' . "\n";
		echo "\t" . '<span id="logged-in-user">' . string_html_specialchars( $t_username ) . '</span>' . "\n";
		echo "\t" . '<span id="logged-in">';
		echo !is_blank( $t_realname ) ?  "\t" . '<span id="logged-in-realname">' . string_html_specialchars( $t_realname ) . '</span>' . "\n" : '';
		echo "\t" . '<span id="logged-in-accesslevel" class="' . $t_access_level . '">' . $t_access_level . '</span>' . "\n";
		echo "\t" . '</span>' . "\n";
	}
	echo '</div>' . "\n";

	# RSS feed
	if( OFF != config_get( 'rss_enabled' ) ) {
		echo '<div id="rss-feed">' . "\n";
		# Link to RSS issues feed for the selected project, including authentication details.
		echo "\t" . '<a href="' . htmlspecialchars( rss_get_issues_feed_url() ) . '">' . "\n";
		echo "\t" . '<img src="' . helper_mantis_url( 'images/rss.png' ) . '" alt="' . lang_get( 'rss' ) . '" title="' . lang_get( 'rss' ) . '" />' . "\n";
		echo "\t" . '</a>' . "\n";
		echo '</div>' . "\n";
	}

	# Project Selector (hidden if only one project visisble to user)
	$t_show_project_selector = true;
	$t_project_ids = current_user_get_accessible_projects();
	if( count( $t_project_ids ) == 1 ) {
		$t_project_id = (int)$t_project_ids[0];
		if( count( current_user_get_accessible_subprojects( $t_project_id ) ) == 0 ) {
			$t_show_project_selector = false;
		}
	}

	if( $t_show_project_selector ) {
		echo '<div id="project-selector-div">';
		echo '<form method="post" id="form-set-project" action="' . helper_mantis_url( 'set_project.php' ) . '">';
		echo '<fieldset id="project-selector">';
		# CSRF protection not required here - form does not result in modifications

		echo '<label for="form-set-project-id">' . lang_get( 'email_project' ) . '</label>';
		echo '<select id="form-set-project-id" name="project_id">';
		print_project_option_list( join( ';', helper_get_current_project_trace() ), true, null, true );
		echo '</select> ';
		echo '<input type="submit" class="button" value="' . lang_get( 'switch' ) . '" />';
		echo '</fieldset>';
		echo '</form>';
		echo '</div>';
	} else {
		# User has only one project, set it as both current and default
		if( ALL_PROJECTS == helper_get_current_project() ) {
			helper_set_current_project( $t_project_id );

			if( !current_user_is_protected() ) {
				current_user_set_default_project( $t_project_id );
			}

			# Force reload of current page, except if we got here after
			# creating the first project
			$t_redirect_url = str_replace( config_get( 'short_path' ), '', $_SERVER['REQUEST_URI'] );
			if( 'manage_proj_create.php' != $t_redirect_url ) {
				html_meta_redirect( $t_redirect_url, 0, false );
			}
		}
	}

	# Current time
	echo '<div id="current-time">' . $t_now . '</div>';
}

/**
 * (11) Print a user-defined banner at the bottom of the page if there is one.
 * @return void
 */
function html_bottom_banner() {
	$t_page = config_get( 'bottom_include_page' );

	if( !is_blank( $t_page ) && file_exists( $t_page ) && !is_dir( $t_page ) ) {
		include( $t_page );
	}
}

/**
 * A function that outputs that an operation was successful and provides a redirect link.
 * @param string $p_redirect_url The url to redirect to.
 * @param string $p_message      Message to display to the user.
 * @return void
 */
function html_operation_successful( $p_redirect_url, $p_message = '' ) {
	echo '<div class="success-msg">';

	if( !is_blank( $p_message ) ) {
		echo $p_message . '<br />';
	}

	echo lang_get( 'operation_successful' ).'<br />';
	print_bracket_link( $p_redirect_url, lang_get( 'proceed' ) );
	echo '</div>';
}

/**
 * (13) Print the page footer information
 * @return void
 */
function html_footer() {
	global $g_queries_array, $g_request_time;

	# If a user is logged in, update their last visit time.
	# We do this at the end of the page so that:
	#  1) we can display the user's last visit time on a page before updating it
	#  2) we don't invalidate the user cache immediately after fetching it
	#  3) don't do this on the password verification or update page, as it causes the
	#    verification comparison to fail
	if( auth_is_user_authenticated() && !current_user_is_anonymous() && !( is_page_name( 'verify.php' ) || is_page_name( 'account_update.php' ) ) ) {
		$t_user_id = auth_get_current_user_id();
		user_update_last_visit( $t_user_id );
	}

	echo '<div id="footer">' . "\n";
	echo '<hr />' . "\n";

	# We don't have a button anymore, so for now we will only show the resized
	# version of the logo when not on login page.
	if( !is_page_name( 'login_page' ) ) {
		echo "\t" . '<div id="powered-by-mantisbt-logo">' . "\n";
		$t_mantisbt_logo_url = helper_mantis_url( 'images/mantis_logo.png' );
		echo "\t\t" . '<a href="http://www.mantisbt.org"
			title="Mantis Bug Tracker: a free and open source web based bug tracking system.">
			<img src="' . $t_mantisbt_logo_url . '" width="102" height="35" 
				alt="Powered by Mantis Bug Tracker: a free and open source web based bug tracking system." />
			</a>' . "\n";
		echo "\t" . '</div>' . "\n";
	}

	# Show MantisBT version and copyright statement
	$t_version_suffix = '';
	$t_copyright_years = ' 2000 - ' . date( 'Y' );
	if( config_get( 'show_version' ) == ON ) {
		$t_version_suffix = ' ' . htmlentities( MANTIS_VERSION . config_get_global( 'version_suffix' ) );
	}

	echo '<address id="mantisbt-copyright">' . "\n";
	echo '<address id="version">Powered by <a href="http://www.mantisbt.org" title="bug tracking software">MantisBT ' . $t_version_suffix . "</a></address>\n";
	echo 'Copyright &copy;' . $t_copyright_years . ' MantisBT Team';

	# Show optional user-specified custom copyright statement
	$t_copyright_statement = config_get( 'copyright_statement' );
	if( $t_copyright_statement ) {
		echo "\t" . '<address id="user-copyright">' . $t_copyright_statement . '</address>' . "\n";
	}

	echo '</address>' . "\n";

	# Show contact information
	if( !is_page_name( 'login_page' ) ) {
		$t_webmaster_email = config_get( 'webmaster_email' );
		if( !is_blank( $t_webmaster_email ) ) {
			$t_webmaster_contact_information = sprintf( lang_get( 'webmaster_contact_information' ), string_html_specialchars( $t_webmaster_email ) );
			echo "\t" . '<address id="webmaster-contact-information">' . $t_webmaster_contact_information . '</address>' . "\n";
		}
	}

	event_signal( 'EVENT_LAYOUT_PAGE_FOOTER' );

	# Print horizontal rule if any debugging statistics follow
	if( config_get( 'show_timer' ) || config_get( 'show_memory_usage' ) || config_get( 'show_queries_count' ) ) {
		echo "\t" . '<hr />' . "\n";
	}

	# Print the page execution time
	if( config_get( 'show_timer' ) ) {
		$t_page_execution_time = sprintf( lang_get( 'page_execution_time' ), number_format( microtime( true ) - $g_request_time, 4 ) );
		echo "\t" . '<p id="page-execution-time">' . $t_page_execution_time . '</p>' . "\n";
	}

	# Print the page memory usage
	if( config_get( 'show_memory_usage' ) ) {
		$t_page_memory_usage = sprintf( lang_get( 'memory_usage_in_kb' ), number_format( memory_get_peak_usage() / 1024 ) );
		echo "\t" . '<p id="page-memory-usage">' . $t_page_memory_usage . '</p>' . "\n";
	}

	# Determine number of unique queries executed
	if( config_get( 'show_queries_count' ) ) {
		$t_total_queries_count = count( $g_queries_array );
		$t_unique_queries_count = 0;
		$t_total_query_execution_time = 0;
		$t_unique_queries = array();
		for( $i = 0; $i < $t_total_queries_count; $i++ ) {
			if( !in_array( $g_queries_array[$i][0], $t_unique_queries ) ) {
				$t_unique_queries_count++;
				$g_queries_array[$i][3] = false;
				array_push( $t_unique_queries, $g_queries_array[$i][0] );
			} else {
				$g_queries_array[$i][3] = true;
			}
			$t_total_query_execution_time += $g_queries_array[$i][1];
		}

		$t_total_queries_executed = sprintf( lang_get( 'total_queries_executed' ), $t_total_queries_count );
		echo "\t" . '<p id="total-queries-count">' . $t_total_queries_executed . '</p>' . "\n";
		if( config_get_global( 'db_log_queries' ) ) {
			$t_unique_queries_executed = sprintf( lang_get( 'unique_queries_executed' ), $t_unique_queries_count );
			echo "\t" . '<p id="unique-queries-count">' . $t_unique_queries_executed . '</p>' . "\n";
		}
		$t_total_query_time = sprintf( lang_get( 'total_query_execution_time' ), $t_total_query_execution_time );
		echo "\t" . '<p id="total-query-execution-time">' . $t_total_query_time . '</p>' . "\n";
	}

	# Print table of log events
	log_print_to_page();

	echo '</div>' . "\n";
}

/**
 * (14) End the <body> section
 * @return void
 */
function html_body_end() {
	event_signal( 'EVENT_LAYOUT_BODY_END' );

	echo '</div>', "\n";

	echo '</body>', "\n";
}

/**
 * (15) Print the closing <html> tag
 * @return void
 */
function html_end() {
	global $g_email_stored;

	echo '</html>', "\n";

	if( $g_email_stored == true ) {
		if( function_exists( 'fastcgi_finish_request' ) ) {
			fastcgi_finish_request();
		}
		email_send_all();
	}
}

function print_bug_jump() {
	# Bug Jump form
	echo '<div id="bug-jump" >';
	echo '<form method="post" class="bug-jump-form" action="' . helper_mantis_url( 'jump_to_bug.php' ) . '">';
	echo '<fieldset class="bug-jump">';
	# CSRF protection not required here - form does not result in modifications
	echo '<input type="hidden" name="bug_label" value="' . lang_get( 'issue_id' ) . '" />';
	echo '<input type="text" name="bug_id" size="8" />&#160;';
	echo '<input type="submit" value="' . lang_get( 'jump' ) . '" />&#160;';
	echo '</fieldset>';
	echo '</form>';
	echo '</div>' . "\n";
}

/**
 * Print the menu bar with a list of projects to which the user has access
 * @return void
 */
function print_project_menu_bar() {
	$t_project_ids = current_user_get_accessible_projects();

	echo '<table class="width100" cellspacing="0">';
	echo '<tr>';
	echo '<td class="menu">';
	echo '<a href="' . helper_mantis_url( 'set_project.php?project_id=' . ALL_PROJECTS ) . '">' . lang_get( 'all_projects' ) . '</a>';

	foreach( $t_project_ids as $t_id ) {
		echo ' | <a href="' . helper_mantis_url( 'set_project.php?project_id=' . $t_id ) . '">' . string_html_specialchars( project_get_field( $t_id, 'name' ) ) . '</a>';
		print_subproject_menu_bar( $t_id, $t_id . ';' );
	}

	echo '</td>';
	echo '</tr>';
	echo '</table>';
}

/**
 * Print the menu bar with a list of projects to which the user has access
 * @todo check parents param - set_project.php?project_id=' . $p_parents . $t_subproject
 * @param integer $p_project_id A Project id.
 * @param string  $p_parents    Parent project identifiers.
 * @return void
 */
function print_subproject_menu_bar( $p_project_id, $p_parents = '' ) {
	$t_subprojects = current_user_get_accessible_subprojects( $p_project_id );
	$t_char = ':';
	foreach( $t_subprojects as $t_subproject ) {
		echo $t_char . ' <a href="' . helper_mantis_url( 'set_project.php?project_id=' . $p_parents . $t_subproject ) . '">' . string_html_specialchars( project_get_field( $t_subproject, 'name' ) ) . '</a>';
		print_subproject_menu_bar( $t_subproject, $p_parents . $t_subproject . ';' );
		$t_char = ',';
	}
}

/**
 * Print the color legend for the status colors
 * @return void
 */
function html_status_legend() {
	# Don't show the legend if only one status is selected by the current filter
	$t_current_filter = current_user_get_bug_filter();
	if( $t_current_filter === false ) {
		$t_current_filter = filter_get_default();
	}
	$t_simple_filter = $t_current_filter['_view_type'] == 'simple';
	if( $t_simple_filter ) {
		if( !filter_field_is_any( $t_current_filter[FILTER_PROPERTY_STATUS][0] ) ) {
			return;
		}
	}

	$t_status_array = MantisEnum::getAssocArrayIndexedByValues( config_get( 'status_enum_string' ) );
	$t_status_names = MantisEnum::getAssocArrayIndexedByValues( lang_get( 'status_enum_string' ) );

	# read through the list and eliminate unused ones for the selected project
	# assumes that all status are are in the enum array
	$t_workflow = config_get( 'status_enum_workflow' );
	if( !empty( $t_workflow ) ) {
		foreach( $t_status_array as $t_status => $t_name ) {
			if( !isset( $t_workflow[$t_status] ) ) {

				# drop elements that are not in the workflow
				unset( $t_status_array[$t_status] );
			}
		}
	}

	# Remove status values that won't appear as a result of the current filter
	foreach( $t_status_array as $t_status => $t_name ) {
		if( $t_simple_filter ) {
			if( !filter_field_is_none( $t_current_filter[FILTER_PROPERTY_HIDE_STATUS][0] ) &&
				$t_status >= $t_current_filter[FILTER_PROPERTY_HIDE_STATUS][0] ) {
				unset( $t_status_array[$t_status] );
			}
		} else {
			if( !in_array( META_FILTER_ANY, $t_current_filter[FILTER_PROPERTY_STATUS] ) &&
				!in_array( $t_status, $t_current_filter[FILTER_PROPERTY_STATUS] ) ) {
				unset( $t_status_array[$t_status] );
			}
		}
	}

	# If there aren't at least two statuses showable by the current filter,
	# don't draw the status bar
	if( count( $t_status_array ) <= 1 ) {
		return;
	}

	echo '<br />';
	echo '<table class="status-legend width100" cellspacing="1">';
	echo '<tr>';

	# draw the status bar
	$t_status_enum_string = config_get( 'status_enum_string' );
	foreach( $t_status_array as $t_status => $t_name ) {
		$t_val = isset( $t_status_names[$t_status] ) ? $t_status_names[$t_status] : $t_status_array[$t_status];
		$t_status_label = MantisEnum::getLabel( $t_status_enum_string, $t_status );

		echo '<td class="small-caption ' . $t_status_label . '-color">' . $t_val . '</td>';
	}

	echo '</tr>';
	echo '</table>';
	if( ON == config_get( 'status_percentage_legend' ) ) {
		html_status_percentage_legend();
	}
}

/**
 * Print the legend for the status percentage
 * @return void
 */
function html_status_percentage_legend() {
	$t_status_percents = get_percentage_by_status();
	$t_status_enum_string = config_get( 'status_enum_string' );
	$t_enum_values = MantisEnum::getValues( $t_status_enum_string );
	$t_enum_count = count( $t_enum_values );

	$t_bug_count = array_sum( $t_status_percents );

	if( $t_bug_count > 0 ) {
		echo '<br />';
		echo '<table class="width100" cellspacing="1">';
		echo '<tr>';
		echo '<td class="form-title" colspan="' . $t_enum_count . '">' . lang_get( 'issue_status_percentage' ) . '</td>';
		echo '</tr>';
		echo '<tr>';

		foreach ( $t_enum_values as $t_status ) {
			$t_percent = ( isset( $t_status_percents[$t_status] ) ?  $t_status_percents[$t_status] : 0 );

			if( $t_percent > 0 ) {
				$t_status_label = MantisEnum::getLabel( $t_status_enum_string, $t_status );
				echo '<td class="small-caption-center ' . $t_status_label . '-color ' . $t_status_label . '-percentage">' . $t_percent . '%</td>';
			}
		}

		echo '</tr>';
		echo '</table>';
	}
}

/**
 * Print an html button inside a form
 * @param string $p_action      Form Action.
 * @param string $p_button_text Button Text.
 * @param array  $p_fields      An array of hidden fields to include on the form.
 * @param string $p_method      Form submit method - default post.
 * @return void
 */
function html_button( $p_action, $p_button_text, array $p_fields = array(), $p_method = 'post' ) {
	$t_form_name = explode( '.php', $p_action, 2 );
	$p_action = urlencode( $p_action );
	$p_button_text = string_attribute( $p_button_text );

	if( strtolower( $p_method ) == 'get' ) {
		$t_method = 'get';
	} else {
		$t_method = 'post';
	}

	echo '<form method="' . $t_method . '" action="' . $p_action . '" class="action-button">' . "\n";
	echo "\t" . '<fieldset>';
	# Add a CSRF token only when the form is being sent via the POST method
	if( $t_method == 'post' ) {
		echo form_security_field( $t_form_name[0] );
	}

	foreach( $p_fields as $t_key => $t_val ) {
		$t_key = string_attribute( $t_key );
		$t_val = string_attribute( $t_val );

		echo "\t\t" . '<input type="hidden" name="' . $t_key . '" value="' . $t_val . '" />' . "\n";
	}

	echo "\t\t" . '<input type="submit" class="button" value="' . $p_button_text . '" />' . "\n";
	echo "\t" . '</fieldset>';
	echo '</form>' . "\n";
}

/**
 * Print a button to update the given bug
 * @param integer $p_bug_id A Bug identifier.
 * @return void
 */
function html_button_bug_update( $p_bug_id ) {
	if( access_has_bug_level( config_get( 'update_bug_threshold' ), $p_bug_id ) ) {
		html_button( string_get_bug_update_page(), lang_get( 'update_bug_button' ), array( 'bug_id' => $p_bug_id ) );
	}
}

/**
 * Print Change Status to: button
 * This code is similar to print_status_option_list except
 * there is no masking, except for the current state
 *
 * @param BugData $p_bug A valid bug object.
 * @return void
 */
function html_button_bug_change_status( BugData $p_bug ) {
	$t_current_access = access_get_project_level( $p_bug->project_id );

	# User must have rights to change status to use this button
	if( !access_has_bug_level( config_get( 'update_bug_status_threshold' ), $p_bug->id ) ) {
		return;
	}

	$t_enum_list = get_status_option_list(
		$t_current_access,
		$p_bug->status,
		false,
		# Add close if user is bug's reporter, still has rights to report issues
		# (to prevent users downgraded to viewers from updating issues) and
		# reporters are allowed to close their own issues
		(  bug_is_user_reporter( $p_bug->id, auth_get_current_user_id() )
		&& access_has_bug_level( config_get( 'report_bug_threshold' ), $p_bug->id )
		&& ON == config_get( 'allow_reporter_close' )
		),
		$p_bug->project_id );

	if( count( $t_enum_list ) > 0 ) {
		# resort the list into ascending order after noting the key from the first element (the default)
		$t_default_arr = each( $t_enum_list );
		$t_default = $t_default_arr['key'];
		ksort( $t_enum_list );
		reset( $t_enum_list );

		echo '<form method="post" action="bug_change_status_page.php">';
		# CSRF protection not required here - form does not result in modifications

		$t_button_text = lang_get( 'bug_status_to_button' );
		echo '<input type="submit" class="button" value="' . $t_button_text . '" />';

		echo ' <select name="new_status">';

		# space at beginning of line is important
		foreach( $t_enum_list as $t_key => $t_val ) {
			echo '<option value="' . $t_key . '" ';
			check_selected( $t_key, $t_default );
			echo '>' . $t_val . '</option>';
		}
		echo '</select>';

		$t_bug_id = string_attribute( $p_bug->id );
		echo '<input type="hidden" name="id" value="' . $t_bug_id . '" />' . "\n";

		echo '</form>' . "\n";
	}
}

/**
 * Print Assign To: combo box of possible handlers
 * @param BugData $p_bug Bug object.
 * @return void
 */
function html_button_bug_assign_to( BugData $p_bug ) {
	# make sure status is allowed of assign would cause auto-set-status
	# workflow implementation
	if( ON == config_get( 'auto_set_status_to_assigned' )
		&& !bug_check_workflow( $p_bug->status, config_get( 'bug_assigned_status' ) )
	) {
		return;
	}

	# make sure current user has access to modify bugs.
	if( !access_has_bug_level( config_get( 'update_bug_assign_threshold', config_get( 'update_bug_threshold' ) ), $p_bug->id ) ) {
		return;
	}

	$t_current_user_id = auth_get_current_user_id();
	$t_options = array();
	$t_default_assign_to = null;

	if( ( $p_bug->handler_id != $t_current_user_id )
		&& access_has_bug_level( config_get( 'handle_bug_threshold' ), $p_bug->id, $t_current_user_id )
	) {
		$t_options[] = array(
			$t_current_user_id,
			'[' . lang_get( 'myself' ) . ']',
		);
		$t_default_assign_to = $t_current_user_id;
	}

	if( ( $p_bug->handler_id != $p_bug->reporter_id )
		&& user_exists( $p_bug->reporter_id )
		&& access_has_bug_level( config_get( 'handle_bug_threshold' ), $p_bug->id, $p_bug->reporter_id )
	) {
		$t_options[] = array(
			$p_bug->reporter_id,
			'[' . lang_get( 'reporter' ) . ']',
		);

		if( $t_default_assign_to === null ) {
			$t_default_assign_to = $p_bug->reporter_id;
		}
	}

	echo '<form method="post" action="bug_update.php">';
	echo form_security_field( 'bug_update' );
	echo '<input type="hidden" name="last_updated" value="' . $p_bug->last_updated . '" />';

	$t_button_text = lang_get( 'bug_assign_to_button' );
	echo '<input type="submit" class="button" value="' . $t_button_text . '" />';

	echo ' <select name="handler_id">';

	# space at beginning of line is important

	$t_already_selected = false;

	foreach( $t_options as $t_entry ) {
		$t_id = (int)$t_entry[0];
		$t_caption = string_attribute( $t_entry[1] );

		# if current user and reporter can't be selected, then select the first
		# user in the list.
		if( $t_default_assign_to === null ) {
			$t_default_assign_to = $t_id;
		}

		echo '<option value="' . $t_id . '" ';

		if( ( $t_id == $t_default_assign_to ) && !$t_already_selected ) {
			check_selected( $t_id, $t_default_assign_to );
			$t_already_selected = true;
		}

		echo '>' . $t_caption . '</option>';
	}

	# allow un-assigning if already assigned.
	if( $p_bug->handler_id != 0 ) {
		echo '<option value="0"></option>';
	}

	# 0 means currently selected
	print_assign_to_option_list( 0, $p_bug->project_id );
	echo '</select>';

	$t_bug_id = string_attribute( $p_bug->id );
	echo '<input type="hidden" name="bug_id" value="' . $t_bug_id . '" />' . "\n";

	echo '</form>' . "\n";
}

/**
 * Print a button to move the given bug to a different project
 * @param integer $p_bug_id A valid bug identifier.
 * @return void
 */
function html_button_bug_move( $p_bug_id ) {
	if( access_has_bug_level( config_get( 'move_bug_threshold' ), $p_bug_id ) ) {
		html_button( 'bug_actiongroup_page.php', lang_get( 'move_bug_button' ), array( 'bug_arr[]' => $p_bug_id, 'action' => 'MOVE' ) );
	}
}

/**
 * Print a button to clone the given bug
 * @param integer $p_bug_id A valid bug identifier.
 * @return void
 */
function html_button_bug_create_child( $p_bug_id ) {
	if( access_has_bug_level( config_get( 'report_bug_threshold' ), $p_bug_id ) ) {
		html_button( string_get_bug_report_url(), lang_get( 'create_child_bug_button' ), array( 'm_id' => $p_bug_id ) );
	}
}

/**
 * Print a button to reopen the given bug
 * @param BugData $p_bug A valid bug object.
 * @return void
 */
function html_button_bug_reopen( BugData $p_bug ) {
	if( access_can_reopen_bug( $p_bug ) ) {
		$t_reopen_status = config_get( 'bug_reopen_status', null, null, $p_bug->project_id );
		html_button(
			'bug_change_status_page.php',
			lang_get( 'reopen_bug_button' ),
			array( 'id' => $p_bug->id, 'new_status' => $t_reopen_status, 'reopen_flag' => ON ) );
	}
}

/**
 * Print a button to close the given bug
 * Only if user can close bugs and workflow allows moving them to that status
 * @param BugData $p_bug A valid bug object.
 * @return void
 */
function html_button_bug_close( BugData $p_bug ) {
	$t_closed_status = config_get( 'bug_closed_status_threshold', null, null, $p_bug->project_id );
	if( access_can_close_bug( $p_bug )
		&& bug_check_workflow( $p_bug->status, $t_closed_status )
	) {
		html_button(
			'bug_change_status_page.php',
			lang_get( 'close_bug_button' ),
			array( 'id' => $p_bug->id, 'new_status' => $t_closed_status ) );
	}
}

/**
 * Print a button to monitor the given bug
 * @param integer $p_bug_id A valid bug identifier.
 * @return void
 */
function html_button_bug_monitor( $p_bug_id ) {
	if( access_has_bug_level( config_get( 'monitor_bug_threshold' ), $p_bug_id ) ) {
		html_button( 'bug_monitor_add.php', lang_get( 'monitor_bug_button' ), array( 'bug_id' => $p_bug_id ) );
	}
}

/**
 * Print a button to unmonitor the given bug
 * no reason to ever disallow someone from unmonitoring a bug
 * @param integer $p_bug_id A valid bug identifier.
 * @return void
 */
function html_button_bug_unmonitor( $p_bug_id ) {
	html_button( 'bug_monitor_delete.php', lang_get( 'unmonitor_bug_button' ), array( 'bug_id' => $p_bug_id ) );
}

/**
 * Print a button to stick the given bug
 * @param integer $p_bug_id A valid bug identifier.
 * @return void
 */
function html_button_bug_stick( $p_bug_id ) {
	if( access_has_bug_level( config_get( 'set_bug_sticky_threshold' ), $p_bug_id ) ) {
		html_button( 'bug_stick.php', lang_get( 'stick_bug_button' ), array( 'bug_id' => $p_bug_id, 'action' => 'stick' ) );
	}
}

/**
 * Print a button to unstick the given bug
 * @param integer $p_bug_id A valid bug identifier.
 * @return void
 */
function html_button_bug_unstick( $p_bug_id ) {
	if( access_has_bug_level( config_get( 'set_bug_sticky_threshold' ), $p_bug_id ) ) {
		html_button( 'bug_stick.php', lang_get( 'unstick_bug_button' ), array( 'bug_id' => $p_bug_id, 'action' => 'unstick' ) );
	}
}

/**
 * Print a button to delete the given bug
 * @param integer $p_bug_id A valid bug identifier.
 * @return void
 */
function html_button_bug_delete( $p_bug_id ) {
	if( access_has_bug_level( config_get( 'delete_bug_threshold' ), $p_bug_id ) ) {
		html_button( 'bug_actiongroup_page.php', lang_get( 'delete_bug_button' ), array( 'bug_arr[]' => $p_bug_id, 'action' => 'DELETE' ) );
	}
}

/**
 * Print a button to create a wiki page
 * @param integer $p_bug_id A valid bug identifier.
 * @return void
 */
function html_button_wiki( $p_bug_id ) {
	if( config_get_global( 'wiki_enable' ) == ON ) {
		if( access_has_bug_level( config_get( 'update_bug_threshold' ), $p_bug_id ) ) {
			html_button( 'wiki.php', lang_get_defaulted( 'Wiki' ), array( 'id' => $p_bug_id, 'type' => 'issue' ), 'get' );
		}
	}
}

/**
 * Print all buttons for view bug pages
 * @param integer $p_bug_id A valid bug identifier.
 * @return void
 */
function html_buttons_view_bug_page( $p_bug_id ) {
	$t_readonly = bug_is_readonly( $p_bug_id );
	$t_sticky = config_get( 'set_bug_sticky_threshold' );

	$t_bug = bug_get( $p_bug_id );

	echo '<table><tr class="vcenter">';
	if( !$t_readonly ) {
		# UPDATE button
		echo '<td class="center">';
		html_button_bug_update( $p_bug_id );
		echo '</td>';

		# ASSIGN button
		echo '<td class="center">';
		html_button_bug_assign_to( $t_bug );
		echo '</td>';
	}

	# Change status button/dropdown
	if( !$t_readonly ) {
		echo '<td class="center">';
		html_button_bug_change_status( $t_bug );
		echo '</td>';
	}

	# MONITOR/UNMONITOR button
	if( !current_user_is_anonymous() ) {
		echo '<td class="center">';
		if( user_is_monitoring_bug( auth_get_current_user_id(), $p_bug_id ) ) {
			html_button_bug_unmonitor( $p_bug_id );
		} else {
			html_button_bug_monitor( $p_bug_id );
		}
		echo '</td>';
	}

	# STICK/UNSTICK button
	if( access_has_bug_level( $t_sticky, $p_bug_id ) ) {
		echo '<td class="center">';
		if( !bug_get_field( $p_bug_id, 'sticky' ) ) {
			html_button_bug_stick( $p_bug_id );
		} else {
			html_button_bug_unstick( $p_bug_id );
		}
		echo '</td>';
	}

	# CLONE button
	if( !$t_readonly ) {
		echo '<td class="center">';
		html_button_bug_create_child( $p_bug_id );
		echo '</td>';
	}

	# REOPEN button
	echo '<td class="center">';
	html_button_bug_reopen( $t_bug );
	echo '</td>';

	# CLOSE button
	echo '<td class="center">';
	html_button_bug_close( $t_bug );
	echo '</td>';

	# MOVE button
	echo '<td class="center">';
	html_button_bug_move( $p_bug_id );
	echo '</td>';

	# DELETE button
	echo '<td class="center">';
	html_button_bug_delete( $p_bug_id );
	echo '</td>';

	helper_call_custom_function( 'print_bug_view_page_custom_buttons', array( $p_bug_id ) );

	echo '</tr></table>';
}

/**
 * get the css class name for the given status, user and project
 * @param integer $p_status  An enumeration value.
 * @param integer $p_user    A valid user identifier.
 * @param integer $p_project A valid project identifier.
 * @return string
 *
 * @todo This does not work properly when displaying issues from a project other
 * than then current one, if the other project has custom status or colors.
 * This is due to the dynamic css for color coding (css/status_config.php).
 * Build CSS including project or even user-specific colors ?
 */
function html_get_status_css_class( $p_status, $p_user = null, $p_project = null ) {
	return string_attribute( MantisEnum::getLabel( config_get( 'status_enum_string', null, $p_user, $p_project ), $p_status ) . '-color' );
}
