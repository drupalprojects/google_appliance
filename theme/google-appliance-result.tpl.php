<?php
// $Id$
/**
 * @file 
 *    default theme implementation for displaying a single search result.
 *
 * This template renders a single search result and is collected into
 * google-applinace-results.tpl.php. This and the parent template are
 * dependent on one another sharing the markup for results listings.
 *
 * @see template_preprocess_google_appliance_result()
 * @see google-appliance-results.tpl.php
 */
//dsm($variables);
?>
<li class="search-result <?php print $classes; ?>" id="result-<?php print $result_idx; ?>"<?php print $attributes; ?>>
  
  <?php print render($title_prefix); ?>
  <h3 class="title"<?php print $title_attributes; ?>>
    <a href="<?php print $abs_url; ?>"><?php print $title; ?></a>
  </h3>
  <?php print render($title_suffix); ?>
  
  <div class="search-snippet-info google-appliance-snippet-info">
    <?php if ($snippet) : ?>
      <p class="search-snippet google-appliance-snippet">
        <?php print $snippet; ?>
      </p>
    <?php endif; ?>
    <p class="search-info google-appliance-info">
      <?php print $short_url; ?>
    </p>
  </div>
</li>
