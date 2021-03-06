<?php
/* For licensing terms, see /license.txt */
/**
 * Header of each frame of the Chat tool
 *
 * @author Olivier Brouckaert
 * Modified by Denes Nagy
 * @package chamilo.chat
 */
/**
 * Code
 */
if (!defined('FRAME')) {
	exit();
}

$bodyXtra = ' class="course_chat" dir="'.api_get_text_direction().'" ';

if (FRAME == 'hidden') {
	$bodyXtra .= 'onload="javascript: updateChat(); updateConnected(); setTimeout(\'submitHiddenForm();\', 5000);"';
    // Change timeout to change refresh time of the chat window
} elseif (FRAME == 'message') {
	$bodyXtra .= 'onload="javascript: eventMessage();"';
}

$mycourseid=api_get_cidreq();
if (empty($mycourseid)) {
	// If it is not set $mycourse id we reload the chat_message window in order to hide the textarea to submit a message.
	echo '<script type="text/javascript" language="javascript">';
	echo "parent.chat_message.location.href='chat_whoisonline.php?".api_get_cidreq()."';";
	echo '</script>';
}

/*
 * Choose CSS style (platform's, user's, or course's)
 */
$my_style = api_get_visual_theme();
?>
<!DOCTYPE html>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo api_get_system_encoding(); ?>">
<title>Chat</title>
<link rel="stylesheet" type="text/css" href="<?php echo api_get_path(WEB_CSS_PATH).$my_style; ?>/default.css">
<script type="text/javascript" language="javascript">
function updateChat() {
	if ('<?php echo $chat_size_old; ?>' != '<?php echo $chat_size_new; ?>')
	{
		parent.chat_chat.location.href='chat_chat.php?size=<?php echo $chat_size_new.'&'.api_get_cidreq(); ?>#bottom';
	}
}

function updateConnected() {
	if ('<?php echo $connected_old; ?>' != '<?php echo $connected_new; ?>')
	{
		parent.chat_whoisonline.location.href='chat_whoisonline.php?size=<?php echo $connected_new; ?>';
	}
}

function submitHiddenForm() {
	document.formHidden.submit();
}

function eventMessage() {
	<?php if($chat_size): ?>
	parent.chat_hidden.document.formHidden.chat_size_old.value='<?php echo $chat_size; ?>';
	parent.chat_chat.location.href='chat_chat.php?size=<?php echo $chat_size.'&'.api_get_cidreq(); ?>#bottom';
	<?php endif; ?>
	document.formMessage.message.focus();
}

function send_message(evenement) {
    for (prop in evenement) {
    	if(prop == 'which') touche = evenement.which; else touche = evenement.keyCode;
    }

    if (touche == 13) {
    	document.formMessage.submit();
    }
}
</script>
</head>
<body <?php echo $bodyXtra; ?> >
