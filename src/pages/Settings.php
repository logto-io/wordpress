<?php use Logto\WpPlugin\LogtoConstants; ?>
<div class="wrap">
  <h1><?= esc_html(get_admin_page_title()); ?></h1>
  <form method="post" action="options.php" id="form-<?= LogtoConstants::OPTION_KEY ?>">
    <?php
    // Output nonce, action, and setting fields
    settings_fields(LogtoConstants::OPTION_GROUP);
    do_settings_sections(LogtoConstants::MENU_SLUG);
    submit_button();
    ?>
  </form>
</div>
