<?php
/**
 * Plugin Name: Epik Copy Posts/Pages
 * Plugin URI: http://www.epikmedia.com
 * Description: Adding copy links to posts/pages
 * Version: 4
 * Author: Neil Carlo Sucuangco
 * Author URI: www.author.com
 * Text Domain: epik-copy-posts-pages
*/
error_reporting( E_ALL );
 if ( ! defined( 'ABSPATH' ) )
 {
     exit;
 }

 class Epik_Copy
 {

    private static $instance = null;

    const PLUGIN_NAME  = 'Epik_Copy';

    const SLUG = 'epik-copy-posts-pages';

    public function __construct()
    {

        if ( isset( self::$instance ) )
        {
            wp_die( esc_html( 'The Epik Copy class has already been loaded' , 'epik-copy-posts-pages' ) );
        }

        self::$instance = $this;

        add_action( 'init' ,                        array( $this , 'init' ) , 1 );
        add_action( 'admin_enqueue_scripts' ,       array( $this , 'admin_enqueue_scripts' ) );
        add_filter( 'post_row_actions' ,            array( $this , 'copy_post_link' ) , 10 , 2 );
        add_filter( 'page_row_actions' ,            array( $this , 'copy_page_link' ) , 10 , 2 );
        add_action( 'admin_action_epik_copy_post' , array( $this , 'epik_copy_post' ) );
        add_action( 'admin_action_epik_copy_page' , array( $this , 'epik_copy_page' ) );

    }

    public function init()
    {

    }

    public function admin_enqueue_scripts( $hook )
    {

        if ( 'edit.php' == $hook )
        {
            wp_enqueue_script( self::SLUG , plugins_url( 'js/script.js' , __FILE__ ) , array( 'jquery' ) );
            wp_enqueue_style( self::SLUG , plugins_url( 'css/style.css' , __FILE__ ) );
        }

    }

    public function copy_post_link( $actions , $post )
    {

        $url = esc_url( 'admin.php?action=epik_copy_post&post=' . $post->ID );

        $actions[ 'copy' ] = '<a class="epik_copy_post" href="' . $url . '" title="' . __("Make a copy from this post")
		. '" rel="permalink">' .  __( "Copy" ) . '</a>';

        return $actions;

    }

    public function copy_page_link( $actions , $post )
    {

        $url = esc_url( 'admin.php?action=epik_copy_page&post=' . $post->ID );

        $actions[ 'copy' ] = '<a class="epik_copy_page" href="' . $url . '" title="' . __("Make a copy from this page")
		. '" rel="permalink">' .  __( "Copy" ) . '</a>';

        return $actions;

    }

    public function epik_copy_post()
    {

        if ( ( isset( $_GET['post'] ) && ! empty( $_GET['post'] ) ) && ( isset( $_GET['copy'] ) && ! empty( $_GET['copy'] ) ) && ( isset( $_GET['action'] ) && $_GET['action'] == 'epik_copy_post' ) )
        {
            $postid     = (int) $_GET['post'];

            if ( $this->myplugin_get_post( $postid ) )
            {
                $newid      = $this->myplugin_from_post( $postid );
                $link       = get_edit_post_link( $newid );

                wp_redirect( $link );
                exit;
            }
            else
            {
                wp_die( __( 'No post to duplicate has been supplied!' , DUPLICATE_POST_I18N_DOMAIN ) );
            }

        }
        else
        {
            wp_die( __( 'No post to duplicate has been supplied!' , DUPLICATE_POST_I18N_DOMAIN ) );
        }

    }

    private function myplugin_get_post( $postid )
    {
        if ( FALSE === get_post_status( $postid ) )
        {
            return false;
        }
        else
        {
            return true;
        }
    }

    private function myplugin_from_post( $postid )
    {

        global $wpdb;

        $post = get_post( $postid );

        $post_author            = get_current_user_id();
        $new_post_date          = current_time( 'mysql' );
        $new_post_date_gmt      = get_gmt_from_date( $new_post_date );
        $post_content           = $post->post_content;
        $post_title             = $post->post_title;
        $post_excerpt           = $post->post_excerpt;
        $post_status            = $post->post_status;
        $comment_status         = $post->comment_status;
        $ping_status            = $post->ping_status;
        $post_password          = $post->post_password;
        $post_name              = $post->post_name;
        $to_ping                = $post->to_ping;
        $pinged                 = $post->pinged;
        $post_content_filtered  = $post->post_content_filtered;
        $post_parent            = $post->post_parent;
        $menu_order             = $post->menu_order;
        $post_type              = $post->post_type;
        $post_mime_type         = $post->post_mime_type;
        $comment_count          = $post->comment_count;
        $filter                 = $post->filter;

        $wpdb->insert(
            $wpdb->posts ,
            array(
                'post_author'           => $post_author ,
                'post_date'             => $new_post_date ,
                'post_date_gmt'         => $new_post_date_gmt ,
                'post_content'          => $post_content ,
                'post_title'            => $post_title ,
                'post_excerpt'          => $post_excerpt ,
                'post_status'           => 'draft' ,
                'comment_status'        => $comment_status ,
                'ping_status'           => $ping_status ,
                'post_password'         => $post_password ,
                'post_name'             => '' ,
                'to_ping'               => $to_ping ,
                'pinged'                => $pinged ,
                'post_modified'         => $new_post_date ,
                'post_modified_gmt'     => $new_post_date_gmt ,
                'post_content_filtered' => $post_content_filtered ,
                'post_parent'           => $post_parent ,
                'guid'                  => '' ,
                'menu_order'            => $menu_order ,
                'post_type'             => $post_type ,
                'post_mime_type'        => $post_mime_type ,
                'comment_count'         => $comment_count
            ) ,
            array(

            )
        );

        $new_post_id = $wpdb->insert_id;

        $this->myplugin_post_taxonomies( $post->ID , $new_post_id , $post->post_type );

        if ( isset( $_GET['copy'] ) && $_GET['copy'] == 1 )
        {
            $this->myplugin_post_meta_info( $post->ID , $new_post_id );
        }

        return $new_post_id;
    }

    private function myplugin_post_taxonomies( $id , $new_id , $post_type )
    {
        global $wpdb;
	    if (isset($wpdb->terms)) {
    		$taxonomies = get_object_taxonomies($post_type);
    		foreach ($taxonomies as $taxonomy) {
    			$post_terms = wp_get_object_terms($id, $taxonomy);
    			for ($i=0; $i<count($post_terms); $i++) {
    				wp_set_object_terms($new_id, $post_terms[$i]->slug, $taxonomy, true);
    			}
    		}
    	}
    }

    private function myplugin_post_meta_info( $id , $new_id ) {
    	global $wpdb;
    	$post_meta_infos = $wpdb->get_results("SELECT meta_key, meta_value FROM $wpdb->postmeta WHERE post_id=$id");

    	if (count($post_meta_infos)!=0) {
    		$sql_query = "INSERT INTO $wpdb->postmeta (post_id, meta_key, meta_value) ";
    		$meta_no_copy = explode(",",get_option('duplicate_post_blacklist'));
    		foreach ($post_meta_infos as $meta_info) {
    			$meta_key = $meta_info->meta_key;
    			$meta_value = addslashes($meta_info->meta_value);
    			if (!in_array($meta_key,$meta_no_copy)) {
    				$sql_query_sel[]= "SELECT $new_id, '$meta_key', '$meta_value'";
    			}
    		}
    		$sql_query.= implode(" UNION ALL ", $sql_query_sel);
    		$wpdb->query($sql_query);
    	}
    }

    public function epik_copy_page()
    {
        if ( ( isset( $_GET['post'] ) && ! empty( $_GET['post'] ) ) && ( isset( $_GET['copy'] ) && ! empty( $_GET['copy'] ) ) && ( isset( $_GET['action'] ) && $_GET['action'] == 'epik_copy_page' ) )
        {
            $postid     = (int) $_GET['post'];

            if ( $this->myplugin_get_post( $postid ) )
            {
                $newid      = $this->myplugin_from_post( $postid );
                $link       = get_edit_post_link( $newid );

                wp_redirect( $link );
                exit;
            }
            else
            {
                wp_die( __( 'No page to duplicate has been supplied!' , DUPLICATE_POST_I18N_DOMAIN ) );
            }

        }
        else
        {
            wp_die( __( 'No page to duplicate has been supplied!' , DUPLICATE_POST_I18N_DOMAIN ) );
        }
    }

 }

 if ( is_admin() )
 {
     new Epik_Copy;
 }
