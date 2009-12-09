<?php
/*
Plugin Name: Show Revision History
Plugin URI: http://keyes.ie/wordpress/show-revision-history
Description: Allow any visitor to your site to view the revisions of posts and/or pages.
Version: 0.1
Author: John Keyes
Author URI: http://keyes.ie
*/

/*  Copyright 2009  John Keyes
 
    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.
 
    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.
 
    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

/*
    Thanks to D'Arcy Norman's http://www.darcynorman.net/wordpress/post-revision-display/
    for a push in the right direction.
*/

add_action('wp', 'check_for_revision');

function check_for_revision($wp) {
    if (valid_revision_id()) {
        $revision = $_GET['revision'];
        $post = get_post($revision);
        if ($post != null) {
            # reset posts so the revision is displayed instead of the original
            global $posts;
            $posts = array($post);
        }
    }
}

add_filter('the_content', 'display_post_revisions');

function display_post_revisions($content) {
	$post = get_post(get_the_ID());
	
	// if we are visiting a revision we need to adjust the post 
	// so we can still get the revision history.
	if ( $post && $post->post_type == "revision" ) {
		$post = get_post($post->post_parent);
	}
    $page_name = 'show_page_revisions';
    $post_name = 'show_post_revisions';
	$page_val = get_option( $page_name );
    $post_val = get_option( $post_name );
    
	if ( $post && (( $post->post_type == "post" && $post_val) ||
	     ($post->post_type == "page" && $page_val)) ) {
 		$revisions = list_post_revisions($post);
 		if ( $revisions ) {
    		$content .= '<div class="revision-history">';
    		$content .= '	<h3>Revision History:</h3>';
		    $content .= $revisions;
    		$content .= '</div>';
    	}
	}
	return $content;
}


function list_post_revisions( $post ) {
	if ( $revisions = wp_get_post_revisions( $post->ID ) ) {
    	$items = '';
	    $revision_id = (valid_revision_id()) ? $revision_id = $_GET['revision'] : $post->ID;
    	foreach ( $revisions as $revision ) {
    		$date = wp_post_revision_title( $revision, 0 );
    		$name = get_author_name( $revision->post_author );
    		$query_string = get_query_string($revision);
    		$items .= "<li>";
    		if ($revision_id == $revision->ID) {
    		    $items .= "$date by $name (<em>displayed above</em>)";
    		} else {
    		    $items .= "<a href=\"$query_string\">$date</a> by $name";
    		}
    		$items .= "</li>";
    	}
    	return "<ul class='revision-list'>$items</ul>";
    }
}

function valid_revision_id() {
    return isset($_GET['revision']) && is_numeric($_GET['revision']);
}

function get_query_string($revision) {
    $query_string = "?revision=$revision->ID";
    foreach ($_GET as $key => $value) {
        if ($key != "revision") {
            $query_string.="&$key=$value";
        }
    }
    return $query_string;
}

add_option('show_page_revisions', '0');
add_option('show_post_revisions', '0');

// Hook for adding admin menus
add_action('admin_menu', 'add_revision_history');

// action function for above hook
function add_revision_history() {
    // Add a new submenu under Settings:
    add_options_page('Revision History', 'Revision History', 'administrator', 'show_revhis_settings', 'add_revision_history_options_page');
}

function add_revision_history_options_page() {
    // variables for the field and option names
    $page_name = 'show_page_revisions';
    $post_name = 'show_post_revisions';
    $page_field_name = 'show_page_revisions';
    $post_field_name = 'show_post_revisions';
    $hidden_field_name = 'submit_hidden';

    // get the current values
    $page_val = get_option( $page_name );
    $post_val = get_option( $post_name );

    // See if the user has posted us some information
    // If they did, this hidden field will be set to 'Y'
    if( $_POST[ $hidden_field_name ] == 'Y' ) {
        // get the values from the POST
        $new_page_val = ($_POST[ $page_field_name ] == "on") ? "1" : "0";
        $new_post_val = ($_POST[ $post_field_name ] == "on") ? "1" : "0";
        // save the new values
        if ( $new_page_val != $page_val ) {
            update_option( $page_name, $new_page_val );
            $page_val = $new_page_val;
        }
        if ( $new_post_val != $post_val ) {
            update_option( $post_name, $new_post_val );
            $post_val = $new_post_val;
        }
        // Feedback that we've updated the options
?>
<div class="updated"><p><strong><?php _e('Options saved.', 'mt_trans_domain' ); ?></strong></p></div>
<?php
    } // END CHECKING POST
?>
<div class="wrap">
    <?php echo "<h2>" . __( 'Revision History Options', 'mt_trans_domain' ) . "</h2>"; ?>

    <form name="show_revision_history" method="post" action="">
        <input type="hidden" name="<?php echo $hidden_field_name; ?>" value="Y">
        <p>
            <input id="show_on_pages" type="checkbox" name="<?php echo $page_field_name; ?>" 
                <?php checked('1', $page_val); ?> />
            <label for="show_on_pages"><?php _e("Show revision history on pages.", 'mt_trans_domain' ); ?></label>
        </p>
        <p>
            <input id="show_on_posts" type="checkbox" name="<?php echo $post_field_name; ?>" 
                <?php checked('1', $post_val); ?> />
            <label for="show_on_posts"><?php _e("Show revision history on posts.", 'mt_trans_domain' ); ?></label>
        </p>
        <p class="submit">
            <input type="submit" name="Submit" value="<?php _e('Update Options', 'mt_trans_domain' ) ?>" />
        </p>
    </form>
</div>
<?php
} // END add_revision_history_options_page


?>
