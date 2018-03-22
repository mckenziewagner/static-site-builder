<?php
/**
 * Plugin Name:     Static Site Builder
 * Plugin URI:      PLUGIN SITE HERE
 * Description:     Build your site from your wordpress dashboard. Currently only supports netlifys build hook but more to come!
 * Author:          waffles
 * Author URI:      YOUR SITE HERE
 * Text Domain:     static-site-builder
 * Domain Path:     /languages
 * Version:         0.1.0
 *
 * @package         Static_Site_Builder
 */

function options_page_html() {
  // check user capabilities
  if (!current_user_can('manage_options')) {
    return;
  }
  if (get_option('builder_status') === false) {
    add_option('builder_status', 'none');
  }
?>
  <div class="wrap">
    <h1><?= esc_html(get_admin_page_title()); ?></h1>
    <form id="" action="<?php echo esc_url( admin_url('admin-post.php') ); ?>" method="post">
<?php
  // output security fields for the registered setting "wporg_options"
  settings_fields('netlify_build_group');
  // output setting sections and their fields
  // (sections are registered for "wporg", each field is registered to a specific section)
  do_settings_sections('netlify_build_id');
?>
      <?php if( isset($_GET['status']) && $_GET['status'] === 'success' ) : ?>
        <div id="message" class="updated notice notice-success"><p>Success: Your netlify site is building.</p></div>
      <?php elseif( isset($_GET['status']) && $_GET['status'] === 'error' ) : ?>
        <div id="message" class="updated notice error"><p>Error: Check your hook id...</p></div>
      <?php endif; ?>
      <label for="netlify-id">Netlify Hook ID (Keep this a secret)</label><br>
      <input id="netlify-id" name="netlify-id" style="width: 100%; max-width: 500px;" type="text" value="<?php echo esc_attr( get_option('netlify_build_id') ); ?>">
      <input type="hidden" name="action" value="netlify_build">
      <br>
      <?php submit_button('Build Site') ?>
    </form>
  </div>
<?php
}

function curl($url) {
  $ch = curl_init();

  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_POSTFIELDS, "{}");
  curl_setopt($ch, CURLOPT_POST, 1);

  $data = curl_exec($ch);
  if ($data === false) {
    printf("cUrl error (#%d): %s<br>\n", curl_errno($ch), htmlspecialchars(curl_error($ch)));
  }
  $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);

  return $code;
}

function post_to_build_hook() {
  $siteUrl = get_site_url() . '/wp-admin/admin.php?page=static-builder';
  $result = curl('https://api.netlify.com/build_hooks/' . $_POST['netlify-id']);

  update_option('netlify_build_id', $_POST['netlify-id']);

  if ($result === 200) {
    wp_redirect($siteUrl . '&status=success');
    exit;
  } else {
    wp_redirect($siteUrl . '&status=error');
    exit;
  }
}

add_action('admin_post_netlify_build', 'post_to_build_hook');

add_action('admin_menu', function() {
  add_menu_page(
    'Netlify Builder Settings',
    'Netlify Builder',
    'manage_options',
    'static-builder',
    'options_page_html',
    'dashicons-admin-tools',
    200
  );
  add_submenu_page(
    'admin.php?page=static-builder',
    'Netlify Builder Settings',
    'manage_options',
    'static-builder-settings',
    'options_page_html'
  );
});

// netlify-builder
add_action( 'admin_init', function() {
  $args = array(
    'type' => 'string',
    'default' => NULL,
  );
  register_setting( 'netlify_build_group', 'netlify_build_id', $args );
});
