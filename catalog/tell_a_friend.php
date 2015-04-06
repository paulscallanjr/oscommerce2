<?php
/*
  $Id$

  osCommerce, Open Source E-Commerce Solutions
  http://www.oscommerce.com

  Copyright (c) 2015 osCommerce

  Released under the GNU General Public License
*/

  use OSC\OM\HTML;

  require('includes/application_top.php');

  if (!isset($_SESSION['customer_id']) && (ALLOW_GUEST_TO_TELL_A_FRIEND == 'false')) {
    $_SESSION['navigation']->set_snapshot();
    tep_redirect(tep_href_link('login.php', '', 'SSL'));
  }

  $valid_product = false;
  if (isset($_GET['products_id'])) {
    $product_info = $OSCOM_Db->prepare('select pd.products_name from products p, products_description pd where p.products_status = 1 and p.products_id = :products_id and p.products_id = pd.products_id and pd.language_id = :languages_id');
    $product_info->bindInt(':products_id', $_GET['products_id']);
    $product_info->bindInt(':languages_id', $_SESSION['languages_id']);
    $product_info->execute();

    if ( $product_info->rowCount() > 0 ) {
      $valid_product = true;
    }
  }

  if ($valid_product == false) {
    tep_redirect(tep_href_link('product_info.php', 'products_id=' . (int)$_GET['products_id']));
  }

  require(DIR_WS_LANGUAGES . $_SESSION['language'] . '/tell_a_friend.php');

  if (isset($_GET['action']) && ($_GET['action'] == 'process') && isset($_POST['formid']) && ($_POST['formid'] == $_SESSION['sessiontoken'])) {
    $error = false;

    $to_email_address = HTML::sanitize($_POST['to_email_address']);
    $to_name = HTML::sanitize($_POST['to_name']);
    $from_email_address = HTML::sanitize($_POST['from_email_address']);
    $from_name = HTML::sanitize($_POST['from_name']);
    $message = HTML::sanitize($_POST['message']);

    if (empty($from_name)) {
      $error = true;

      $messageStack->add('friend', ERROR_FROM_NAME);
    }

    if (!tep_validate_email($from_email_address)) {
      $error = true;

      $messageStack->add('friend', ERROR_FROM_ADDRESS);
    }

    if (empty($to_name)) {
      $error = true;

      $messageStack->add('friend', ERROR_TO_NAME);
    }

    if (!tep_validate_email($to_email_address)) {
      $error = true;

      $messageStack->add('friend', ERROR_TO_ADDRESS);
    }

    $actionRecorder = new actionRecorder('ar_tell_a_friend', (isset($_SESSION['customer_id']) ? $_SESSION['customer_id'] : null), $from_name);
    if (!$actionRecorder->canPerform()) {
      $error = true;

      $actionRecorder->record(false);

      $messageStack->add('friend', sprintf(ERROR_ACTION_RECORDER, (defined('MODULE_ACTION_RECORDER_TELL_A_FRIEND_EMAIL_MINUTES') ? (int)MODULE_ACTION_RECORDER_TELL_A_FRIEND_EMAIL_MINUTES : 15)));
    }

    if ($error == false) {
      $email_subject = sprintf(TEXT_EMAIL_SUBJECT, $from_name, STORE_NAME);
      $email_body = sprintf(TEXT_EMAIL_INTRO, $to_name, $from_name, $product_info->value('products_name'), STORE_NAME) . "\n\n";

      if (tep_not_null($message)) {
        $email_body .= $message . "\n\n";
      }

      $email_body .= sprintf(TEXT_EMAIL_LINK, tep_href_link('product_info.php', 'products_id=' . (int)$_GET['products_id'], 'NONSSL', false)) . "\n\n" .
                     sprintf(TEXT_EMAIL_SIGNATURE, STORE_NAME . "\n" . HTTP_SERVER . DIR_WS_CATALOG . "\n");

      tep_mail($to_name, $to_email_address, $email_subject, $email_body, $from_name, $from_email_address);

      $actionRecorder->record();

      $messageStack->add_session('header', sprintf(TEXT_EMAIL_SUCCESSFUL_SENT, $product_info->value('products_name'), tep_output_string_protected($to_name)), 'success');

      tep_redirect(tep_href_link('product_info.php', 'products_id=' . (int)$_GET['products_id']));
    }
  }

  $breadcrumb->add(NAVBAR_TITLE, tep_href_link('tell_a_friend.php', 'products_id=' . (int)$_GET['products_id']));

  require('includes/template_top.php');
?>

<div class="page-header">
  <h1><?php echo sprintf(HEADING_TITLE, $product_info->value('products_name')); ?></h1>
</div>

<?php
  if ($messageStack->size('friend') > 0) {
    echo $messageStack->output('friend');
  }
?>

<?php echo tep_draw_form('email_friend', tep_href_link('tell_a_friend.php', 'action=process&products_id=' . (int)$_GET['products_id'], $request_type), 'post', 'class="form-horizontal" role="form"', true); ?>

<div class="contentContainer">

  <p class="inputRequirement text-right"><?php echo FORM_REQUIRED_INFORMATION; ?></p>

  <div class="page-header">
    <h4><?php echo FORM_TITLE_CUSTOMER_DETAILS; ?></h4>
  </div>

  <div class="contentText">
    <div class="form-group has-feedback">
      <label for="inputFromName" class="control-label col-sm-3"><?php echo FORM_FIELD_CUSTOMER_NAME; ?></label>
      <div class="col-sm-9">
        <?php
        echo tep_draw_input_field('from_name', NULL, 'required aria-required="true" id="inputFromName" placeholder="' . ENTRY_FORM_FIELD_CUSTOMER_NAME_TEXT . '"');
        echo FORM_REQUIRED_INPUT;
        ?>
      </div>
    </div>
    <div class="form-group has-feedback">
      <label for="inputFromEmail" class="control-label col-sm-3"><?php echo FORM_FIELD_CUSTOMER_EMAIL; ?></label>
      <div class="col-sm-9">
        <?php
        echo tep_draw_input_field('from_email_address', NULL, 'required aria-required="true" id="inputFromEmail" placeholder="' . ENTRY_FORM_FIELD_CUSTOMER_EMAIL_TEXT . '"', 'email');
        echo FORM_REQUIRED_INPUT;
        ?>
      </div>
    </div>
  </div>

  <div class="page-header">
    <h4><?php echo FORM_TITLE_FRIEND_DETAILS; ?></h4>
  </div>

  <div class="contentText">
    <div class="form-group has-feedback">
      <label for="inputToName" class="control-label col-sm-3"><?php echo FORM_FIELD_FRIEND_NAME; ?></label>
      <div class="col-sm-9">
        <?php
        echo tep_draw_input_field('to_name', NULL, 'required aria-required="true" id="inputToName" placeholder="' . ENTRY_FORM_FIELD_FRIEND_NAME_TEXT . '"');
        echo FORM_REQUIRED_INPUT;
        ?>
      </div>
    </div>
    <div class="form-group has-feedback">
      <label for="inputToEmail" class="control-label col-sm-3"><?php echo FORM_FIELD_FRIEND_EMAIL; ?></label>
      <div class="col-sm-9">
        <?php
        echo tep_draw_input_field('to_email_address', NULL, 'required aria-required="true" id="inputToEmail" placeholder="' . ENTRY_FORM_FIELD_FRIEND_EMAIL_TEXT . '"', 'email');
        echo FORM_REQUIRED_INPUT;
        ?>
      </div>
    </div>
  </div>

  <hr>

  <div class="contentText">
    <div class="form-group has-feedback">
      <label for="inputMessage" class="control-label col-sm-3"><?php echo FORM_TITLE_FRIEND_MESSAGE; ?></label>
      <div class="col-sm-9">
        <?php
        echo tep_draw_textarea_field('message', 'soft', 40, 8, NULL, 'required aria-required="true" id="inputMessage" placeholder="' . ENTRY_FORM_TITLE_FRIEND_MESSAGE_TEXT . '"');
        echo FORM_REQUIRED_INPUT;
        ?>
      </div>
    </div>
  </div>

  <div class="clearfix"></div>

  <div class="row">
    <div class="col-sm-6 text-right pull-right"><?php echo tep_draw_button(IMAGE_BUTTON_CONTINUE, 'glyphicon glyphicon-chevron-right', null, 'primary', null, 'btn-success'); ?></div>
    <div class="col-sm-6"><?php echo tep_draw_button(IMAGE_BUTTON_BACK, 'glyphicon glyphicon-chevron-left', tep_href_link('product_info.php', 'products_id=' . (int)$_GET['products_id'])); ?></div>
  </div>
</div>

</form>

<?php
  require('includes/template_bottom.php');
  require('includes/application_bottom.php');
?>
