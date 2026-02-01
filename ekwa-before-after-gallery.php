<?php
/**
 * Plugin Name: EKWA Before After Gallery
 * Plugin URI: https://ekwa.com
 * Description: A beautiful before and after gallery with stacked card design for dental and medical practices.
 * Version: 1.0.0
 * Author: EKWA
 * Author URI: https://ekwa.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: ekwa-before-after-gallery
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('EKWA_BAG_VERSION', '1.1.5');
define('EKWA_BAG_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('EKWA_BAG_PLUGIN_URL', plugin_dir_url(__FILE__));

/**
 * Main Plugin Class
 */
class EKWA_Before_After_Gallery {
    
    private static $instance = null;
    
    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->init_hooks();
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Activation/Deactivation hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        // Init hooks
        add_action('init', array($this, 'register_post_type'));
        add_action('init', array($this, 'register_taxonomies'));
        add_action('init', array($this, 'register_shortcode'));
        
        // Admin hooks
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_meta_boxes'));
        
        // Frontend hooks
        add_action('wp_enqueue_scripts', array($this, 'frontend_enqueue_scripts'));
        
        // AJAX hooks
        add_action('wp_ajax_ekwa_bag_get_cases', array($this, 'ajax_get_cases'));
        add_action('wp_ajax_nopriv_ekwa_bag_get_cases', array($this, 'ajax_get_cases'));
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        $this->register_post_type();
        $this->register_taxonomies();
        $this->create_default_categories();
        flush_rewrite_rules();
    }
    
    /**
     * Plugin deactivation
     */
    public function deactivate() {
        flush_rewrite_rules();
    }
    
    /**
     * Register custom post type
     */
    public function register_post_type() {
        $labels = array(
            'name'               => __('Gallery Cases', 'ekwa-before-after-gallery'),
            'singular_name'      => __('Gallery Case', 'ekwa-before-after-gallery'),
            'menu_name'          => __('BA Gallery', 'ekwa-before-after-gallery'),
            'add_new'            => __('Add New Case', 'ekwa-before-after-gallery'),
            'add_new_item'       => __('Add New Case', 'ekwa-before-after-gallery'),
            'edit_item'          => __('Edit Case', 'ekwa-before-after-gallery'),
            'new_item'           => __('New Case', 'ekwa-before-after-gallery'),
            'view_item'          => __('View Case', 'ekwa-before-after-gallery'),
            'search_items'       => __('Search Cases', 'ekwa-before-after-gallery'),
            'not_found'          => __('No cases found', 'ekwa-before-after-gallery'),
            'not_found_in_trash' => __('No cases found in trash', 'ekwa-before-after-gallery'),
        );
        
        $args = array(
            'labels'              => $labels,
            'public'              => false,
            'show_ui'             => true,
            'show_in_menu'        => false,
            'capability_type'     => 'post',
            'hierarchical'        => false,
                'supports'            => array('title', 'editor'),
            'has_archive'         => false,
            'rewrite'             => false,
        );
        
        register_post_type('ekwa_bag_case', $args);
    }
    
    /**
     * Register taxonomies
     */
    public function register_taxonomies() {
        // Main Category
        register_taxonomy('ekwa_bag_category', 'ekwa_bag_case', array(
            'labels' => array(
                'name'          => __('Categories', 'ekwa-before-after-gallery'),
                'singular_name' => __('Category', 'ekwa-before-after-gallery'),
                'add_new_item'  => __('Add New Category', 'ekwa-before-after-gallery'),
            ),
            'hierarchical' => true,
            'show_ui'      => true,
            'show_admin_column' => true,
            'rewrite'      => false,
        ));
    }
    
    /**
     * Create default categories
     */
    private function create_default_categories() {
        $categories = array(
            'cosmetic' => array(
                'name' => 'Cosmetic',
                'children' => array('Whitening', 'Veneers')
            ),
            'restorative' => array(
                'name' => 'Restorative',
                'children' => array('Crowns', 'Implants', 'Dentures')
            ),
            'orthodontic' => array(
                'name' => 'Orthodontic',
                'children' => array('Clear Aligners', 'Braces')
            )
        );
        
        foreach ($categories as $slug => $cat) {
            $parent = term_exists($cat['name'], 'ekwa_bag_category');
            if (!$parent) {
                $parent = wp_insert_term($cat['name'], 'ekwa_bag_category', array('slug' => $slug));
            }
            
            if (!is_wp_error($parent)) {
                $parent_id = is_array($parent) ? $parent['term_id'] : $parent;
                foreach ($cat['children'] as $child) {
                    if (!term_exists($child, 'ekwa_bag_category')) {
                        wp_insert_term($child, 'ekwa_bag_category', array(
                            'parent' => $parent_id
                        ));
                    }
                }
            }
        }
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            __('Before After Gallery', 'ekwa-before-after-gallery'),
            __('BA Gallery', 'ekwa-before-after-gallery'),
            'manage_options',
            'ekwa-bag',
            array($this, 'render_admin_page'),
            'dashicons-images-alt2',
            30
        );
        
        add_submenu_page(
            'ekwa-bag',
            __('All Cases', 'ekwa-before-after-gallery'),
            __('All Cases', 'ekwa-before-after-gallery'),
            'manage_options',
            'edit.php?post_type=ekwa_bag_case'
        );
        
        add_submenu_page(
            'ekwa-bag',
            __('Add New Case', 'ekwa-before-after-gallery'),
            __('Add New', 'ekwa-before-after-gallery'),
            'manage_options',
            'post-new.php?post_type=ekwa_bag_case'
        );
        
        add_submenu_page(
            'ekwa-bag',
            __('Categories', 'ekwa-before-after-gallery'),
            __('Categories', 'ekwa-before-after-gallery'),
            'manage_options',
            'edit-tags.php?taxonomy=ekwa_bag_category&post_type=ekwa_bag_case'
        );
        
        add_submenu_page(
            'ekwa-bag',
            __('Settings', 'ekwa-before-after-gallery'),
            __('Settings', 'ekwa-before-after-gallery'),
            'manage_options',
            'ekwa-bag-settings',
            array($this, 'render_settings_page')
        );
    }
    
    /**
     * Render admin dashboard page
     */
    public function render_admin_page() {
        include EKWA_BAG_PLUGIN_DIR . 'includes/admin/dashboard.php';
    }
    
    /**
     * Render settings page
     */
    public function render_settings_page() {
        include EKWA_BAG_PLUGIN_DIR . 'includes/admin/settings.php';
    }
    
    /**
     * Admin enqueue scripts
     */
    public function admin_enqueue_scripts($hook) {
        global $post_type;
        
        if ($post_type === 'ekwa_bag_case' || strpos($hook, 'ekwa-bag') !== false) {
            wp_enqueue_media();
            wp_enqueue_style('ekwa-bag-admin', EKWA_BAG_PLUGIN_URL . 'assets/css/admin.css', array(), EKWA_BAG_VERSION);
            wp_enqueue_script('ekwa-bag-admin', EKWA_BAG_PLUGIN_URL . 'assets/js/admin.js', array('jquery', 'jquery-ui-sortable'), EKWA_BAG_VERSION, true);
            
            wp_localize_script('ekwa-bag-admin', 'ekwaBagAdmin', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce'   => wp_create_nonce('ekwa_bag_admin_nonce'),
                'strings' => array(
                    'selectImage' => __('Select Image', 'ekwa-before-after-gallery'),
                    'useImage'    => __('Use This Image', 'ekwa-before-after-gallery'),
                    'removeSet'   => __('Remove this image set?', 'ekwa-before-after-gallery'),
                )
            ));
        }
    }
    
    /**
     * Frontend enqueue scripts
     */
    public function frontend_enqueue_scripts() {
        // Only load on pages with our shortcode
        global $post;
        if (is_a($post, 'WP_Post') && has_shortcode($post->post_content, 'ekwa_gallery')) {
            wp_enqueue_style('ekwa-bag-fonts', 'https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Libre+Baskerville:ital,wght@0,400;0,700;1,400&display=swap', array(), null);
            wp_enqueue_style('ekwa-bag-fontawesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css', array(), '6.4.0');
            wp_enqueue_style('ekwa-bag-gallery', EKWA_BAG_PLUGIN_URL . 'assets/css/gallery.css', array(), EKWA_BAG_VERSION);
            wp_enqueue_script('ekwa-bag-gallery', EKWA_BAG_PLUGIN_URL . 'assets/js/gallery.js', array('jquery'), EKWA_BAG_VERSION, true);
            
            wp_localize_script('ekwa-bag-gallery', 'ekwaBagFrontend', array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce'   => wp_create_nonce('ekwa_bag_frontend_nonce'),
            ));
        }
    }
    
    /**
     * Register shortcode
     */
    public function register_shortcode() {
        add_shortcode('ekwa_gallery', array($this, 'render_shortcode'));
    }
    
    /**
     * Render shortcode
     */
    public function render_shortcode($atts) {
        $atts = shortcode_atts(array(
            'category'    => '',
            'limit'       => -1,
            'columns'     => 3,
            'show_filter' => 'yes',
        ), $atts, 'ekwa_gallery');
        
        // Get cases data
        $cases = $this->get_cases_data($atts);
        $categories = $this->get_categories_tree();
        
        // Ensure scripts are loaded
        wp_enqueue_style('ekwa-bag-fonts', 'https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Libre+Baskerville:ital,wght@0,400;0,700;1,400&display=swap', array(), null);
        wp_enqueue_style('ekwa-bag-fontawesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css', array(), '6.4.0');
        wp_enqueue_style('ekwa-bag-gallery', EKWA_BAG_PLUGIN_URL . 'assets/css/gallery.css', array(), EKWA_BAG_VERSION);
        wp_enqueue_script('ekwa-bag-gallery', EKWA_BAG_PLUGIN_URL . 'assets/js/gallery.js', array('jquery'), EKWA_BAG_VERSION, true);
        
        // Use inline script to pass data reliably
        $json_data = json_encode(array(
            'ajaxUrl'    => admin_url('admin-ajax.php'),
            'nonce'      => wp_create_nonce('ekwa_bag_frontend_nonce'),
            'cases'      => $cases,
            'categories' => $categories,
            'debug'      => $this->get_debug_info(),
        ));
        
        wp_add_inline_script('ekwa-bag-gallery', 'var ekwaBagFrontend = ' . $json_data . ';', 'before');
        
        ob_start();
        include EKWA_BAG_PLUGIN_DIR . 'includes/frontend/gallery-template.php';
        return ob_get_clean();
    }
    
    /**
     * Get cases data for frontend
     */
    private function get_cases_data($atts = array()) {
        $args = array(
            'post_type'      => 'ekwa_bag_case',
            'post_status'    => 'publish',
            'posts_per_page' => isset($atts['limit']) ? intval($atts['limit']) : -1,
            'orderby'        => 'menu_order date',
            'order'          => 'ASC',
        );
        
        if (!empty($atts['category'])) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => 'ekwa_bag_category',
                    'field'    => 'slug',
                    'terms'    => explode(',', $atts['category']),
                )
            );
        }
        
        $query = new WP_Query($args);
        $cases = array();
        
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $post_id = get_the_ID();
                
                // Get categories
                $terms = get_the_terms($post_id, 'ekwa_bag_category');
                $mainCat = '';
                $subCat = '';
                
                if ($terms && !is_wp_error($terms)) {
                    foreach ($terms as $term) {
                        if ($term->parent == 0) {
                            $mainCat = $term->slug;
                        } else {
                            $subCat = $term->slug;
                            // If only subcategory is assigned, get the parent as mainCat
                            if (empty($mainCat)) {
                                $parent_term = get_term($term->parent, 'ekwa_bag_category');
                                if ($parent_term && !is_wp_error($parent_term)) {
                                    $mainCat = $parent_term->slug;
                                }
                            }
                        }
                    }
                }
                
                // Get image sets
                $image_sets = get_post_meta($post_id, '_ekwa_bag_image_sets', true);
                $sets = array();
                
                if (!empty($image_sets) && is_array($image_sets)) {
                    foreach ($image_sets as $set) {
                        $before_id = isset($set['before']) ? absint($set['before']) : 0;
                        $after_id = isset($set['after']) ? absint($set['after']) : 0;
                        
                        if ($before_id && $after_id) {
                            $before_url = wp_get_attachment_image_url($before_id, 'large');
                            if (!$before_url) $before_url = wp_get_attachment_image_url($before_id, 'full');

                            $after_url = wp_get_attachment_image_url($after_id, 'large');
                            if (!$after_url) $after_url = wp_get_attachment_image_url($after_id, 'full');
                            
                            if ($before_url && $after_url) {
                                $sets[] = array(
                                    'before' => $before_url,
                                    'after'  => $after_url,
                                );
                            }
                        }
                    }
                }
                
                // Only include cases that have at least one valid image set
                if (!empty($sets)) {
                    $cases[] = array(
                        'id'      => $post_id,
                        'title'   => get_the_title(),
                        'mainCat' => $mainCat ?: 'uncategorized',
                        'subCat'  => $subCat,
                        'desc'    => wp_strip_all_tags(get_the_content()),
                        'sets'    => $sets,
                    );
                }
            }
            wp_reset_postdata();
        }
        
        return $cases;
    }
    
    /**
     * Debug helper - get info about all cases
     */
    private function get_debug_info() {
        $debug = array(
            'function_called' => true,
            'timestamp' => current_time('mysql'),
        );
        
        $args = array(
            'post_type'      => 'ekwa_bag_case',
            'post_status'    => 'any',
            'posts_per_page' => -1,
        );
        
        $query = new WP_Query($args);
        
        $debug['query_args'] = $args;
        $debug['total_posts'] = $query->found_posts;
        $debug['posts'] = array();
        
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $post_id = get_the_ID();
                $image_sets_raw = get_post_meta($post_id, '_ekwa_bag_image_sets', true);
                
                // Try to get image URLs
                $processed_sets = array();
                if (!empty($image_sets_raw) && is_array($image_sets_raw)) {
                    foreach ($image_sets_raw as $set) {
                        $before_id = isset($set['before']) ? absint($set['before']) : 0;
                        $after_id = isset($set['after']) ? absint($set['after']) : 0;
                        $before_url = $before_id ? wp_get_attachment_image_url($before_id, 'large') : false;
                        $after_url = $after_id ? wp_get_attachment_image_url($after_id, 'large') : false;
                        
                        $processed_sets[] = array(
                            'before_id' => $before_id,
                            'after_id' => $after_id,
                            'before_url' => $before_url,
                            'after_url' => $after_url,
                        );
                    }
                }
                
                $debug['posts'][] = array(
                    'id'              => $post_id,
                    'title'           => get_the_title(),
                    'status'          => get_post_status(),
                    'image_sets_raw'  => $image_sets_raw,
                    'processed_sets'  => $processed_sets,
                );
            }
            wp_reset_postdata();
        }
        
        return $debug;
    }
    
    /**
     * Get categories tree for frontend
     */
    private function get_categories_tree() {
        $tree = array(
            'all' => array(
                'label'   => __('All', 'ekwa-before-after-gallery'),
                'subCats' => array()
            )
        );
        
        $parent_terms = get_terms(array(
            'taxonomy'   => 'ekwa_bag_category',
            'hide_empty' => false,
            'parent'     => 0,
        ));
        
        if (!is_wp_error($parent_terms)) {
            foreach ($parent_terms as $parent) {
                $children = get_terms(array(
                    'taxonomy'   => 'ekwa_bag_category',
                    'hide_empty' => false,
                    'parent'     => $parent->term_id,
                ));
                
                $subCats = array();
                if (!is_wp_error($children)) {
                    foreach ($children as $child) {
                        $subCats[] = array(
                            'key'   => $child->slug,
                            'label' => $child->name,
                        );
                    }
                }
                
                $tree[$parent->slug] = array(
                    'label'   => $parent->name,
                    'subCats' => $subCats,
                );
            }
        }
        
        return $tree;
    }
    
    /**
     * Add meta boxes
     */
    public function add_meta_boxes() {
        add_meta_box(
            'ekwa_bag_image_sets',
            __('Before & After Image Sets', 'ekwa-before-after-gallery'),
            array($this, 'render_image_sets_meta_box'),
            'ekwa_bag_case',
            'normal',
            'high'
        );
    }
    
    /**
     * Render image sets meta box
     */
    public function render_image_sets_meta_box($post) {
        wp_nonce_field('ekwa_bag_save_meta', 'ekwa_bag_meta_nonce');
        $image_sets = get_post_meta($post->ID, '_ekwa_bag_image_sets', true);
        if (empty($image_sets)) {
            $image_sets = array(array('before' => '', 'after' => ''));
        }
        include EKWA_BAG_PLUGIN_DIR . 'includes/admin/meta-box-image-sets.php';
    }
    
    /**
     * Save meta boxes
     */
    public function save_meta_boxes($post_id) {
        // Verify nonce
        if (!isset($_POST['ekwa_bag_meta_nonce']) || !wp_verify_nonce($_POST['ekwa_bag_meta_nonce'], 'ekwa_bag_save_meta')) {
            return;
        }
        
        // Check autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Check permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Save image sets
        if (isset($_POST['ekwa_bag_image_sets'])) {
            $image_sets = array();
            foreach ($_POST['ekwa_bag_image_sets'] as $set) {
                if (!empty($set['before']) || !empty($set['after'])) {
                    $image_sets[] = array(
                        'before' => absint($set['before']),
                        'after'  => absint($set['after']),
                    );
                }
            }
            update_post_meta($post_id, '_ekwa_bag_image_sets', $image_sets);
        }
    }
    
    /**
     * AJAX handler to get cases
     */
    public function ajax_get_cases() {
        check_ajax_referer('ekwa_bag_frontend_nonce', 'nonce');
        
        $cases = $this->get_cases_data();
        $categories = $this->get_categories_tree();
        
        wp_send_json_success(array(
            'cases'      => $cases,
            'categories' => $categories,
        ));
    }
}

// Initialize plugin
function ekwa_before_after_gallery_init() {
    return EKWA_Before_After_Gallery::get_instance();
}
add_action('plugins_loaded', 'ekwa_before_after_gallery_init');
