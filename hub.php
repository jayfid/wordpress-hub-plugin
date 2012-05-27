<?php

/*
Plugin Name: Hub Repositories
Plugin URI: https://github.com/jayfid/wordpress-hub-plugin.git
Description: Display a user's public repos
Author: jayfid
Version: 0.1
Author URI: http://blog.newpointdesigns.com
*/

/**
 * Adds Hub_Widget widget.
 */
class Hub_Widget extends WP_Widget {

  public function __construct() {
    parent::__construct(
       'hub_widget',
      'Hub_Widget',
      array( 'description' => __( 'Display Github repos', 'text_domain' ), )
    );
  }

  /**
   * Front-end display of widget.
   *
   * @see WP_Widget::widget()
   *
   * @param array $args     Widget arguments.
   * @param array $instance Saved values from database.
   */
  public function widget( $args, $instance ) {
    extract( $args );
    $title = apply_filters( 'widget_title', $instance['title'] );
    echo $before_widget;
    if ( ! empty( $title ) )
      echo $before_title . $title . $after_title;
      if ( isset( $instance[ 'github_user' ] ) && $instance[ 'github_user' ] != '' ) {
        $cache_file = dirname(__FILE__) . '/projects.cache';
        if ( isset($instance[ 'cache' ]) && $instance[ 'cache' ] && file_exists($cache_file) && filemtime($cache_file) + $instance[ 'cache' ] > time() ) {
          $output = file_get_contents($cache_file);
          echo $output;
          return;
        }
        else {
          //echo $instance[ 'github_user' ];
          $get_curl_response = wp_remote_get('https://api.github.com/users/'.$instance[ 'github_user' ].'/repos');
          //echo $get_curl_response['headers']['status'];
          if ( $get_curl_response['headers']['status'] == '200 OK' ) {
            $data = json_decode($get_curl_response['body']);
            //print_r($data);
            if ( !empty( $data ) ) {
              //print_r($data['message']);
              if ( !isset( $data['message'] ) ) {
                $output = "";
                $x = plugin_dir_url(__FILE__);
                $output .= '<a href="https://www.github.com" title="Github"><img alt="Github.com" src="'.$x.'octocat.png" /></a>';
                $output.= '<h3 style="text-align:center;margin-bottom:1em;"><a href="https://www.github.com/' . $instance[ 'github_user' ] . '">' . $instance[ 'github_user' ] . ' on GitHub</a></h3>';
                $output .= "<ul>";
                foreach ( $data as $repo ) {
                  $output .= '<li>';
                  $output .=   '<p style="margin:0;"><a href="' . $repo->html_url . '"><strong>' . $repo->name . '</strong></a></p>';
                  $output .=   '<p><small>Last Updated: ' . date('M jS, Y h:i', strtotime($repo->updated_at)) . '</small></p>';
                  $output .= '</li>';
                }
                $output .= "</ul>";
                file_put_contents($cache_file, $output);
                echo $output;
              }
              elseif ( $data['message'] != 'Not Found' ) {
                echo '<p class="error">The user specified was not found!</p>';
              }
            }
            else {
              echo "This user has no public respositories!";
            }
          }
          else {
            echo '<p class="error">We were unable to reach the Github API at this time.</p>';
          }
        }
      }
      else {
        echo "The hub widget hasn't been configured yet.";
      }
    echo $after_widget;
  }

  /**
   * Sanitize widget form values as they are saved.
   *
   * @see WP_Widget::update()
   *
   * @param array $new_instance Values just sent to be saved.
   * @param array $old_instance Previously saved values from database.
   *
   * @return array Updated safe values to be saved.
   */
  public function update( $new_instance, $old_instance ) {
    $instance = array();
    $instance['title'] = strip_tags( $new_instance['title'] );
    if ($new_instance['github_user'] != $old_instance['github_user']) {
      $instance[ 'cache' ] = 0;
    }
    else {
      $instance[ 'cache' ] = $new_instance[ 'cache' ];
    }
    $instance['github_user'] = strip_tags( $new_instance['github_user'] );
    return $instance;
  }

  /**
   * Back-end widget form.
   *
   * @see WP_Widget::form()
   *
   * @param array $instance Previously saved values from database.
   */
  public function form( $instance ) {
    if ( isset( $instance[ 'title' ] ) ) {
      $title = $instance[ 'title' ];
    }
    else {
      $title = __( 'New title', 'text_domain' );
    }
    ?>
    <p>
    <label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label> 
    <input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
    </p>
    <?php
    if ( isset( $instance[ 'github_user' ] ) ) {
      $github_user = $instance[ 'github_user' ];
    }
    else {
      $github_user = __( '', 'text_domain' );
    }
    ?>
    <p>
    <label for="<?php echo $this->get_field_id( 'github_user' ); ?>"><?php _e( 'Github User:' ); ?></label>
    <input class="widefat" id="<?php echo $this->get_field_id( 'github_user' ) ?>" name="<?php echo $this->get_field_name( 'github_user' ); ?>" type="text" value="<?php echo esc_attr( $github_user ); ?>" />
    </p>
    <?php
    if ( isset($instance[ 'cache' ]) ) {
      $cache = $instance[ 'cache' ];
    }
    else {
      $cache = 0;
    }
    ?>
      <p>
      <label for="<?php echo $this->get_field_id( 'cache' ); ?>"><?php _e( 'Time to cache (in seconds):' ); ?></label>
      <input class="widefat" id="<?php echo $this->get_field_id( 'cache' ) ?>" name="<?php echo $this->get_field_name( 'cache' ); ?>" type="text" value="<?php echo esc_attr( $cache ); ?>" />
      </p>
    <?php
  }

} // class Hub_Widget

add_action( 'widgets_init', create_function( '', 'register_widget( "hub_widget" );' ) );