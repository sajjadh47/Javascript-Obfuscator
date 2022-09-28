<?php

/**
 * Category & Taxonomy Settings Class
 *
 * @author Sajjad Hossain Sagor
 */
class WPJSOBFUSCATE_SETTINGS
{
    private $settings_api;

    function __construct()
    {
    	// add settings api wrapper
		require WPJSOBFUSCATE_PLUGIN_PATH . 'includes/class.settings-api.php';
        
        $this->settings_api = new Javascript_Obfuscator_Settings_API;

        add_action( 'admin_init', array( $this, 'admin_init') );
        
        add_action( 'admin_menu', array( $this, 'admin_menu') );
    }

    public function admin_init()
    {
        //set the settings
        $this->settings_api->set_sections( $this->get_settings_sections() );
        
        $this->settings_api->set_fields( $this->get_settings_fields() );

        //initialize settings
        $this->settings_api->admin_init();
    }

    public function admin_menu()
    {
        add_options_page( 'JS Obfuscate', 'JS Obfuscate', 'manage_options', 'javascript-obfuscate', array( $this, 'render_js_obfuscate_settings' ) );
    }

    public function get_settings_sections()
    {   
        $sections = array(
            array(
                'id'    => 'jsobfuscate_basic_settings',
                'title' => __( 'General Settings', 'javascript-obfuscator' )
            )
        );
        
        return $sections;
    }

    /**
     * Returns all the settings fields
     *
     * @return array settings fields
     */
    public function get_settings_fields()
    {
		$settings_fields = array(
            'jsobfuscate_basic_settings' => array(
                array(
                    'name'    => 'enable_obfuscate',
                    'label'   => __( 'Enable Obfuscate', 'javascript-obfuscator' ),
                    'type'    => 'checkbox',
                    'desc'    => __( 'Checking this box will enable obfuscating js files from themes & plugins folders', 'javascript-obfuscator' )
                ),
                array(
                    'name'    => 'exclude_obfuscate',
                    'label'   => __( 'Exclude Files From Obfuscating', 'javascript-obfuscator' ),
                    'type'    => 'text',
                    'desc'    => __( 'Add comma separated js files name to exclude it from obfuscating', 'javascript-obfuscator' ),
                    'placeholder' => __( 'admin-bar.min.js, wp-emoji-release.min.js', 'javascript-obfuscator' )
                ),
                array(
                    'name'    => 'include_obfuscate',
                    'label'   => __( 'Include Files From Obfuscating', 'javascript-obfuscator' ),
                    'type'    => 'text',
                    'desc'    => __( 'Add comma separated js files name to include it while obfuscating... Note if added any! only those files will be obfuscated', 'javascript-obfuscator' ),
                    'placeholder' => __( 'app.js, front-script.min.js', 'javascript-obfuscator' )
                ),
                array(
                    'name'    => 'obfuscate_mode',
                    'label'   => __( 'Obfuscating Mode', 'javascript-obfuscator' ),
                    'type'    => 'select',
                    'options' => array(
                    	'0'  => 'None (Only Minify)',
                    	'10' => 'Numeric',
                    	'62' => 'Normal (Default : Recommended)',
                    	'95' => 'High ASCII (Not Recommended)'
                    ),
                    'default' => '62',
                    'desc'    => __( 'If you have UTF8 characters in your JavaScript, avoid using the "High ASCII" encoding and use "Normal" instead.', 'javascript-obfuscator' ),
                ) 
            )
        );

        return $settings_fields;
    }

    /**
     * Render settings fields
     *
     */

    public function render_js_obfuscate_settings()
    {    
        echo '<div class="wrap">';

	        $this->settings_api->show_navigation();
	       
	        $this->settings_api->show_forms();

        echo '</div>';
    }

    /**
	 * Returns option value
	 *
	 * @return string|array option value
	 */

	static public function get_option( $option, $section, $default = '' )
    {
	    $options = get_option( $section );

	    if ( isset( $options[$option] ) )
        {
	        return $options[$option];
	    }

	    return $default;
	}
}

$wp_js_obfuscate_settings = new WPJSOBFUSCATE_SETTINGS();
