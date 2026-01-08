<?php

/**
 * Plugin Name: Scheduled Email
 * Description: Schedules and sends emails and Omnisend events after a WooCommerce purchase, managing them via a custom database table and a single cron job.
 * Version: 2.5
 * Author: Lead Academy
 */

if (!defined('ABSPATH')) exit;

define( 'FACE_2_FACE_PRODUCT_CODES_OMNISEND', [354284,366854,376417,413582,376420,377824,408597,368595,382016,386971,410644,420510,371100,380325,388994,413724,389230,387587,413716,391079,391063,389109,394220,409404,413703,414748,414746,414685, 421612, 435450 , 438477, 438480, 436829,440128 , 439294, 440908, 441734, 441758, 443472, 443706, 448459,448192,446501,446674,447327,448877,451550,457511,457585, 463897,465279]);

define('PHLEBOTOMY_TRAINING_OMNISEND', [366854,354284,376417,376420,377824,408597,463897,465279,457511]);

$debug_moode_emails = false;

// Function to format date like "1st November, 2025"
function format_course_date($date_string) {
    $timestamp = strtotime($date_string);
    $day = date('j', $timestamp);
    $month = date('F', $timestamp);
    $year = date('Y', $timestamp);

    // Add ordinal suffix
    if ($day > 3 && $day < 21) {
        $suffix = 'th';
    } else {
        switch ($day % 10) {
            case 1: $suffix = 'st'; break;
            case 2: $suffix = 'nd'; break;
            case 3: $suffix = 'rd'; break;
            default: $suffix = 'th'; break;
        }
    }

    return $day . $suffix . ' ' . $month . ', ' . $year;
}

// --- Plugin Activation/Deactivation Hooks ---
register_activation_hook(__FILE__, 'create_email_schedule_table');
function create_email_schedule_table()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'scheduled_emails';
    $charset_collate = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        mailto VARCHAR(255) NOT NULL,
        subject VARCHAR(255) NOT NULL,
        content TEXT NOT NULL,
        order_id BIGINT(20) DEFAULT NULL,
        product_id BIGINT(20) DEFAULT NULL,
        variation_id BIGINT(20) DEFAULT NULL,
        scheduled_time DATETIME NOT NULL,
        sent_time TEXT DEFAULT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
}

register_deactivation_hook(__FILE__, 'clear_email_cron_job');
function clear_email_cron_job()
{
    wp_clear_scheduled_hook('send_scheduled_emails');
}


// --- Refund Handling ---
add_action('woocommerce_order_status_refunded', 'remove_email_by_order_id', 10, 1);
function remove_email_by_order_id($order_id)
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'scheduled_emails';
    $wpdb->delete($table_name, ['order_id' => $order_id], ['%d']);
}

// --- Core Scheduling Logic ---
add_action('woocommerce_order_status_completed', 'schedule_events_on_purchase', 10, 1);
function schedule_events_on_purchase($order_id, $return = false)
{
    global $wpdb, $debug_moode_emails;
    error_log("Scheduled Email Plugin: Starting scheduling for Order ID $order_id");
    $order = wc_get_order($order_id);
    if (!$order) {
        error_log("Scheduled Email Plugin: Order ID $order_id not found");
        return;
    }

    $table_name = $wpdb->prefix . 'scheduled_emails';
    $customer_email = $debug_moode_emails ? "ferdous935174@gmail.com" : $order->get_billing_email();
    error_log("Scheduled Email Plugin: Customer email for Order ID $order_id: $customer_email");


    foreach ($order->get_items() as $item) {
        $product_id = $item->get_product_id();
        $product = $item->get_product();
        $meta_id = ($product_id == 366854) ? 354284 : $product_id;
        $three_days_ids = [368595, 382016, 386971, 410644, 394220, 409404];
        $variation_id = $item->get_variation_id();
        $la_phleb_course_meta_group = get_post_meta($meta_id, 'la_phleb_course_meta_group', true);

        // Collect course name from product ID
        $product_obj = wc_get_product($product_id);
        $course_name = $product_obj ? $product_obj->get_name() : 'Unknown Course';

        error_log("Scheduled Email Plugin: Product ID: $product_id, Course Name: $course_name");
    
        $first_date = "";
        $middle_date = "";
        $last_date = "";
        $variation_name = "";
        $variation_data = $item->get_meta_data();
        foreach ($variation_data as $meta) {
            if ($meta->key === 'courses') $variation_name = $meta->value;
        }

        if (is_array($la_phleb_course_meta_group)) {
            foreach ($la_phleb_course_meta_group as $course) {
                if (isset($course['la_phleb_course_var_id']) && $course['la_phleb_course_var_id'] == $variation_id) {
                    $first_date = $course['adv_course_date'];
                    $middle_date = $course['adv_course_middle_date'] ?? '';
                    $last_date = in_array($product_id, $three_days_ids) ? ($course['adv_course_last_date'] ?? $course['adv_course_date']) : $course['adv_course_date'];
                    break;
                }
            }
        }

        if (in_array($product_id, FACE_2_FACE_PRODUCT_CODES_OMNISEND)) {
            if ($course_name === 'Unknown Course' || empty($course_name)) {
                continue;
            }

            $location = get_post_meta($meta_id, 'la_phleb_course_location_root', true);

            // Location based review email
            $review_properties = ["location" => $location];
            $review_subject = "Get a £10 Gift Card by Writing a Review";
            $review_time = date('Y-m-d H:i:s', strtotime("{$last_date} 5:00pm"));

            $insert_result = $wpdb->insert($table_name, [
                'mailto'         => $customer_email,
                'subject' => $review_subject,
                'content'        => json_encode(['eventName' =>  'google_map_review', 'properties' => $review_properties]),
                'scheduled_time' => $review_time,
                'order_id'       => $order_id,
                'product_id' => $product_id,
                'variation_id' => $variation_id,
            ]);
            if ($insert_result) {
                error_log("Scheduled Email Plugin: Location based review email Inserted review event for Order ID $order_id, Product ID $product_id");
            } else {
                error_log("Scheduled Email Plugin: Location based review email Failed to insert review event for Order ID $order_id, Product ID $product_id: " . $wpdb->last_error);
            }

            require_once plugin_dir_path(__FILE__) . 'marketing.php';

            $special_template_status = get_post_meta($meta_id, 'la_phleb_special_template_status', true);

            $reminder_properties = [
                "product-id" => $product_id,
                "Course_Name" => $course_name,
                "Course_Date" => $variation_name ?: "",
                "location" => $location,
                "special_template_status" => $special_template_status ?: 'No',
            ];

            // Schedule Reminder mail event
            $reminder_time = date('Y-m-d H:i:s', strtotime("$first_date 10:00 AM -1 day"));
            $reminder_subject = 'Reminder - Venue Details of ' . $course_name;
            $insert_result = $wpdb->insert($table_name, [
                'mailto'         => $customer_email,
                'subject' => $reminder_subject,
                'content'        => json_encode(['eventName' => 'Reminder_Mail', 'properties' => $reminder_properties]),
                'scheduled_time' => $reminder_time,
                'order_id'       => $order_id,
                'product_id' => $product_id,
                'variation_id' => $variation_id,
            ]);
            if ($insert_result) {
                error_log("Scheduled Email Plugin: Schedule Reminder mail Inserted reminder event for Order ID $order_id, Product ID $product_id");
            } else {
                error_log("Scheduled Email Plugin: Schedule Reminder mail Failed to insert reminder event for Order ID $order_id, Product ID $product_id: " . $wpdb->last_error);
            }

            // Schedule Feedback Form Event
            $feedback_date = $last_date ?: $first_date;
            $feedback_time = date('Y-m-d H:i:s', strtotime("$feedback_date 4:45 PM"));
            $insert_result = $wpdb->insert($table_name, [
                'mailto'         => $customer_email,
                'subject' => 'Your thoughts matter! Share your feedback now',
                'content'        => json_encode(['eventName' => 'feedback_form']),
                'scheduled_time' => $feedback_time,
                'order_id'       => $order_id,
                'product_id' => $product_id,
                'variation_id' => $variation_id,
            ]);
            if ($insert_result) {
                error_log("Scheduled Email Plugin: Feedback Email Inserted feedback event for Order ID $order_id, Product ID $product_id");
            } else {
                error_log("Scheduled Email Plugin: Feedback Email Failed to insert feedback event for Order ID $order_id, Product ID $product_id: " . $wpdb->last_error);
            }
        }

        // Shedule Nail Technician Email Event
        if ($product_id == 394220 && !empty($first_date)) {
            $nail_time = date('Y-m-d H:i:s', strtotime("$first_date +24 hours"));
            $insert_result = $wpdb->insert($table_name, [
                'mailto'         => $customer_email,
                'subject' => 'URGENT: Need to Bring Few Essential Materials for Upcoming Nail Technician Training',
                'content'        => json_encode(['eventName' => 'nail_technician','properties' => []]),
                'scheduled_time' => $nail_time,
                'order_id'       => $order_id,
                'product_id' => $product_id,
                'variation_id' => $variation_id,
            ]);
            if ($insert_result) {
                error_log("Scheduled Email Plugin: Nail Technician Training Inserted feedback event for Order ID $order_id, Product ID $product_id");
            } else {
                error_log("Scheduled Email Plugin: Nail Technician Training Failed to insert feedback event for Order ID $order_id, Product ID $product_id: " . $wpdb->last_error);
            }
        }

        // Phlebotomy Training for theory part
        if (in_array($product_id, PHLEBOTOMY_TRAINING_OMNISEND) && !empty($first_date)) {
            $phle_time = date('Y-m-d H:i:s', strtotime("$first_date 4:45 PM -72 hours"));
            $insert_result = $wpdb->insert($table_name, [
                'mailto'         => $customer_email,
                'subject' => 'Phlebotomy Reminder Email for completing theory Part',
                'content'        => json_encode(['eventName' => 'phle_reminder_mail']),
                'scheduled_time' => $phle_time,
                'order_id'       => $order_id,
                'product_id' => $product_id,
                'variation_id' => $variation_id,
            ]);
            if ($insert_result) {
                error_log("Scheduled Email Plugin: Phlebotomy Training for Theory Inserted feedback event for Order ID $order_id, Product ID $product_id");
            } else {
                error_log("Scheduled Email Plugin: Phlebotomy Training for Theory Failed to insert feedback event for Order ID $order_id, Product ID $product_id: " . $wpdb->last_error);
            }
        }
    }

    if ($return) {
        wp_send_json_success(['message' => 'Events successfully scheduled.']);
    }
}


// --- Cron Job to Process Scheduled Events ---
add_action('init', 'register_email_cron');
function register_email_cron()
{
    if (!wp_next_scheduled('send_scheduled_emails')) {
        wp_schedule_event(time(), 'hourly', 'send_scheduled_emails');
    }
}

add_action('send_scheduled_emails', 'process_scheduled_emails');
function process_scheduled_emails()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'scheduled_emails';
    $emails = $wpdb->get_results("SELECT * FROM $table_name WHERE sent_time IS NULL AND scheduled_time < NOW()");

    foreach ($emails as $email) {

        $payload = json_decode($email->content, true);

        if (strpos($email->content, "omnisend, ") !== false) {
            $course_title = str_replace("omnisend, ", "", $email->content);
            $data = [
                "contact" => ["email" => $email->mailto],
                "origin" => "api",
                "eventName" => "marketing",
                "properties" => ["Course_Title" => $course_title]
            ];
            $response = wp_remote_post("https://api.omnisend.com/v5/events", [
                'headers' => [
                    'X-API-KEY' => '644a6c6f71a2f8c907940b48-ZPvXST3Zm2mzhZ5hnUqnBLZJOPRegnEqWuJTCd7J4SuvJhKrQF',
                    'Content-Type' => 'application/json',
                ],
                'body' => json_encode($data),
            ]);
            if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) < 300) {
                $wpdb->update($table_name, ['sent_time' => current_time('mysql')], ['id' => $email->id]);
            }
        } elseif (json_last_error() === JSON_ERROR_NONE && isset($payload['eventName'])) {
            error_log('Omnisend Request Payload: ' . print_r($payload, true));
            $data = [
                "contact"    => ["email" => $email->mailto],
                "origin"     => "api",
                "eventName"  => $payload['eventName'],
                "properties" => (object)($payload['properties'] ?? []),
                
            ];
            $response = wp_remote_post("https://api.omnisend.com/v5/events", [
                'headers' => [
                    'X-API-KEY' => '644a6c6f71a2f8c907940b48-ZPvXST3Zm2mzhZ5hnUqnBLZJOPRegnEqWuJTCd7J4SuvJhKrQF',
                    'Content-Type' => 'application/json',
                ],
                'body' => json_encode($data),
            ]);
            if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) < 300) {
                $wpdb->update($table_name, ['sent_time' => current_time('mysql')], ['id' => $email->id]);
            } else {
                $error_message = is_wp_error($response) ? $response->get_error_message() : wp_remote_retrieve_body($response);
                error_log("Scheduled Email Plugin: Failed to send Omnisend event for Order ID " . $email->order_id . ". Response: " . $error_message);
            }
        } else {
            $headers = ['Content-Type: text/html; charset=UTF-8'];
            if (wp_mail($email->mailto, $email->subject, $email->content, $headers)) {
                $wpdb->update($table_name, ['sent_time' => current_time('mysql')], ['id' => $email->id]);
            }
        }
    }

    error_log('✅ send_scheduled_emails hook fired at: ' . current_time('mysql'));
}



// --- Admin Interface & AJAX Handlers (Unchanged) ---

add_action('admin_menu', function () {
    $hook = add_menu_page('Scheduled Email', 'Scheduled Email', 'manage_options', 'scheduled-emails', 'view_scheduled_emails', 'dashicons-email-alt', 24);
    add_action("load-$hook", function () {
        add_screen_option('per_page', ['label' => 'Emails per page', 'default' => 15, 'option' => 'emails_per_page']);
    });
});

add_filter('set-screen-option', function ($status, $option, $value) {
    return ($option === 'emails_per_page') ? (int) $value : $status;
}, 10, 3);

add_action('admin_enqueue_scripts', function () {
    $screen = get_current_screen();
    if ($screen && $screen->id === 'toplevel_page_scheduled-emails') {
        // Enqueue WP Editor (TinyMCE) scripts
        wp_enqueue_editor();
        wp_enqueue_media();

        wp_enqueue_script('scheduled-emails-js', plugin_dir_url(__FILE__) . 'scripts.js', ['jquery', 'wp-editor'], time(), true);
        wp_enqueue_style('scheduled-emails-styles', plugins_url('styles.css', __FILE__), '', time());
        wp_localize_script('scheduled-emails-js', 'scheduledAjax', ['ajaxUrl' => admin_url('admin-ajax.php'), 'nonce' => wp_create_nonce('update_content_nonce')]);
    }
});

function view_scheduled_emails()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'scheduled_emails';

    // Handle Delete/Move to Bin Action
    if (!empty($_GET['delete_email']) && is_numeric($_GET['delete_email'])) {
        $email_id = intval($_GET['delete_email']);
        $sent_time = $wpdb->get_var($wpdb->prepare("SELECT sent_time FROM $table_name WHERE id = %d", $email_id));
        $new_sent_time = ($sent_time === 'Deleted') ? 'Bin' : 'Deleted';
        $wpdb->update($table_name, ['sent_time' => $new_sent_time], ['id' => $email_id]);
        echo "<div class='notice notice-success is-dismissible'><p>Item moved to '$new_sent_time' folder.</p></div>";
    }

    // Handle Bulk Delete by Variation
    if (isset($_POST['delete']) && $_POST['delete'] == 1 && !empty($_POST['variation'])) {
        list($variation_id) = explode('~', $_POST['variation']);
        $rows_affected = $wpdb->delete($table_name, ['variation_id' => $variation_id], ['%d']);
        echo '<div class="notice notice-error is-dismissible"><h3>🚨 Items Deleted!</h3><p><strong>' . esc_html($rows_affected) . '</strong> items deleted for Variation ID: <strong>' . esc_html($variation_id) . '</strong></p></div>';
    }

    // Handle Bulk Change Date by Variation
    if (isset($_POST['change']) && $_POST['change'] == 1 && !empty($_POST['variation']) && !empty($_POST['newDate'])) {
        $new_date = $_POST['newDate'];
        list($variation_id, $old_date) = explode('~', $_POST['variation']);
        $schedules = $wpdb->get_results($wpdb->prepare("SELECT id, scheduled_time, subject, content FROM $table_name WHERE variation_id = %d AND (sent_time IS NULL OR sent_time = '')", $variation_id));
        if (!empty($schedules) && strtotime($old_date) !== false) {
            $date_diff = strtotime($new_date) - strtotime($old_date);
            $updated_count = 0;
            foreach ($schedules as $schedule) {
                $new_timestamp = strtotime($schedule->scheduled_time) + $date_diff;
                $new_scheduled_time = date('Y-m-d H:i:s', $new_timestamp);
                $update_data = ['scheduled_time' => $new_scheduled_time, 'sent_time' => null];

                // Update Course_Date in payload for reminder emails
                if (strpos($schedule->subject, 'Reminder - Venue Details of ') === 0) {
                    $payload = json_decode($schedule->content, true);
                    if (json_last_error() === JSON_ERROR_NONE && isset($payload['properties']['Course_Date'])) {
                        $payload['properties']['Course_Date'] = format_course_date($new_date);
                        $update_data['content'] = json_encode($payload);
                    }
                }

                if ($wpdb->update($table_name, $update_data, ['id' => $schedule->id])) {
                    $updated_count++;
                }
            }
            echo '<div class="notice notice-success is-dismissible"><h3>✅ Dates Successfully Changed!</h3><p><strong>' . esc_html($updated_count) . '</strong> unsent items updated for Variation ID: <strong>' . esc_html($variation_id) . '</strong></p></div>';
        } else {
            echo '<div class="notice notice-warning is-dismissible"><h3>No unsent schedules found for Variation ID: ' . esc_html($variation_id) . '.</h3></div>';
        }
    }

    // Pagination & Filtering
    $per_page = get_user_meta(get_current_user_id(), 'emails_per_page', true) ?: 15;
    $current_page = max(1, intval($_GET['paged'] ?? 1));
    $offset = ($current_page - 1) * $per_page;
    $filter_query = sanitize_text_field($_GET['r'] ?? '');
    $search_query = sanitize_text_field($_GET['s'] ?? '');
    $where_conditions = [];
    if (empty($filter_query)) $where_conditions[] = "(sent_time IS NULL OR sent_time NOT IN ('Bin', 'Deleted'))";
    elseif ($filter_query === 'sent') $where_conditions[] = "sent_time IS NOT NULL AND sent_time NOT IN ('Bin', 'Deleted')";
    elseif ($filter_query === 'schedule') $where_conditions[] = "sent_time IS NULL";
    else $where_conditions[] = $wpdb->prepare("sent_time = %s", $filter_query);

    if (!empty($search_query)) {
        $where_conditions[] = $wpdb->prepare("(mailto LIKE %s OR subject LIKE %s OR order_id LIKE %s OR product_id LIKE %s OR variation_id LIKE %s)", "%$search_query%", "%$search_query%", "%$search_query%", "%$search_query%", "%$search_query%");
    }
    $where_sql = 'WHERE ' . implode(' AND ', $where_conditions);
    $total_items = $wpdb->get_var("SELECT COUNT(*) FROM $table_name $where_sql");
    $emails = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name $where_sql ORDER BY scheduled_time ASC LIMIT %d OFFSET %d", $per_page, $offset));
    $pagination_links = paginate_links(['base' => add_query_arg('paged', '%#%'), 'format' => '', 'current' => $current_page, 'total' => ceil($total_items / $per_page), 'prev_text' => '&laquo;', 'next_text' => '&raquo;']);

    echo "<div class='wrap se-admin-wrap'><h1>Scheduled Emails &amp; Events</h1>";
?>
    <!-- Bulk Actions Card -->
    <div class="se-card se-bulk-card">
        <div class="se-card-header">
            <div class="se-card-header-content">
                <svg class="se-card-icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/><path d="M19 3v4"/><path d="M21 5h-4"/></svg>
                <div>
                    <h3 class="se-card-title">Bulk Actions</h3>
                    <p class="se-card-description">Manage multiple scheduled emails at once</p>
                </div>
            </div>
        </div>
        <div class="se-card-body">
            <form method="post" class="se-bulk-actions-form">
                <div class="se-bulk-grid">
                    <div class="se-form-group">
                        <label class="se-form-label" for="course">
                            Product
                            <span class="se-form-label-hint">Select a course</span>
                        </label>
                        <div class="se-select-wrapper">
                            <select name="product" id="course" class="se-form-select">
                                <option value="">Select product...</option>
                                <?php foreach (FACE_2_FACE_PRODUCT_CODES_OMNISEND as $id):
                                    $product = wc_get_product($id);
                                    if (!$product) continue;
                                    $selected = (isset($_POST['product']) && $id == $_POST['product']) ? 'selected' : '';
                                    echo "<option {$selected} value='{$id}'>{$id} ~ {$product->get_name()}</option>";
                                endforeach; ?>
                            </select>
                            <svg class="se-select-chevron" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                        </div>
                    </div>
                    <div class="se-form-group">
                        <label class="se-form-label" for="date">
                            Variation Date
                            <span class="se-form-label-hint">Current date to modify</span>
                        </label>
                        <div class="se-select-wrapper">
                            <select name="variation" id="date" class="se-form-select">
                                <option>Select variation...</option>
                                <?php foreach (FACE_2_FACE_PRODUCT_CODES_OMNISEND as $id):
                                    $meta_id = ($id == 366854) ? 354284 : $id;
                                    $meta_group = get_post_meta($meta_id, 'la_phleb_course_meta_group', true);
                                    $product = wc_get_product($id);
                                    if (!$product || !is_array($meta_group)) continue;
                                    usort($meta_group, fn($a, $b) => strtotime($a['adv_course_date'] ?? 0) - strtotime($b['adv_course_date'] ?? 0));
                                    foreach ($meta_group as $variation):
                                        if (empty($variation['la_phleb_course_var_id'])) continue;
                                        $val = $variation['la_phleb_course_var_id'] . '~' . $variation['adv_course_date'];
                                        $is_selected_product = isset($_POST['product']) && $_POST['product'] == $id;
                                        $display = $is_selected_product ? '' : 'style="display:none;"';
                                        echo "<option {$display} data-course='{$id}' value='{$val}'>{$val}</option>";
                                    endforeach;
                                endforeach; ?>
                            </select>
                            <svg class="se-select-chevron" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m6 9 6 6 6-6"/></svg>
                        </div>
                    </div>
                    <div class="se-form-group">
                        <label class="se-form-label" for="newDate">
                            New Date
                            <span class="se-form-label-hint">Target date</span>
                        </label>
                        <input type="date" name="newDate" id="newDate" class="se-form-input" />
                    </div>
                </div>
                <div class="se-bulk-footer">
                    <div class="se-bulk-footer-hint">
                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4"/><path d="M12 8h.01"/></svg>
                        <span>Actions will apply to all emails matching the selected variation</span>
                    </div>
                    <div class="se-bulk-actions-buttons">
                        <button name="delete" value="1" class="se-btn se-btn-outline-danger" type="submit" onclick="return confirm('Are you sure you want to delete all emails for this variation?')">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 6h18"/><path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/><path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/><line x1="10" x2="10" y1="11" y2="17"/><line x1="14" x2="14" y1="11" y2="17"/></svg>
                            Delete All
                        </button>
                        <button name="change" value="1" class="se-btn se-btn-primary" type="submit" onclick="return confirm('Are you sure you want to change dates?')">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12a9 9 0 0 0-9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/><path d="M3 12a9 9 0 0 0 9 9 9.75 9.75 0 0 0 6.74-2.74L21 16"/><path d="M16 21h5v-5"/></svg>
                            Change Date
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

<?php
    echo '<div class="search-filter">';
    echo '<ul class="subsubsub">';
    // Use tokens that match how rows are stored and filtered
    $statuses = [
        'All' => '',
        'Sent' => 'sent',           // sent_time IS NOT NULL AND NOT in Bin/Deleted
        'Scheduled' => 'schedule',   // sent_time IS NULL
        'Deleted' => 'Deleted',      // exact text stored in sent_time
        'Bin' => 'Bin'               // exact text stored in sent_time
    ];
    foreach ($statuses as $label => $status) {
        if ($status === '') {
            $count_where = "(sent_time IS NULL OR (sent_time IS NOT NULL AND sent_time NOT IN ('Bin', 'Deleted')))";
        } elseif ($status === 'sent') {
            $count_where = "(sent_time IS NOT NULL AND sent_time NOT IN ('Bin', 'Deleted'))";
        } elseif ($status === 'schedule') {
            $count_where = "(sent_time IS NULL)";
        } else {
            // 'Deleted' or 'Bin' exact match
            $count_where = $wpdb->prepare("sent_time = %s", $status);
        }
        $count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name WHERE $count_where");
        $class = ($filter_query === $status) ? 'class="current"' : '';
        echo "<li><a href='" . esc_url(add_query_arg('r', $status)) . "' $class>$label <span class='se-tab-count'>$count</span></a></li>";
    }
    echo '</ul>';
    echo '<div class="se-actions-right">';
    echo '<button type="submit" id="replaceContent" class="button button-primary">Replace Content</button>';
    echo '<button type="submit" id="addByOrderId" class="button button-primary">+ Order ID</button>';
    echo '<button type="submit" id="openModalBtn" class="button button-primary">+ New Email</button>';
    echo '<form method="GET" class="se-flex se-gap-2"><input type="hidden" name="page" value="scheduled-emails" /><input type="text" name="s" value="' . esc_attr($search_query) . '" placeholder="Search emails..." style="width:200px;" /><button type="submit" class="button">Search</button></form>';
    echo '</div>';
    echo '</div>';

    echo "<table class='wp-list-table widefat fixed striped se-table'><thead><tr><th>ID</th><th>Email</th><th style='width:35%;'>Subject / Content</th><th>Scheduled For</th><th>Order</th><th>Product</th><th>Variation</th><th>Status</th><th>Action</th></tr></thead><tbody>";
    if ($emails) {
        foreach ($emails as $email) {
            $escaped_content = esc_attr(htmlspecialchars($email->content, ENT_QUOTES, 'UTF-8'));
            $subject_display = esc_html($email->subject);
            if (strpos($email->subject, 'Reminder - Venue Details of ') === 0) {
                $subject_display = '<span class="se-text-primary se-font-medium">' . $subject_display . '</span>';
            }
            // Truncate content for display
            $content_preview = mb_strlen($email->content) > 80 ? mb_substr($email->content, 0, 80) . '...' : $email->content;
            $content_preview = esc_html($content_preview);

            // Status badge
            $status_badge = '';
            if ($email->sent_time === null) {
                $status_badge = '<span class="se-badge se-badge-pending">Scheduled</span>';
            } elseif ($email->sent_time === 'Deleted' || $email->sent_time === 'Bin') {
                $status_badge = '<span class="se-badge se-badge-deleted">' . esc_html($email->sent_time) . '</span>';
            } else {
                $sent_timestamp = strtotime($email->sent_time);
                $is_today = date('Y-m-d', $sent_timestamp) === current_time('Y-m-d');
                $format = $is_today ? 'g:i A' : 'M j, g:i A';
                $sent_time_formatted = date($format, $sent_timestamp);
                $status_badge = '<span class="se-badge se-badge-sent">Sent ' . $sent_time_formatted . '</span>';
            }

            echo "<tr>
                <td class='se-text-muted'>{$email->id}</td>
                <td class='editable' data-name='mailto' data-id='{$email->id}'>{$email->mailto}</td>
                <td class='editable' data-subject='{$email->subject}' data-content='{$escaped_content}' data-name='edit_content' data-id='{$email->id}'><strong>{$subject_display}</strong><br><span class='se-text-muted se-text-sm'>{$content_preview}</span></td>
                <td class='editable' data-name='scheduled_time' data-id='{$email->id}'>{$email->scheduled_time}</td>
                <td><a href='/wp-admin/post.php?post={$email->order_id}&action=edit' class='se-text-primary'>{$email->order_id}</a></td>
                <td class='se-text-muted'>{$email->product_id}</td>
                <td class='se-text-muted'>{$email->variation_id}</td>
                <td>{$status_badge}</td>
                <td><a href='" . esc_url(add_query_arg('delete_email', $email->id)) . "' class='button button-small' onclick='return confirm(\"Move this to Deleted?\")'>Delete</a></td>
            </tr>";
        }
    } else {
        echo "<tr><td colspan='9' class='not-found'>No scheduled emails found.</td></tr>";
    }
    echo "</tbody></table>";

    if ($pagination_links) {
        echo "<div class='custom-pagination'>";
        echo "<div class='pagination-info'>Showing page {$current_page} of " . ceil($total_items / $per_page) . " ({$total_items} total items)</div>";
        echo "<div class='pagination-controls'>{$pagination_links}</div>";
        echo "</div>";
    }
    echo "</div>";
}

add_action('wp_ajax_replace_email_content', 'handle_replace_email_content');
function handle_replace_email_content()
{
    // Accept nonce in 'security' param as sent from JS
    check_ajax_referer('update_content_nonce', 'security');
    global $wpdb;
    $table_name = $wpdb->prefix . 'scheduled_emails';
    $data = json_decode(stripslashes($_POST['info']), true);
    if (!$data) wp_send_json_error(['message' => 'Invalid JSON data.']);
    $new_content = wp_kses_post($data['content_replace']);
    $old_content = wp_kses_post($data['content_find']);
    $query_value = sanitize_text_field($data['query']);
    $updated_rows = $wpdb->query($wpdb->prepare("UPDATE $table_name SET content = REPLACE(content, %s, %s) WHERE order_id = %s OR product_id = %s OR variation_id = %s", $old_content, $new_content, $query_value, $query_value, $query_value));
    if ($updated_rows !== false) wp_send_json_success(['message' => 'Content replaced successfully', 'updated_rows' => $updated_rows]);
    else wp_send_json_error(['message' => 'Failed to replace content.']);
}

add_action('wp_ajax_add_email_by_order_id', 'add_email_by_order_id');
function add_email_by_order_id()
{
    // Accept nonce in 'security' param as sent from JS
    check_ajax_referer('update_content_nonce', 'security');
    schedule_events_on_purchase(absint($_POST['order_id']), true);
}

add_action('wp_ajax_add_email_content', 'handle_add_email_content');
function handle_add_email_content()
{
    // Accept nonce in 'security' param as sent from JS
    check_ajax_referer('update_content_nonce', 'security');
    global $wpdb;
    $table_name = $wpdb->prefix . 'scheduled_emails';
    $data = json_decode(stripslashes($_POST['info']), true);
    if (!$data) wp_send_json_error(['message' => 'Invalid JSON data.']);

    $scheduled_time = DateTime::createFromFormat('Y-m-d\TH:i', sanitize_text_field($data['sent_time']));
    if (!$scheduled_time) wp_send_json_error(['message' => 'Invalid date format for sent_time.']);

    $email_array = array_map('trim', explode(',', sanitize_text_field($data['email'])));
    $inserted_count = 0;
    foreach ($email_array as $email) {
        if (!is_email($email)) continue;
        $insert_data = [
            'mailto' => $email,
            'subject' => sanitize_text_field($data['subject']),
            'content' => wp_kses_post($data['content']),
            'order_id' => absint($data['order_id']),
            'product_id' => absint($data['product_id']),
            'variation_id' => absint($data['variation_id']),
            'scheduled_time' => $scheduled_time->format('Y-m-d H:i:s')
        ];
        if ($wpdb->insert($table_name, $insert_data)) $inserted_count++;
    }
    if ($inserted_count > 0) wp_send_json_success(['message' => "$inserted_count email(s) added successfully."]);
    else wp_send_json_error(['message' => 'No valid emails were added.']);
}

add_action('wp_ajax_update_content', 'handle_update_email_content');
function handle_update_email_content()
{
    // Accept nonce in 'security' param as sent from JS
    check_ajax_referer('update_content_nonce', 'security');
    global $wpdb;
    $table_name = $wpdb->prefix . 'scheduled_emails';
    $id = absint($_POST['id']);
    $name_info = sanitize_text_field($_POST['name_info']);
    $content = stripslashes($_POST['content']);
    $subject_info = isset($_POST['subject_info']) ? stripslashes($_POST['subject_info']) : '';
    if (!$id) wp_send_json_error(['message' => 'Invalid ID.']);

    $update_data = [];
    $format = [];
    if ($name_info === 'scheduled_time') {
        $date = DateTime::createFromFormat('Y-m-d\TH:i', sanitize_text_field($content));
        if ($date) {
            $update_data = ['scheduled_time' => $date->format('Y-m-d H:i:s'), 'sent_time' => null];
            $format = ['%s', '%s'];
        } else {
			wp_send_json_error(['message' => 'Invalid date format.']);
		}
    } elseif ($name_info === 'edit_content') {
        $update_data = ['content' => $content, 'subject' => sanitize_text_field($subject_info)];
        $format = ['%s', '%s'];
    } elseif ($name_info === 'mailto') {
        if (is_email($content)) {
            $update_data = ['mailto' => sanitize_email($content)];
            $format = ['%s'];
        } else wp_send_json_error(['message' => 'Invalid email address.']);
    }

    if (!empty($update_data) && $wpdb->update($table_name, $update_data, ['id' => $id], $format, ['%d']) !== false) {
        if ($name_info === 'edit_content') {
            $content_preview = sanitize_text_field($subject_info) . '<br>' . wp_kses_post($content);
            wp_send_json_success(['message' => 'Update successful.', 'content_preview' => $content_preview]);
        } else {
            wp_send_json_success(['message' => 'Update successful.']);
        }
    }
    wp_send_json_error(['message' => 'Update failed or no changes made.']);
}

// --- Display Scheduled Emails on Thank You Page ---
function get_scheduled_emails_for_customer($customer_email)
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'scheduled_emails';
    $emails = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name WHERE mailto = %s ORDER BY scheduled_time DESC", $customer_email));
    return $emails;
}

// add_action('woocommerce_thankyou', 'display_scheduled_emails', 10, 1);
function display_scheduled_emails($order_id)
{
    $order = wc_get_order($order_id);
    if (!$order) return;

    $customer_email = $order->get_billing_email();
    $emails = get_scheduled_emails_for_customer($customer_email);

    if (empty($emails)) return;

    echo '<div class="scheduled-emails-section">';
    echo '<h2>Your Scheduled Emails</h2>';
    echo '<p>Here is a list of emails scheduled for you based on your recent purchase:</p>';
    echo '<table class="scheduled-emails-table">';
    echo '<thead><tr><th>Subject</th><th>Scheduled Send Date</th><th>Status</th></tr></thead>';
    echo '<tbody>';

    foreach ($emails as $email) {
        $status = $email->sent_time ? 'Sent' : 'Scheduled';
        $scheduled_date = date('F j, Y \a\t g:i A', strtotime($email->scheduled_time));
        echo '<tr>';
        echo '<td>' . esc_html($email->subject) . '</td>';
        echo '<td>' . esc_html($scheduled_date) . '</td>';
        echo '<td>' . esc_html($status) . '</td>';
        echo '</tr>';
    }

    echo '</tbody></table>';
    echo '</div>';
}
