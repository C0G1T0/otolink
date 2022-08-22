<?php

/*
  Plugin Name: Otolink
  Description: Add internal links to your post
  Version 1.0
  Author: William
  Author URI: https://github.com/C0G1T0
*/

if( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class OtolinkPlugin {
  function __construct() {
    add_action('admin_menu', array($this, 'ourMenu'));
    add_action('admin_init', array($this, 'ourSettings'));
    if (get_option('plugin_otolink_keywords')) add_filter( 'wp_insert_post_data', array($this, 'onSaveLogic'), 10, 4);

  }

  function ourSettings() {
    add_settings_section('minimum-links-section', null, null, 'otolink-options');
    register_setting('minLinksFields', 'minimumLinks');
    add_settings_field('links-number', 'Minimum number of internal link', array($this, 'minLinksFieldHTML'), 'otolink-options', 'minimum-links-section');
  }

  function minLinksFieldHTML() { ?>
    <input type="number" name="minimumLinks" value="<?php echo esc_attr(get_option('minimumLinks', '1')) ?>">
    <p class="description">By default only one internal link will be added to the content.</p>
  <?php }

  function onSaveLogic($data, $postarr, $unsanitized_postarr, $update) {

      //======================================
      //Check how many link in page
      //Get minimum link needed
      //As long as link in page < links needed
      //Get keywords and links
      //Loop on keyword
      //Check if keyword present, (not in title, take care of uppercase?Regex)
      //Replace only the first occurence and insert link
      
      $numberOfInternalLinks = substr_count($data['post_content'], $_SERVER['SERVER_NAME']);     
      $minimumNumberOfLink =  get_option('minimumLinks');
      
      while($numberOfInternalLinks < $minimumNumberOfLink) {
        $keywordsList = get_option('plugin_otolink_keywords');      
        foreach($keywordsList as $key => $keyword) {
          $myKeyword = $keyword['keyword'];
          $myLink = '<a href="https://' . $keyword['link'] .'">'.$myKeyword.'</a>';

          $keywordExist = strpos(($data['post_content']), $myKeyword.' ');
          if($keywordExist !== false) {
            $newstring = substr_replace($data['post_content'], $myLink, $keywordExist, strlen($myKeyword));
            $data['post_content'] = $newstring;
            $numberOfInternalLinks ++;
            break;
          }
        }
      }
      
    return $data;
  }

  function ourMenu() {
    $mainPageHook = add_menu_page('Otolink', 'Otolink', 'manage_options', 'otolink', array($this, 'otolinkPage'), 'dashicons-admin-links', 100);
    add_submenu_page('otolink', 'Otolink Keywords List', 'Keywords List', 'manage_options', 'otolink', array($this, 'otolinkPage'));
    $addKeywordPageHook = add_submenu_page('otolink', 'Otolink Add Keyword', 'Add New Keyword', 'manage_options', 'otolink-add-keyword', array($this, 'addKeywordPage'));
    add_submenu_page('otolink', 'Otolink Options', 'Options', 'manage_options', 'otolink-options', array($this, 'optionsSubPage'));
    add_action("load-{$mainPageHook}", array($this, 'mainPageAssets'));
    add_action("load-{$addKeywordPageHook}", array($this, 'mainPageAssets'));
  }

  function mainPageAssets() {
    wp_enqueue_style('otolinkAdminCss', plugin_dir_url(__FILE__) . 'styles.css');
  }

  function addKeywordForm() {
    if (wp_verify_nonce($_POST['ourNonce'], 'saveKeyword') AND current_user_can('manage_options')) {
      
      $keywordAndLink['keyword'] = sanitize_text_field($_POST['plugin_keyword_otolink']);
      $keywordAndLink['link'] = sanitize_text_field($_POST['plugin_link_otolink']);
      $recap_keywords = get_option('plugin_otolink_keywords');
      
      if($recap_keywords > 0) {
        $recap_keywords[] = $keywordAndLink;
        update_option('plugin_otolink_keywords', $recap_keywords);      
      } else {
        $recap_keywords = [];
        $recap_keywords[] = $keywordAndLink;
        update_option('plugin_otolink_keywords', $recap_keywords); 
      }?>

      <div class="updated">
        <p>Your new keyword was saved.</p>
      </div>

    <?php } else { ?>

      <div class="error">
        <p>Sorry, you do not have permission to perform that action.</p>
      </div>

    <?php } 
  }

  function deleteKeywordForm($dataId) {
    if (wp_verify_nonce($_POST['ourNonce'], 'deleteKeyword') AND current_user_can('manage_options')) {
      $keywordsList = get_option('plugin_otolink_keywords');
      unset($keywordsList[$dataId]);  
      update_option('plugin_otolink_keywords', $keywordsList);
      ?>
      <div class="updated">
        <p>Your keyword was delete.</p>
      </div>
    <?php } else { ?>
      <div class="error">
        <p>Sorry, you do not have permission to perform that action.</p>
      </div>
    <?php } 
  }

  function otolinkPage() { 
    $keywordsList = get_option('plugin_otolink_keywords');
    ?>
    <div class="wrap">
      <h1>Keywords List</h1>
      <?php 
        if ($_POST['justsubmitted'] == "true") {
          $this->deleteKeywordForm($_POST['plugin_keyword_dataid']);
          $keywordsList = get_option('plugin_otolink_keywords');
        };
        wp_nonce_field('deleteKeyword', 'ourNonce');
        
        // Empty array is false in php
        if(($keywordsList > 0) AND ($keywordsList == true)) {
          
          foreach($keywordsList as $key => $keyword) {
            ?>
             <form method="POST" id="<?php echo "form".$key ?>">
             <input type="hidden" name="justsubmitted" value="true">
              <?php wp_nonce_field('deleteKeyword', 'ourNonce') ?>
              <div class="autolink-box" data-id="<?php echo $key ?>">
                <p class="autolink-box__keyword"><?php echo $keyword['keyword']; ?></p>
                <div class="autolink-box__link"><a href="<?php echo $keyword['link']; ?>"><?php echo $keyword['link']; ?></a></div>
                <input type="hidden" name="plugin_keyword_dataid" id="plugin_keyword_dataid" value="<?php echo $key ?>"></input>
                <input type="submit" name="submit" id="<?php echo $key ?>" class="button button-primary" value="Delete"> 
              </div>
              </form>
            <?php
          };
        } else {
          ?>
          <div class="">
            <p>Go to "Add new keywords" to start adding link(s) in your post/page</p>
          </div>
    <?php
        }
        ?>  
    </div>
  <?php }

  function addKeywordPage() { ?>
    <div class="wrap">
      <h1>Add a new keyword</h1>
      <?php if ($_POST['justsubmitted'] == "true") $this->addKeywordForm() ?>
      <form method="POST">
        <input type="hidden" name="justsubmitted" value="true">
        <?php wp_nonce_field('saveKeyword', 'ourNonce') ?>
        <div class="new-keyword-box">
          <div class="new-keyword-box__input">
            <label for="plugin_keyword_otolink"><p>Enter a <strong>keyword</strong>.</p></label>
            <input type="text" name="plugin_keyword_otolink" id="plugin_keyword_otolink" placeholder="Target keyword"></input>
          </div>
          <div class="new-keyword-box__input">
            <label for="plugin_link_otolink"><p>Enter a <strong>URL</strong>.</p></label>
            <input type="text" name="plugin_link_otolink" id="plugin_link_otolink" placeholder="https://my-optimised-website.com"></input>
          </div>
        </div>
        <input type="submit" name="submit" id="submit" class="button button-primary" value="Save Keyword"> 
      </form>
    </div>
  <?php }

  function optionsSubPage() { ?>
    <div class="wrap">
      <h1>Otolink Options</h1>
      <form action="options.php" method="POST">
        <?php
          settings_errors();
          settings_fields('minLinksFields');
          do_settings_sections('otolink-options');
          submit_button();
        ?>
      </form>
    </div>
  <?php }

}

$otolinkPlugin = new OtolinkPlugin();