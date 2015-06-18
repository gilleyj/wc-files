<?php
    /**
     * @package wc_files
     * @version 1.0
     */
    /*
     Plugin Name: Wordpress File Sharing Tool
     Plugin URI: https://github.com/gilleyj/wc-files
     Description: Adds a file post type with admin and shortcodes
     Author: Joelle Gilley
     Version: 1.0
     Author URI: http://whamcat.com/
     License: GNU General Public License
     Text Domain: wc_files
     */
    
    if ( ! class_exists( 'wc_files' ) ) :
    
    class wc_files_class {
        
        /**
         * Notice Array
         */
        Private $_notices = array();
        
        /**
         * Nonce value
         */
        private $_nonce = 'wc_files_nonce';
        
        /**
         * New Post Type
         */
        Private $_posttype = 'wc_file';
        
        /**
         * Our custom field name
         */
        Private $_custom_field_name = 'wc_file_attachment_media_id';
        
        
        /**
         * Loads default options
         *
         * @return void
         */
        function __construct() {
            // the initialization function basically register the post type
            add_action( 'init', array( $this, 'wc_files_init' ) );
            
            // add upload metainfo to the admin form
            add_action( 'post_edit_form_tag', array( $this, 'update_edit_form' ) );
            
            // action to take when saving the post with a file
            add_action( 'save_post', array( $this, 'save_custom_meta_data' ) );
            
            // adding a content filter to display this arrangement
            add_filter( 'the_content', array( &$this, 'content_filter' ), 1 );
            
            // add filtering for the shortcodes
            add_shortcode( 'wc-files', array( &$this, 'documents_shortcode' ) );
            
            
            wp_register_style( 'myPluginStylesheet', plugins_url('stylesheet.css', __FILE__) );
            wp_enqueue_style( 'myPluginStylesheet' );

        }
        
        
        /**
         * Prevents Attachment ID from being displayed on front end
         * @since 1.0.3
         * @param string $content the post content
         * @return string either the original content or none
         */
        function content_filter( $content ) {
            
            if ( !$this->verify_post_type( ) )
                return $content;
            
            //allow password prompt to display
            if ( post_password_required() )
                return $content;
            
            $html = '';
            $attachment_id = intval(get_post_meta( get_the_ID(), $this->_custom_field_name, true));
            if($attachment_id>0) {
                $attachment = $this->wc_file_get_attachment( $attachment_id );
                $html .= $attachment->thumbnail;
                $html .= '<p class="title"><strong>'.$attachment->filename.'</strong> ('.$attachment->post_mime_type.')</p>';
                $html .= '<p class="info"><strong><a href="'.$attachment->uri_relative.'" target="_blank">'.__('Download').'</a></strong></p>';
            } else {
                $html .= '<p class="title">'.__('There is no attachment to display.').'</p>';
            }
            
            return $html;
            
        }
        
        /**
         * Checks if a given post is a document
         * note: We can't use the screen API because A) used on front end, and B) admin_init is too early (enqueue scripts)
         * @param object|int either a post object or a postID
         * @since 0.5
         * @param unknown $post (optional)
         * @return bool true if document, false if not
         */
        function verify_post_type( $post = false ) {
            
            //check for post_type query arg (post new)
            if ( $post == false && isset( $_GET['post_type'] ) && $_GET['post_type'] == $this->_posttype )
                return true;
            
            //if post isn't set, try get vars (edit post)
            if ( $post == false )
                $post = ( isset( $_GET['post'] ) ) ? $_GET['post'] : false;
            
            //look for post_id via post or get (media upload)
            if ( $post == false )
                $post = ( isset( $_REQUEST['post_id'] ) ) ? $_REQUEST['post_id'] : false;
            
            
            $post_type = get_post_type( $post );
            
            //if post is really an attachment or revision, look to the post's parent
            if ( $post_type == $this->_posttype )
                $post_type = get_post_type( get_post( $post )->post_parent );
            
            return $post_type == $this->_posttype;
            
        }
        
        /**
         * adds the ecoding type to handle a file upload to the admin form
         *
         * @access public
         */
        function update_edit_form() {
            echo ' enctype="multipart/form-data"';
        }
        
        /**
         * Initializes the files post type
         *
         * @access public
         */
        function wc_files_init() {
            // The labels used by our new post type.
            $labels = array(
                            'name'                  => __( 'File Tool', 'wc_files' ),
                            'singular_name'         => __( 'File' ),
                            'menu_name'             => __( 'WC Files' ),
                            'add_new'               => __( 'Add a file' ),
                            'all_items'             => __( 'All files' ),
                            'add_new_item'          => __( 'Add a file' ),
                            'edit_item'             => __( 'Edit file' ),
                            'new_item'              => __( 'New file' ),
                            'view_item'             => __( 'View file' ),
                            'search_items'          => __( 'Search files' ),
                            'not_found'             => __( 'No files found' ),
                            'not_found_in_trash'    => __( 'No files found in trash' ),
                            'parent_item_colon'     => __( 'Parent file' ),
                            );
            
            $supports = array(
                              'title',
                              // 'editor',
                              // 'author',
                              // 'thumbnail',
                              'excerpt',
                              // 'trackbacks',
                              // 'custom-fields',
                              // 'comments',
                              // 'revisions',
                              // 'page-attributes',
                              // 'post-formats',
                              );
            
            // plugins_url('/img/menu-icon.png', __FILE__)
            $options = array(
                             'labels'                   => $labels,
                             'public'                   => true,
                             'has_archive'              => true,
                             'publicly_queryable'       => true,
                             'query_var'                => true,
                             'rewrite'                  => true,
                             'capability_type'          => 'post',
                             'hierarchical'             => false,
                             'register_meta_box_cb'     => array( $this, 'wc_files_add_meta_box' ),
                             'supports'                 => $supports,
                             'menu_icon'                => 'dashicons-portfolio',
                             );
            
            register_post_type(
                               $this->_posttype,
                               $options
                               );
            
            register_taxonomy_for_object_type( 'category', $this->_posttype );
        }
        
        /**
         * Define the custom metabox for our content tupe
         *
         * @access public
         */
        function wc_files_add_meta_box() {
            
            // Define the custom attachment for posts
            add_meta_box(
                         'wc_files_attachment', // id
                         __('File Attachment'), // title
                         array( $this, 'wc_files_render_meta_box' ), // callback
                         $this->_posttype,      // screen
                         'normal',              // context
                         'high'                 // priority
                         );
            
        }
        
        /**
         * Get the attachment with some extras
         *
         * @access public
         */
        function wc_file_get_attachment( $attachment_id ) {
            
            $attachment = get_post( $attachment_id );
            
            $url_realtive = parse_url( $attachment->guid );
            $attachment->uri_relative = $url_realtive['path'];
            $attachment->filepath = get_attached_file( $attachment->ID );
            $attachment->filename = basename( $attachment->filepath );
            
            $attachment->thumbnail = wp_get_attachment_image(
                                                             $attachment->ID,
                                                             array(64,64),
                                                             true,
                                                             array('class' => 'attachment-64x64 alignright')
                                                             );
            
            return $attachment;
        }
        
        /**
         * Draws the meta box for our post type
         *
         * @access public
         */
        function wc_files_render_meta_box() {
            
            $html = '';
            
            wp_nonce_field(plugin_basename(__FILE__), $this->_nonce);
            
            $attachment_id = intval(get_post_meta( get_the_ID(), $this->_custom_field_name, true));
            if($attachment_id>0) {
                $attachment = $this->wc_file_get_attachment( $attachment_id );
                $html .= $attachment->thumbnail;
                $html .= '<p class="title"><strong>'.$attachment->filename.'</strong> ('.$attachment->post_mime_type.')</p>';
            }
            
            $html .= '<p class="description">';
            $html .= 'Upload a new file here:';
            $html .= '</p>';
            $html .= '<input type="file" id="wc_files_upload" name="wc_files_upload" value="" size="25" />';
            
            echo $html;
        }
        
        
        /**
         * Saves and uploads the file
         *
         * @access public
         */
        function save_custom_meta_data($id) {
            
            /* --- security verification --- */
            if(!wp_verify_nonce($_POST[$this->_nonce], plugin_basename(__FILE__))) {
                return $id;
            } // end if
            
            if(defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
                return $id;
            } // end if
            
            if('page' == $_POST['post_type']) {
                if(!current_user_can('edit_page', $id)) {
                    return $id;
                } // end if
            } else {
                if(!current_user_can('edit_page', $id)) {
                    return $id;
                } // end if
            } // end if
            /* - end security verification - */
            
            // Make sure the file array isn't empty
            if(!empty($_FILES['wc_files_upload']['name'])) {
                
                // Get the file type of the upload
                $arr_file_type = wp_check_filetype(basename($_FILES['wc_files_upload']['name']));
                $uploaded_type = $arr_file_type['type'];
                
                // Check if the type is supported. If not, throw an error.
                // if(in_array($uploaded_type, $supported_types)) {
                
                // Use the WordPress API to upload the file
                $attachment_id = media_handle_upload( 'wc_files_upload', $_POST['post_id'] );
                if ( is_wp_error( $attachment_id ) ) {
                    wp_die('There was an error uploading your file. The error is: ' . $upload['error']);
                } else {
                    add_post_meta($id, $this->_custom_field_name, $attachment_id, true) or
                    update_post_meta($id, $this->_custom_field_name, $attachment_id);
                } // end if/else
                
            } // end if
            
        } // end save_custom_meta_data
        
        function my_get_documents( $args = array(), $return_attachments = false ) {
            $args = (array) $args;
            $args['post_type'] = $this->_posttype;
            $documents = get_posts( $args );
            $output = array_filter( $documents );
            
            $documents = array();
            foreach ( $output as $document ) {
                $attachment_id = intval(get_post_meta( $document->ID, $this->_custom_field_name, true));
                if($attachment_id>0) $attachment = $this->wc_file_get_attachment($attachment_id);
                else $attachment = false;
                $document->attachment = $attachment;
                $documents[] = $document;
            }
            
            return $documents;
        }
        
        
        /**
         * Process the shortcodes
         * @access public
         */
        function documents_shortcode( $atts ) {
            
            $defaults = array(
                              'orderby' => 'modified',
                              'order' => 'DESC',
                              );
            
            //list of all string or int based query vars (because we are going through shortcode)
            // via http://codex.wordpress.org/Class_Reference/WP_Query#Parameters
            $keys = array( 'author', 'author_name', 'cat', 'category_name', 'category__and', 'tag', 'tag_id', 'p', 'name', 'post_parent', 'post_status', 'numberposts', 'year', 'monthnum', 'w', 'day', 'hour', 'minute', 'second', 'meta_key', 'meta_value', 'meta_value_num', 'meta_compare');
            
            foreach ( $keys as $key )
            $defaults[ $key ] = null;
            
            $taxs = get_taxonomies( array( 'object_type' => array( $this->_posttype ) ), 'objects' );
            
            //allow querying by custom taxonomy
            foreach ( $taxs as $tax ) {
                $defaults[ $tax->query_var ] = null;
            }
            $atts = apply_filters( 'document_shortcode_atts', $atts );
            
            //default arguments, can be overriden by shortcode attributes
            $atts = shortcode_atts( $defaults, $atts );
            $atts = array_filter( $atts );
            
            // get the lists
            $documents = $this->my_get_documents( $atts );
            
            // build up html and return that
            $html = '';
            $html .= '<ul class="wc-files-list">';
            
            foreach ( $documents as $document ) {
                
                if($document->attachment != false) {
                    $html .= '<li class="wc-file-li wc_file-'.$document->attachment->ID.'">';
                    $html .= '<a class="wc-file-a" href="'.$document->attachment->uri_relative.'" target="_blank">';
                    $html .= '<span class="wc-file-title">'.get_the_title( $document->ID ).'</span>';
                    // $html .= '<span class="wc-file-name">'.$document->attachment->filename.'</span> ';
                    $html .= '<span class="wc-file-type">('.$document->attachment->post_mime_type.')</span>';
                    $html .= '</a>';
                    if(current_user_can('edit_posts')) {
                        $html .= '<a class="wc-file-edit-link" href="'.get_edit_post_link( $document->ID ).'">edit</a>';
                    }
                    if($document->post_excerpt != '') {
                        $html .= '<span class="wc-file-excerpt">'.$document->post_excerpt.'</span>';
                    }
                    $html .= '</li>';
                }
            }
            $html .= '</ul>';
            
            return $html;
            
        }
        
    }
    
    // Start it up!
    $wc_files_object = new wc_files_class();
    
    endif;
    
    