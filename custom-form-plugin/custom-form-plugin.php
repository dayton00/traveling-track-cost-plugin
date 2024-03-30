<?php
/*
Plugin Name: Custom Form Plugin
Description: A simple plugin to add a custom form and display data in a table with delete functionality.
Version: 1.0
*/

// Enqueue jQuery and Google Maps API
function custom_form_enqueue_scripts() {
    wp_enqueue_script('jquery');
    wp_enqueue_script('google-maps', 'https://maps.googleapis.com/maps/api/js?key=AIzaSyC0PZPQf-_NlXwYCfITL1fXJN7Qr9_9suY&libraries=places', array(), null, true);
    wp_enqueue_script('custom-script', plugin_dir_url(__FILE__) . 'custom.js', array('jquery', 'google-maps'), null, true);
}
add_action('wp_enqueue_scripts', 'custom_form_enqueue_scripts');

// Create custom table in the database
function custom_form_create_table() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'custom_form_data';
    $charset_collate = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        email varchar(255) NOT NULL,
        from_location varchar(255) NOT NULL,
        to_location varchar(255) NOT NULL,
        price decimal(10, 2),
        PRIMARY KEY  (id)
    ) $charset_collate;";
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
register_activation_hook(__FILE__, 'custom_form_create_table');

// Add shortcode to display form and data
function custom_form_shortcode() {
    ob_start();
    custom_form_process_form();
    custom_form_display_form();
    if (isset($_POST['show_activity'])) {
        $email = sanitize_email($_POST['email']);
        custom_form_display_data_by_email($email);
    } elseif (isset($_POST['submit'])) {
        custom_form_display_data();
    } else {
        custom_form_show_activity_button();
    }
    return ob_get_clean();
}
add_shortcode('custom_form', 'custom_form_shortcode');

// Process form submission
function custom_form_process_form() {
    global $wpdb;
    if (isset($_POST['submit'])) {
        $email = sanitize_email($_POST['email']);
        $from = sanitize_text_field($_POST['from']);
        $to = sanitize_text_field($_POST['to']);
        $price = floatval($_POST['price']);

        $table_name = $wpdb->prefix . 'custom_form_data';
        $wpdb->insert($table_name, array(
            'email' => $email,
            'from_location' => $from,
            'to_location' => $to,
            'price' => $price
        ));
    }
    // Handle delete request
    if (isset($_POST['delete'])) {
        $id = intval($_POST['delete']);
        $table_name = $wpdb->prefix . 'custom_form_data';
        $wpdb->delete($table_name, array('id' => $id));
    }
}

// Display form
function custom_form_display_form() {
    ?>
    <style>
        /* Add CSS styling for the form */
        .custom-form-container {
            max-width: 400px;
            margin: 20px auto;
            padding: 20px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        .custom-form-container label {
            display: block;
            margin-bottom: 10px;
        }

        .custom-form-container input[type="text"],
        .custom-form-container input[type="email"],
        .custom-form-container input[type="number"] {
            width: 100%;
            padding: 8px;
            margin-bottom: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
        }

        .custom-form-container input[type="submit"] {
            background-color: #4CAF50;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .custom-form-container input[type="submit"]:hover {
            background-color: #45a049;
        }
    </style>
    <div class="custom-form-container">
    <form method="post" action="">
        <div class="form-group1">
            <label for="email">Email:</label>
            <input type="email" name="email" id="email" required>
        </div>

        <div class="form-group2">
            <label for="from">From:</label>
            <input type="text" name="from" id="from" autocomplete="from" required>
        </div>

        <div class="form-group3">
            <label for="to">To:</label>
            <input type="text" name="to" id="to" autocomplete="to" required>
        </div>

        <div class="form-group4">
            <label for="price">Price:</label>
            <input type="number" name="price" id="price" step="0.01" min="0" required>
        </div>

        <input type="submit" name="submit" value="Submit">
    </form>
    </div>
<?php
}

// Display data in a table filtered by email
function custom_form_display_data_by_email($email) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'custom_form_data';
    $results = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name WHERE email = %s", $email), ARRAY_A);
    if ($results) {
        echo '<table><tr><th>ID</th><th>Email</th><th>From</th><th>To</th><th>Price</th><th>Action</th></tr>';
        foreach ($results as $row) {
            echo '<tr>';
            echo '<td>' . $row['id'] . '</td>';
            echo '<td>' . $row['email'] . '</td>';
            echo '<td>' . $row['from_location'] . '</td>';
            echo '<td>' . $row['to_location'] . '</td>';
            echo '<td>' . $row['price'] . '</td>';
            echo '<td>';
            echo '<form method="post">';
            echo '<input type="hidden" name="delete" value="' . $row['id'] . '">';
            echo '<button type="submit" name="submit_delete">Delete</button>';
            echo '</form>';
            echo '</td>';
            echo '</tr>';
        }
        echo '</table>';
    } else {
        echo 'No activity found for this email.';
    }
}

// Display form to filter data by email
function custom_form_show_activity_button() {
    ?>
    <div class="custom-form-container">
        <form method="post" action="">
            <label for="email">Enter Email to Show Activity:</label>
            <input type="email" name="email" id="email" required>
            <input type="submit" name="show_activity" value="Show My Activity">
        </form>
    </div>
<?php
}

// Add menu item to the WordPress dashboard settings menu
function custom_form_settings_menu() {
    add_options_page('Custom Form Settings', 'Custom Form Settings', 'manage_options', 'custom-form-settings', 'custom_form_settings_page');
}
add_action('admin_menu', 'custom_form_settings_menu');

// Display settings page content
function custom_form_settings_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'custom_form_data';

    // Handle delete request
    if (isset($_POST['submit_delete'])) {
        $id = intval($_POST['delete']);
        $wpdb->delete($table_name, array('id' => $id));
    }

    // Fetch all data
    $results = $wpdb->get_results("SELECT * FROM $table_name", ARRAY_A);

    ?>
    <div class="wrap">
        <h2>Custom Form Plugin Settings</h2>
        <p>Add the following shortcode to display the custom form:</p>
        <code>[custom_form]</code>
        <h3>Inserted Data</h3>
        <?php if (!empty($results)) : ?>
            <table>
                <tr>
                    <th>ID</th>
                    <th>Email</th>
                    <th>From</th>
                    <th>To</th>
                    <th>Price</th>
                    <th>Action</th>
                </tr>
                <?php foreach ($results as $row) : ?>
                    <tr>
                        <td><?php echo $row['id']; ?></td>
                        <td><?php echo $row['email']; ?></td>
                        <td><?php echo $row['from_location']; ?></td>
                        <td><?php echo $row['to_location']; ?></td>
                        <td><?php echo $row['price']; ?></td>
                        <td>
                            <form method="post">
                                <input type="hidden" name="delete" value="<?php echo $row['id']; ?>">
                                <button type="submit" name="submit_delete">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        <?php else : ?>
            <p>No data found.</p>
        <?php endif; ?>
    </div>
<?php
}
