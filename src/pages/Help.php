<?php if (!defined('ABSPATH'))
  exit; ?>
<?php use Logto\WpPlugin\Settings\SettingsSection; ?>
<div class="wrap" style="max-width: 800px;">
  <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
  <p>
    <?php echo wp_kses(_x('Thank you for choosing Logto! By integrating Logto into your WordPress site, you are not only enhancing the security and user experience of your site, but also enabling a unified login experience across all your applications.', 'help', 'logto'), SettingsSection::allowedTextHtml); ?>
  </p>
  <p>
    <?php echo wp_kses(_x('If you are new to Logto, we recommend checking out our <a href="https://docs.logto.io/" target="_blank" rel="noopener">introduction page</a> to have a quick overview of Logto concepts and features.', 'help', 'logto'), SettingsSection::allowedTextHtml); ?>
  </p>
  <p>
    <b>
      <?php echo wp_kses(_x('If you are looking for a step-by-step guide to set up Logto, please refer to our <a href="https://docs.logto.io/quick-starts/wordpress-plugin" target="_blank" rel="noopener">WordPress plugin quick start guide</a>.', 'help', 'logto'), SettingsSection::allowedTextHtml); ?>
    </b>
  </p>
  <p>
    <?php echo wp_kses(_x('If you have any questions or feedback, please feel free to:', 'help', 'logto'), SettingsSection::allowedTextHtml); ?>
  </p>
  <ul style="list-style: disc; padding-left: 20px;">
    <li>
      <?php echo wp_kses(_x('<a href="https://discord.com/invite/UEPaF3j5e6" target="_blank" rel="noopener">Join our Discord server</a> to get help from the community.', 'help', 'logto'), SettingsSection::allowedTextHtml); ?>
    </li>
    <li>
      <?php echo wp_kses(_x('<a href="https://github.com/logto-io/wordpress/issues" target="_blank" rel="noopener">Open an issue on GitHub</a> to report bugs or request features.', 'help', 'logto'), SettingsSection::allowedTextHtml); ?>
    </li>
    <li>
      <?php echo wp_kses(_x('<a href="https://logto.io/" target="_blank" rel="noopener">Subscribe to a paid plan</a> to get access to official support and more features.', 'help', 'logto'), SettingsSection::allowedTextHtml); ?>
    </li>
  </ul>
  <p>
    <?php echo wp_kses(_x('Hope you enjoy using Logto!', 'help', 'logto'), SettingsSection::allowedTextHtml); ?>
  </p>
</div>
