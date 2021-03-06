<?php
/* For licensing terms, see /license.txt */

/**
 *  @package chamilo.admin
 */

// Language files that need to be included.
$language_file = array('admin');
$cidReset = true;

require_once '../inc/global.inc.php';

$this_section = SECTION_PLATFORM_ADMIN;

api_protect_admin_script();

if (api_get_setting('allow_skills_tool') != 'true') {
    api_not_allowed();
}

//Adds the JS needed to use the jqgrid
$htmlHeadXtra[] = api_get_js('d3/d3.v2.min.js');
$htmlHeadXtra[] = api_get_js('d3/colorbrewer.js');
$htmlHeadXtra[] = api_get_js('d3/jquery.xcolor.js');

$tpl = $app['template'];

$load_user = 0;
if (isset($_GET['load_user'])) {
    $load_user = 1;
}

$skill_condition = '';
if (isset($_GET['skill_id'])) {
    $skill_condition = '&skill_id='.intval($_GET['skill_id']);
    $tpl->assign('skill_id_to_load', $_GET['skill_id']);
}

$url = api_get_path(WEB_AJAX_PATH)."skill.ajax.php?a=get_skills_tree_json&load_user=$load_user";
$tpl->assign('wheel_url', $url);


$url  = api_get_path(WEB_AJAX_PATH).'skill.ajax.php?1=1';
$tpl->assign('url', $url);

$content = $tpl->fetch('default/skill/skill_wheel.tpl');
$tpl->assign('content', $content);
$tpl->display_no_layout_template();
