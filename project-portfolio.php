<?php
/*
Plugin Name: Project Portfolio
Description: Adds a custom post type for managing and displaying project portfolios.
Author: Ahmed Shaikh
GitHub: https://github.com/AhmedShaikh0
*/

// Register the "Project" custom post type
function pp_register_project_post_type() {
    register_post_type('project', array(
        'labels' => array(
            'name' => __('Projects'),
            'singular_name' => __('Project'),
            'add_new' => __('Add New Project'),
            'add_new_item' => __('Add New Project'),
            'edit_item' => __('Edit Project'),
            'new_item' => __('New Project'),
            'view_item' => __('View Project'),
            'search_items' => __('Search Projects'),
            'not_found' => __('No projects found'),
            'menu_name' => __('Projects')
        ),
        'public' => true,
        'menu_position' => 5,
        'supports' => array('title'),
        'has_archive' => true,
        'show_in_menu' => true,
        'rewrite' => array('slug' => 'project'),
    ));

    // Make sure permalinks are updated
    flush_rewrite_rules();
}
add_action('init', 'pp_register_project_post_type');

// Add custom meta boxes to project post type
function pp_add_meta_boxes() {
    add_meta_box(
        'pp_project_details',
        'Project Details',
        'pp_project_meta_box_callback',
        'project'
    );
}
add_action('add_meta_boxes', 'pp_add_meta_boxes');

// Render the meta box fields
function pp_project_meta_box_callback($post) {
    $description = get_post_meta($post->ID, '_project_description', true);
    $client = get_post_meta($post->ID, '_project_client', true);
    $date = get_post_meta($post->ID, '_project_date', true);
    $url = get_post_meta($post->ID, '_project_url', true);
    ?>

    <p><label>Description:</label><br>
    <textarea name="project_description" rows="4" cols="50"><?php echo esc_textarea($description); ?></textarea></p>

    <p><label>Client Name:</label><br>
    <input type="text" name="project_client" value="<?php echo esc_attr($client); ?>"></p>

    <p><label>Completion Date:</label><br>
    <input type="date" name="project_date" value="<?php echo esc_attr($date); ?>"></p>

    <p><label>Project URL:</label><br>
    <input type="url" name="project_url" value="<?php echo esc_url($url); ?>"></p>

    <?php
}

// Save the custom meta fields
function pp_save_project_meta($post_id) {
    // Avoid autosaves
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;

    // Save each field if it's present
    if (isset($_POST['project_description'])) {
        update_post_meta($post_id, '_project_description', sanitize_textarea_field($_POST['project_description']));
    }

    if (isset($_POST['project_client'])) {
        update_post_meta($post_id, '_project_client', sanitize_text_field($_POST['project_client']));
    }

    if (isset($_POST['project_date'])) {
        update_post_meta($post_id, '_project_date', sanitize_text_field($_POST['project_date']));
    }

    if (isset($_POST['project_url'])) {
        update_post_meta($post_id, '_project_url', esc_url_raw($_POST['project_url']));
    }
}
add_action('save_post', 'pp_save_project_meta');

// Add submenu for a custom Project Dashboard
function pp_add_admin_menu() {
    add_submenu_page(
        'edit.php?post_type=project',
        'Project Dashboard',
        'Project Dashboard',
        'manage_options',
        'project-dashboard',
        'pp_render_admin_page'
    );
}
add_action('admin_menu', 'pp_add_admin_menu');

// Render the admin dashboard page with a table of projects
function pp_render_admin_page() {
    $projects = get_posts(array(
        'post_type' => 'project',
        'numberposts' => -1
    ));
    ?>
    <div class="wrap">
        <h1>All Projects</h1>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Client</th>
                    <th>Completion Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($projects as $project): ?>
                    <tr>
                        <td><?php echo esc_html($project->post_title); ?></td>
                        <td><?php echo esc_html(get_post_meta($project->ID, '_project_client', true)); ?></td>
                        <td><?php echo esc_html(get_post_meta($project->ID, '_project_date', true)); ?></td>
                        <td>
                            <a href="<?php echo get_edit_post_link($project->ID); ?>">Edit</a> |
                            <a href="<?php echo get_delete_post_link($project->ID); ?>" onclick="return confirm('Are you sure?')">Delete</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <p><a class="button-primary" href="post-new.php?post_type=project">Add New Project</a></p>
    </div>
    <?php
}

// Create a shortcode to display all projects on the frontend
function pp_project_shortcode() {
    $output = '';
    $projects = get_posts(array('post_type' => 'project', 'numberposts' => -1));

    foreach ($projects as $project) {
        $title = esc_html($project->post_title);
        $url = esc_url(get_post_meta($project->ID, '_project_url', true));
        $desc = esc_html(get_post_meta($project->ID, '_project_description', true));
        $client = esc_html(get_post_meta($project->ID, '_project_client', true));
        $date = esc_html(get_post_meta($project->ID, '_project_date', true));

        $output .= "<div class='project-item'>";
        $output .= "<h2><a href='{$url}' target='_blank'>{$title}</a></h2>";
        $output .= "<p><strong>Description:</strong> {$desc}</p>";
        $output .= "<p><strong>Client:</strong> {$client}</p>";
        $output .= "<p><strong>Completion Date:</strong> {$date}</p>";
        $output .= "</div><hr>";
    }

    return $output;
}
add_shortcode('project_portfolio', 'pp_project_shortcode');

// Display custom fields on the single project page
function pp_display_project_fields_on_single($content) {
    if (is_singular('project') && in_the_loop() && is_main_query()) {
        $post_id = get_the_ID();
        $description = get_post_meta($post_id, '_project_description', true);
        $client = get_post_meta($post_id, '_project_client', true);
        $date = get_post_meta($post_id, '_project_date', true);
        $url = get_post_meta($post_id, '_project_url', true);

        $meta_content = '<div class="project-details">';
        $meta_content .= '<p><strong>Description:</strong> ' . esc_html($description) . '</p>';
        $meta_content .= '<p><strong>Client:</strong> ' . esc_html($client) . '</p>';
        $meta_content .= '<p><strong>Completion Date:</strong> ' . esc_html($date) . '</p>';
        $meta_content .= '<p><strong>Project URL:</strong> <a href="' . esc_url($url) . '" target="_blank">' . esc_html($url) . '</a></p>';
        $meta_content .= '</div>';

        return $content . $meta_content;
    }

    return $content;
}
add_filter('the_content', 'pp_display_project_fields_on_single');
?>
