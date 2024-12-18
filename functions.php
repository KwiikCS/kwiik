<?php
/**
 * @package BuddyBoss Child
 * The parent theme functions are located at /buddyboss-theme/inc/theme/functions.php
 * Add your own functions at the bottom of this file.
 */


/****************************** THEME SETUP ******************************/

/**
 * Sets up theme for translation
 *
 * @since BuddyBoss Child 1.0.0
 */
function buddyboss_theme_child_languages()
{
  /**
   * Makes child theme available for translation.
   * Translations can be added into the /languages/ directory.
   */

  // Translate text from the PARENT theme.
  load_theme_textdomain( 'buddyboss-theme', get_stylesheet_directory() . '/languages' );

  // Translate text from the CHILD theme only.
  // Change 'buddyboss-theme' instances in all child theme files to 'buddyboss-theme-child'.
  // load_theme_textdomain( 'buddyboss-theme-child', get_stylesheet_directory() . '/languages' );

}
add_action( 'after_setup_theme', 'buddyboss_theme_child_languages' );

/**
 * Enqueues scripts and styles for child theme front-end.
 *
 * @since Boss Child Theme  1.0.0
 */
function buddyboss_theme_child_scripts_styles()
{
  /**
   * Scripts and Styles loaded by the parent theme can be unloaded if needed
   * using wp_deregister_script or wp_deregister_style.
   *
   * See the WordPress Codex for more information about those functions:
   * http://codex.wordpress.org/Function_Reference/wp_deregister_script
   * http://codex.wordpress.org/Function_Reference/wp_deregister_style
   **/

  // Styles
  wp_enqueue_style( 'buddyboss-child-css', get_stylesheet_directory_uri().'/assets/css/custom.css' );

  // Javascript
  wp_enqueue_script( 'buddyboss-child-js', get_stylesheet_directory_uri().'/assets/js/custom.js' );
}
add_action( 'wp_enqueue_scripts', 'buddyboss_theme_child_scripts_styles', 9999 );


/****************************** CUSTOM FUNCTIONS ******************************/

// Add your own custom functions here

function user_programs_rewrite_rules() {
    add_rewrite_rule('^programme-txt/(.+)$', 'index.php?programme_txt=$matches[1]', 'top');
}
add_action('init', 'user_programs_rewrite_rules');

function user_programs_query_vars($query_vars) {
    $query_vars[] = 'programme_txt';
    return $query_vars;
}
add_filter('query_vars', 'user_programs_query_vars');

function user_programs_template_redirect() {
    $programme_txt = get_query_var('programme_txt');
    if ($programme_txt) {
        $upload_dir = wp_upload_dir();
        $file_path = $upload_dir['basedir'] . '/programme_txt/' . $programme_txt;
        if (file_exists($file_path)) {
            header('Content-Type: text/plain');
            header('Content-Disposition: attachment; filename="' . $programme_txt . '"');
            readfile($file_path);
            exit;
        } else {
            wp_redirect(home_url());
            exit;
        }
    }
}
add_action('template_redirect', 'user_programs_template_redirect');

// Charger les dépendances principales
function init_kwiik_core() {
    wp_enqueue_script('jquery');
    wp_enqueue_script('jspdf', 'https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js', array('jquery'), '2.5.1', true);
}
add_action('wp_enqueue_scripts', 'init_kwiik_core');

// Vérifier si les fichiers existent avant de les inclure
$module_files = array(
    'pdf-generator.php',
    'txt-generator.php',
    'txt-admin.php',
    'email-sender.php',
    'buttons-display.php'
);

foreach ($module_files as $file) {
    $file_path = get_stylesheet_directory() . '/modules/' . $file;
    if (file_exists($file_path)) {
        require_once $file_path;
    }
}

function add_buddyboss_content_styles() {
    ?>
    <style>
        /* Styles pour bb-grid site-content-grid */
        @media (min-width: 1025px) {
            .bb-grid.site-content-grid {
                margin-top: 98px !important;
            }
        }

        @media (max-width: 543px) {
            .bb-grid.site-content-grid {
                margin-left: 68px !important;
                margin-right: 68px !important;
                width: calc(100% - 136px) !important;
            }
        }

        @media (max-width: 1024px) {
            .bb-grid.site-content-grid {
                margin-top: 68px !important;
            }
        }
    </style>
    <?php
}
add_action('wp_head', 'add_buddyboss_content_styles');

// Autoriser les inscriptions
add_filter('pre_option_users_can_register', '__return_true');

// Définir un rôle par défaut pour les nouveaux utilisateurs
add_filter('register_default_role', function() {
    return 'subscriber'; // Ou 'contributor', 'author', etc.
});

?>

