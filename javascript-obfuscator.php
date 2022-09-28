<?php
/*
Plugin Name: Javascript Obfuscator
Plugin URI : https://github.com/sajjadh47/javascript-obfuscator
Description: Obfuscate your JavaScript Source Code to enable anti-theft protection by converting your js source code into completely unreadable form & preventing it from analyzing and reusing.
Version: 1.0.1
Author: Sajjad Hossain Sagor
Author URI: https://profiles.wordpress.org/sajjad67
Text Domain: javascript-obfuscator
Domain Path: /languages

License: GPL2
This WordPress Plugin is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
any later version.

This free software is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this software. If not, see http://www.gnu.org/licenses/gpl-2.0.html.
*/

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// ---------------------------------------------------------
// Define Plugin Folders Path
// ---------------------------------------------------------
define( "WPJSOBFUSCATE_PLUGIN_PATH", plugin_dir_path( __FILE__ ) );
define( "WPJSOBFUSCATE_PLUGIN_URL", plugin_dir_url( __FILE__ ) );

// Create cache directory on plugin activation
register_activation_hook( __FILE__, 'wp_js_obfuscate_create_directory' );

function wp_js_obfuscate_create_directory()
{
    $upload_dir = WP_JS_OBFUSCATOR::get_cache_dir( true );
    
    // check if upload directory is writable...
    if ( is_writable( $upload_dir ) )
    {
        if ( ! is_dir( $upload_dir . '/obfuscated_scripts' ) )
        {
            mkdir( $upload_dir . '/obfuscated_scripts', 0700 );
        }
    }
    else
    {
        // is this plugin active?
        if ( is_plugin_active( plugin_basename( __FILE__ ) ) )
        {
            // deactivate the plugin
            deactivate_plugins( plugin_basename( __FILE__ ) );
            
            // unset activation notice
            unset( $_GET[ 'activate' ] );
            
            // display notice
            add_action( 'admin_notices', function()
            {
                echo '<div class="error notice is-dismissible">';
                    echo __( 'Upload Directory is not writable! Please make it writable to store cache files.', 'javascript-obfuscator' );
                echo '</div>';
            });
        }
    }
}

// add plugin settings to admin menu
require WPJSOBFUSCATE_PLUGIN_PATH . 'admin/admin_settings.php';

/**
* Obsfucate Provided Script
*/
class WP_JS_OBFUSCATOR
{
    /**
     * Obsfucate the provided source code
     *
     * @return string source code
     */
    public function run( $source_code = '' )
    {
        // add the obfuscating library [https://github.com/tholu/php-packer]
        require WPJSOBFUSCATE_PLUGIN_PATH . 'includes/library/vendor/autoload.php';

        if ( $source_code == '' ) return '';

        $mode = WPJSOBFUSCATE_SETTINGS::get_option( 'obfuscate_mode', 'jsobfuscate_basic_settings', 'Normal' );

        $Obfuscate = new Tholu\Packer\Packer( $source_code, $mode, true, false, true );

        return $Obfuscate->pack();
    }

    /**
     * Save obfuscated source code
     *
     * @return boolean
     */
    public function save( $code, $filename )
    {
        // get cache folder
        $cache_folder = self::get_cache_dir();

        file_put_contents( $cache_folder . '/' . $filename , $code );
    }

    /**
     * Clear Cache
     *
     * @return boolean
     */
    static public function clear()
    {
        // get cache folder
        $cache_folder = self::get_cache_dir();

        // check if cache directory exists
        if ( is_dir( $cache_folder ) )
        {
            // get all files from that folder
            $files = glob( $cache_folder . '/*' ); // get all file names
        
            foreach( $files as $file )
            {
                // iterate files
                if( is_file( $file ) )
                
                unlink( $file ); // delete file
            }
        }
    }

    /**
     * Clear Cache
     *
     * @return boolean
     */
    static public function get_cache_dir( $base_dir_only = false, $dir = 'basedir' )
    {
        $upload = wp_upload_dir();
    
        $upload_dir = $upload[$dir];

        $dir = $upload_dir . '/obfuscated_scripts';

        if ( ! file_exists( $dir ) )
        {
            mkdir( $dir );
        }

        return $dir;
    }
}

add_action( 'wp_enqueue_scripts', function()
{
    $enable_obfuscate = WPJSOBFUSCATE_SETTINGS::get_option( 'enable_obfuscate', 'jsobfuscate_basic_settings', 'off' );

    // obsfucating is not enabled so nothing to do here...
    if ( $enable_obfuscate == 'off' ) return;

    $excluded_scripts = WPJSOBFUSCATE_SETTINGS::get_option( 'exclude_obfuscate', 'jsobfuscate_basic_settings', '' );

    $included_scripts = WPJSOBFUSCATE_SETTINGS::get_option( 'include_obfuscate', 'jsobfuscate_basic_settings', '' );

    $cache_dir = WP_JS_OBFUSCATOR::get_cache_dir();
    
    $cache_dir_url = isset( $_SERVER['HTTPS'] ) && $_SERVER['HTTPS'] == 'on' ? preg_replace( "/^http:/i", "https:", WP_JS_OBFUSCATOR::get_cache_dir( true, 'baseurl' ) ) : WP_JS_OBFUSCATOR::get_cache_dir( true, 'baseurl' );
    
    global $wp_scripts;

    if( false != $wp_scripts->queue )
    {
        foreach( $wp_scripts->queue as $handle )
        {
            // get the script version
            $version = $wp_scripts->registered[$handle]->ver;

            // get script dependencies
            $deps = $wp_scripts->registered[$handle]->deps;

            // get script src
            $script_src = $wp_scripts->registered[$handle]->src;

            // if script is built in wp don't touch it
            $built_in_script = preg_match_all( '/(\/wp-includes\/)|(\/wp-admin\/)/', $script_src, $matches );

            if ( $built_in_script === 1 ) continue;

            // get script file name
            $filename = basename( $script_src );

            // check if include files is not empty
            if ( $included_scripts !== '' )
            {    
                // get all comma separated file list
                $included_scripts_file_list = explode( ',', $included_scripts );

                // check if any valid comma separated file exists
                if ( ! empty( $included_scripts_file_list ) )
                {    
                    // if not included don't obfuscate
                    if ( ! in_array( $filename , $included_scripts_file_list ) ) continue;
                }
            }
            else
            {
                if ( $excluded_scripts !== '' )
                {    
                    // get all comma separated file list
                    $excluded_obfuscate_file_list = explode( ',', $excluded_scripts );

                    // check if any valid comma separated file exists
                    if ( ! empty( $excluded_obfuscate_file_list ) )
                    {    
                        // if not excluded don't obfuscate
                        if ( in_array( $filename , $excluded_obfuscate_file_list ) ) continue;
                    }
                }
            }

            // check if file is already generated... if so load cache file
            if ( ! file_exists( $cache_dir .'/'. $filename ) )
            {    
                // get script file content
                $source_code = file_get_contents( $script_src );

                $obfuscate_obj = new WP_JS_OBFUSCATOR;

                $obfuscated_content = $obfuscate_obj->run( $source_code );

                $obfuscate_obj->save( $obfuscated_content, $filename );
            }

            // remove the script from loading
            wp_dequeue_script( $handle );
            
            wp_deregister_script( $handle );

            // load onfuscated script from cache folder
            wp_enqueue_script( $handle, $cache_dir_url . '/' . $filename, $deps, $version, true );
        }
    }

}, 101 );

// clear cache if requested
add_action( 'admin_init', function()
{
    global $pagenow;
    
    // check if it is plugin option page & requested for clearing cache...
    if ( $pagenow == 'options-general.php' && isset( $_GET['page'] ) && $_GET['page'] == 'javascript-obfuscate'  && isset( $_GET['clear_cache'] ) && $_GET['clear_cache'] == 'yes' )
    {
        WP_JS_OBFUSCATOR::clear();

        // display notice
        add_action( 'admin_notices', function()
        {
            echo '<div class="notice notice-success is-dismissible" style="padding: 10px 12px;">';
                echo __( 'Obsfucated Files Successfully Deleted! New Cache Files Will Be Generated Soon if Enabled!', 'javascript-obfuscator' );
            echo '</div>';
        });
    }
});

/**
 * Add Go To Settings Page in Plugin List Table
 *
 */
add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), function( $links )
{    
    $plugin_actions = array();
        
    $plugin_actions[] = sprintf( '<a href="%s">%s</a>', admin_url( 'options-general.php?page=javascript-obfuscate' ), __( 'Settings', 'javascript-obfuscator' ) );

    return array_merge( $links, $plugin_actions );
});
