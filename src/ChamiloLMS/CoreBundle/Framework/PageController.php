<?php
/* For licensing terms, see /license.txt */

namespace ChamiloLMS\CoreBundle\Framework;

use ChamiloLMS\CoreBundle\Framework\Application;
use Pagerfanta\Adapter\FixedAdapter;
use Pagerfanta\Pagerfanta;
use Pagerfanta\View\TwitterBootstrapView;
use SystemAnnouncementManager;
use UserManager;
use CourseManager;
use ChamiloSession as Session;

/**
 * Class PageController
 * Controller for pages presentation in general
 * @package chamilo.page.controller
 * @author Julio Montoya <gugli100@gmail.com>
 *
 * @todo move functions in the Template class, remove this class.
 */
class PageController
{
    public $maxPerPage = 5;

    /**
     * Returns an HTML block with the user picture (as a link in a <div>)
     * @param int User ID (if not provided, will use the user ID from session)
     * @return string HTML div with a link to the user's profile
     * @uses UserManager::get_user_pictur_path_by_id() to get the image path
     * @uses UserManager::get_picture_user() to get the details of the image in a specific format
     * @uses PageController::show_right_block() to include the image in a larger user block
     * @assert (-1) === false
     */
    public function setUserImageBlock($user_id = null)
    {
        if (empty($user_id)) {
            $user_id = api_get_user_id();
        }

        //Always show the user image
        $img_array = UserManager::get_user_picture_path_by_id($user_id, 'web', true, true);
        $no_image  = false;
        if ($img_array['file'] == 'unknown.jpg') {
            $no_image = true;
        }
        $img_array       = UserManager::get_picture_user($user_id, $img_array['file'], 100, USER_IMAGE_SIZE_ORIGINAL);
        $profile_content = null;
        if (api_get_setting('allow_social_tool') == 'true') {
            if (!$no_image) {
                $profile_content .= '<a style="text-align:center" href="'.api_get_path(WEB_CODE_PATH).'social/home.php">
                                    <img src="'.$img_array['file'].'"></a>';
            } else {
                $profile_content .= '<a style="text-align:center"  href="'.api_get_path(WEB_CODE_PATH).'auth/profile.php">
                                    <img title="'.get_lang('EditProfile').'" src="'.$img_array['file'].'"></a>';
            }
        }
        if (!empty($profile_content)) {
            $this->show_right_block(null, null, 'user_image_block', array('content' => $profile_content));
        }
    }

    /**
     * Return a block with course-related links. The resulting HTML block's
     * contents are only based on the user defined by the active session.
     *
     * @return string HTML <div> with links
     * @assert () != ''
     */
    public function setCourseBlock($filter = null)
    {
        $show_course_link = false;
        $display_add_course_link = false;

        if ((api_get_setting('allow_users_to_create_courses') == 'true' && api_is_allowed_to_create_course() ||
            api_is_platform_admin())
        ) {
            $display_add_course_link = true;
        }

        if (api_is_platform_admin() || api_is_course_admin() || api_is_allowed_to_create_course()) {
            $show_course_link = true;
        } else {
            if (api_get_setting('allow_students_to_browse_courses') == 'true') {
                $show_course_link = true;
            }
        }

        // My account section.
        $my_account_content = array();

        if ($display_add_course_link) {
            $my_account_content[] = array(
                'href'  => api_get_path(WEB_CODE_PATH).'create_course/add_course.php',
                'title' => api_get_setting('course_validation') == 'true' ? get_lang('CreateCourseRequest') : get_lang(
                    'CourseCreate'
                )
            );
        }

        // Sort courses.
        $url = api_get_path(WEB_CODE_PATH).'auth/courses.php?action=sortmycourses';
        $my_account_content[] = array(
            'href'  => $url,
            'title' => get_lang('SortMyCourses')
        );

        // Course management.
        if ($show_course_link) {
            if (!api_is_drh()) {
                $my_account_content[] = array(
                    'href'  => api_get_path(WEB_CODE_PATH).'auth/courses.php',
                    'title' => get_lang('CourseCatalog')
                );

                if (isset($filter) && $filter == 'history') {
                    $my_account_content[] = array(
                        'href'  => api_get_path(WEB_PUBLIC_PATH).'userportal',
                        'title' => get_lang('DisplayTrainingList')
                    );
                } else {
                    $my_account_content[] = array(
                        'href'  => api_get_path(WEB_PUBLIC_PATH).'userportal/history',
                        'title' => get_lang('HistoryTrainingSessions')
                    );
                }
            } else {
                $my_account_content[] = array(
                    'href'  => api_get_path(WEB_CODE_PATH).'dashboard/index.php',
                    'title' => get_lang('Dashboard')
                );
            }
        }

        $this->show_right_block(get_lang('Courses'), $my_account_content, 'course_block');
    }

    /**
     *
     */
    public function setSessionBlock()
    {
        $showSessionBlock = false;

        if (api_is_platform_admin()) {
            $showSessionBlock = true;
        }

        if (api_get_setting('allow_teachers_to_create_sessions') == 'true' && api_is_allowed_to_create_course()) {
            $showSessionBlock = true;
        }

        if ($showSessionBlock) {
            $content = array(
                array(
                    'href'  => api_get_path(WEB_CODE_PATH).'session/session_add.php',
                    'title' => get_lang('AddSession')
                )
            );
            $this->show_right_block(get_lang('Sessions'), $content, 'session_block');
        }
    }

    /**
     * Returns the profile block, showing links to the messaging and social
     * network tools. The user ID is taken from the active session
     * @return string HTML <div> block
     * @assert () != ''
     */
    public function setProfileBlock()
    {
        if (api_get_setting('allow_message_tool') == 'true') {
            if (api_get_setting('allow_social_tool') == 'true') {
                $this->show_right_block(get_lang('Profile'), array(), 'profile_social_block');
            } else {
                $this->show_right_block(get_lang('Profile'), array(), 'profile_block');
            }
        }
    }

    /**
     * Get the course - session menu
     */
    public function setCourseSessionMenu()
    {
        $courseURL             = Session::$urlGenerator->generate('userportal', array('type' => 'courses'));
        $sessionURL            = Session::$urlGenerator->generate('userportal', array('type' => 'sessions'));
        $courseCategoriesURL   = Session::$urlGenerator->generate('userportal', array('type' => 'mycoursecategories'));
        $specialCoursesURL     = Session::$urlGenerator->generate('userportal', array('type' => 'specialcourses'));
        $sessionCategoriesURL  = Session::$urlGenerator->generate('userportal', array('type' => 'sessioncategories'));

        $params = array(
            array('href' => $courseURL, 'title' => get_lang('Courses')),
            array('href' => $specialCoursesURL, 'title' => get_lang('SpecialCourses')),
            array('href' => $courseCategoriesURL, 'title' => get_lang('MyCourseCategories')),
            array('href' => $sessionURL, 'title' => get_lang('Sessions')),
            array('href' => $sessionCategoriesURL, 'title' => get_lang('SessionsCategories')),
        );
        $this->show_right_block(get_lang('CourseSessionBlock'), $params, 'course_session_block');
    }

    /**
     * Returns a list of the most popular courses of the moment (also called
     * "hot courses").
     * @uses CourseManager::returnHotCourses() in fact, the current method is only a bypass to this method
     * @return string HTML <div> with the most popular courses
     * @assert () != ''
     */
    public function returnHotCourses()
    {
        return CourseManager::returnHotCourses();
    }

    /**
     * Returns an online help block read from the home/home_menu_[lang].html
     * file
     * @return string HTML block
     * @assert () != ''
     */
    public function returnHelp()
    {
        $home                   = api_get_home_path();
        $user_selected_language = api_get_interface_language();
        $sys_path               = api_get_path(SYS_PATH);
        $platformLanguage       = api_get_setting('platformLanguage');

        if (!isset($user_selected_language)) {
            $user_selected_language = $platformLanguage;
        }
        $home_menu = @(string)file_get_contents($sys_path.$home.'home_menu_'.$user_selected_language.'.html');
        if (!empty($home_menu)) {
            $home_menu_content = api_to_system_encoding($home_menu, api_detect_encoding(strip_tags($home_menu)));
            $this->show_right_block(
                get_lang('MenuGeneral'),
                null,
                'help_block',
                array('content' => $home_menu_content)
            );
        }
    }

    /**
     * Returns an HTML block with links to the skills tools
     * @return string HTML <div> block
     * @assert () != ''
     */
    public function returnSkillsLinks()
    {
        if (api_get_setting('allow_skills_tool') == 'true') {
            $content   = array();
            $content[] = array(
                'title' => get_lang('MySkills'),
                'href'  => api_get_path(WEB_CODE_PATH).'social/skills_wheel.php'
            );

            if (api_get_setting('allow_hr_skills_management') == 'true' || api_is_platform_admin()) {
                $content[] = array(
                    'title' => get_lang('ManageSkills'),
                    'href'  => api_get_path(WEB_CODE_PATH).'admin/skills_wheel.php'
                );
            }
            $this->show_right_block(get_lang("Skills"), $content, 'skill_block');
        }
    }

    /**
     * Returns an HTML block with the notice, as found in the
     * home/home_notice_[lang].html file
     * @return string HTML <div> block
     * @assert () != ''
     */
    public function returnNotice()
    {
        $sys_path               = api_get_path(SYS_PATH);
        $user_selected_language = api_get_interface_language();
        $home                   = api_get_home_path();

        // Notice
        $home_notice = @(string)file_get_contents($sys_path.$home.'home_notice_'.$user_selected_language.'.html');
        if (empty($home_notice)) {
            $home_notice = @(string)file_get_contents($sys_path.$home.'home_notice.html');
        }

        if (!empty($home_notice)) {
            $home_notice = api_to_system_encoding($home_notice, api_detect_encoding(strip_tags($home_notice)));
            $home_notice = Display::div($home_notice, array('class' => 'homepage_notice'));

            $this->show_right_block(get_lang('Notice'), null, 'notice_block', array('content' => $home_notice));
        }
    }

    /**
     * Returns the received content packaged in <div> block, with the title as
     * <h4>
     * @param string Title to include as h4
     * @param string Longer content to show (usually a <ul> list)
     * @param string ID to be added to the HTML attributes for the block
     * @param array Array of attributes to add to the HTML block
     * @return string HTML <div> block
     * @assert ('a','') != ''
     * @todo use the menu builder
     */
    public function show_right_block($title, $content, $id, $params = null)
    {
        if (!empty($id)) {
            $params['id'] = $id;
        }
        $block_menu = array(
            'id'       => $params['id'],
            'title'    => $title,
            'elements' => $content,
            'content'  => isset($params['content']) ? $params['content'] : null
        );

        //$app['template']->assign($id, $block_menu);
    }


    /**
     * Returns a content search form in an HTML <div>, pointing at the
     * main/search/ directory. If search_enabled is not set, then it returns
     * an empty string
     * @return string HTML <div> block showing the search form, or an empty string if search not enabled
     * @assert () !== false
     */
    public function return_search_block()
    {
        $html = '';
        if (api_get_setting('search_enabled') == 'true') {
            $html .= '<div class="searchbox">';
            $search_btn     = get_lang('Search');
            $search_content = '<br />
                <form action="main/search/" method="post">
                <input type="text" id="query" class="span2" name="query" value="" />
                <button class="save" type="submit" name="submit" value="'.$search_btn.'" />'.$search_btn.' </button>
                </form></div>';
            $html .= $this->show_right_block(get_lang('Search'), $search_content, 'search_block');
        }

        return $html;
    }

    /**
     * Returns a list of announcements
     * @param int User ID
     * @param bool True: show the announcements as a slider. False: show them as a vertical list
     * @return string HTML list of announcements
     * @assert () != ''
     * @assert (1) != ''
     */
    public function getAnnouncements($user_id = null, $show_slide = true)
    {
        // Display System announcements
        $announcement = isset($_GET['announcement']) ? intval($_GET['announcement']) : null;

        if (!api_is_anonymous() && $user_id) {
            $visibility = api_is_allowed_to_create_course(
            ) ? SystemAnnouncementManager::VISIBLE_TEACHER : SystemAnnouncementManager::VISIBLE_STUDENT;
            if ($show_slide) {
                $announcements = SystemAnnouncementManager::display_announcements_slider($visibility, $announcement);
            } else {
                $announcements = SystemAnnouncementManager::display_all_announcements($visibility, $announcement);
            }
        } else {
            if ($show_slide) {
                $announcements = SystemAnnouncementManager::display_announcements_slider(
                    SystemAnnouncementManager::VISIBLE_GUEST,
                    $announcement
                );
            } else {
                $announcements = SystemAnnouncementManager::display_all_announcements(
                    SystemAnnouncementManager::VISIBLE_GUEST,
                    $announcement
                );
            }
        }

        return $announcements;
    }

    /**
     * Return the homepage, including announcements
     * @return string The portal's homepage as an HTML string
     * @assert () != ''
     */
    public function returnHomePage()
    {
        // Including the page for the news
        $html          = null;
        $home          = api_get_path(SYS_DATA_PATH).api_get_home_path();
        $home_top_temp = null;

        if (!empty($_GET['include']) && preg_match('/^[a-zA-Z0-9_-]*\.html$/', $_GET['include'])) {
            $open = @(string)file_get_contents(api_get_path(SYS_PATH).$home.$_GET['include']);
            $html = api_to_system_encoding($open, api_detect_encoding(strip_tags($open)));
        } else {
            $user_selected_language = api_get_user_language();

            if (!file_exists($home.'home_news_'.$user_selected_language.'.html')) {
                if (file_exists($home.'home_top.html')) {
                    $home_top_temp = file($home.'home_top.html');
                } else {
                    //$home_top_temp = file('home/'.'home_top.html');
                }
                if (!empty($home_top_temp)) {
                    $home_top_temp = implode('', $home_top_temp);
                }
            } else {
                if (file_exists($home.'home_top_'.$user_selected_language.'.html')) {
                    $home_top_temp = file_get_contents($home.'home_top_'.$user_selected_language.'.html');
                } else {
                    $home_top_temp = file_get_contents($home.'home_top.html');
                }
            }

            if (empty($home_top_temp) && api_is_platform_admin()) {
                $home_top_temp = get_lang('PortalHomepageDefaultIntroduction');
            }
            $open = str_replace('{rel_path}', api_get_path(REL_PATH), $home_top_temp);
            if (!empty($open)) {
                $html = api_to_system_encoding($open, api_detect_encoding(strip_tags($open)));
            }
        }

        return $html;
    }

    /**
     * Returns the reservation block (if the reservation tool is enabled)
     * @return string HTML block, or empty string if reservation tool is disabled
     * @assert () == ''
     */
    public function return_reservation_block()
    {
        $html            = '';
        $booking_content = null;
        if (api_get_setting('allow_reservation') == 'true' && api_is_allowed_to_create_course()) {
            $booking_content .= '<ul class="nav nav-list">';
            $booking_content .= '<a href="main/reservation/reservation.php">'.get_lang(
                'ManageReservations'
            ).'</a><br />';
            $booking_content .= '</ul>';
            $html .= $this->show_right_block(get_lang('Booking'), $booking_content, 'reservation_block');
        }

        return $html;
    }

    /**
     * Returns an HTML block with classes (if show_groups_to_users is true)
     * @return string A list of links to users classes tools, or an empty string if show_groups_to_users is disabled
     * @assert  () == ''
     */
    public function return_classes_block()
    {
        $html = '';
        if (api_get_setting('show_groups_to_users') == 'true') {
            $usergroup      = new Usergroup();
            $usergroup_list = $usergroup->get_usergroup_by_user(api_get_user_id());
            $classes        = '';
            if (!empty($usergroup_list)) {
                foreach ($usergroup_list as $group_id) {
                    $data         = $usergroup->get($group_id);
                    $data['name'] = Display::url(
                        $data['name'],
                        api_get_path(WEB_CODE_PATH).'user/classes.php?id='.$data['id']
                    );
                    $classes .= Display::tag('li', $data['name']);
                }
            }
            if (api_is_platform_admin()) {
                $classes .= Display::tag(
                    'li',
                    Display::url(get_lang('AddClasses'), api_get_path(WEB_CODE_PATH).'admin/usergroups.php?action=add')
                );
            }
            if (!empty($classes)) {
                $classes = Display::tag('ul', $classes, array('class' => 'nav nav-list'));
                $html .= $this->show_right_block(get_lang('Classes'), $classes, 'classes_block');
            }
        }

        return $html;
    }

    /**
     * Prepares a block with all the pending exercises in all courses
     * @param array Array of courses (arrays) of the user
     * @return void Doesn't return anything but prepares and HTML block for use in templates
     * @assert () !== 1
     */
    public function return_exercise_block($personal_course_list, $tpl)
    {
        $exercise_list = array();
        if (!empty($personal_course_list)) {
            foreach ($personal_course_list as $course_item) {
                $course_code = $course_item['c'];
                $session_id  = $course_item['id_session'];

                $exercises = ExerciseLib::get_exercises_to_be_taken($course_code, $session_id);

                foreach ($exercises as $exercise_item) {
                    $exercise_item['course_code'] = $course_code;
                    $exercise_item['session_id']  = $session_id;
                    $exercise_item['tms']         = api_strtotime($exercise_item['end_time'], 'UTC');

                    $exercise_list[] = $exercise_item;
                }
            }
            if (!empty($exercise_list)) {
                $exercise_list = ArrayClass::msort($exercise_list, 'tms');
                $my_exercise   = $exercise_list[0];
                $url           = Display::url(
                    $my_exercise['title'],
                    api_get_path(
                        WEB_CODE_PATH
                    ).'exercice/overview.php?exerciseId='.$my_exercise['id'].'&cidReq='.$my_exercise['course_code'].'&id_session='.$my_exercise['session_id']
                );
                $tpl->assign('exercise_url', $url);
                $tpl->assign(
                    'exercise_end_date',
                    api_convert_and_format_date($my_exercise['end_time'], DATE_FORMAT_SHORT)
                );
            }
        }
    }

    /**
     * Returns links to teachers tools (create course, etc) based on the user
     * in the active session
     * @return string HTML <div> block
     * @assert () == ''
     */
    public function return_teacher_link()
    {
        $user_id = api_get_user_id();

        if (!empty($user_id)) {
            // tabs that are deactivated are added here

            $show_menu        = false;
            $show_create_link = false;
            $show_course_link = false;

            if (api_is_platform_admin() || api_is_course_admin() || api_is_allowed_to_create_course()) {
                $show_menu        = true;
                $show_course_link = true;
            } else {
                if (api_get_setting('allow_students_to_browse_courses') == 'true') {
                    $show_menu        = true;
                    $show_course_link = true;
                }
            }

            if ($show_menu && ($show_create_link || $show_course_link)) {
                $show_menu = true;
            } else {
                $show_menu = false;
            }
        }

        // My Account section
        $elements = array();
        if ($show_menu) {
            if ($show_create_link) {
                $elements[] = array(
                    'href'  => api_get_path(WEB_CODE_PATH).'create_course/add_course.php',
                    'title' => (api_get_setting('course_validation') == 'true' ? get_lang(
                        'CreateCourseRequest'
                    ) : get_lang('CourseCreate'))
                );
            }

            if ($show_course_link) {
                if (!api_is_drh() && !api_is_session_admin()) {
                    $elements[] = array(
                        'href'  => api_get_path(WEB_CODE_PATH).'auth/courses.php',
                        'title' => get_lang('CourseCatalog')
                    );
                } else {
                    $elements[] = array(
                        'href'  => api_get_path(WEB_CODE_PATH).'dashboard/index.php',
                        'title' => get_lang('Dashboard')
                    );
                }
            }
        }
        $this->show_right_block(get_lang('Courses'), $elements, 'teacher_block');
    }

    /**
     * Display list of courses in a category.
     * (for anonymous users)
     *
     * @version 1.1
     * @author Patrick Cool <patrick.cool@UGent.be>, Ghent University - refactoring and code cleaning
     * @author Julio Montoya <gugli100@gmail.com>, Beeznest template modifs
     * @assert () !== 0
     */
    public function return_courses_in_categories()
    {
        $result = '';
        $stok   = Security::get_token();

        // Initialization.
        $user_identified                  = (api_get_user_id() > 0 && !api_is_anonymous());
        $web_course_path                  = api_get_path(WEB_COURSE_PATH);
        $category                         = Database::escape_string($_GET['category']);
        $setting_show_also_closed_courses = api_get_setting('show_closed_courses') == 'true';

        // Database table definitions.
        $main_course_table   = Database :: get_main_table(TABLE_MAIN_COURSE);
        $main_category_table = Database :: get_main_table(TABLE_MAIN_CATEGORY);

        // Get list of courses in category $category.
        $sql_get_course_list = "SELECT * FROM $main_course_table cours
                                    WHERE category_code = '".Database::escape_string($_GET['category'])."'
                                    ORDER BY title, UPPER(visual_code)";

        // Showing only the courses of the current access_url_id.
        if (api_is_multiple_url_enabled()) {
            $url_access_id = api_get_current_access_url_id();
            if ($url_access_id != -1) {
                $tbl_url_rel_course  = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE);
                $sql_get_course_list = "SELECT * FROM $main_course_table as course INNER JOIN $tbl_url_rel_course as url_rel_course
                        ON (url_rel_course.c_id = course.id)
                        WHERE access_url_id = $url_access_id AND category_code = '".Database::escape_string(
                    $_GET['category']
                )."' ORDER BY title, UPPER(visual_code)";
            }
        }

        // Removed: AND cours.visibility='".COURSE_VISIBILITY_OPEN_WORLD."'
        $sql_result_courses = Database::query($sql_get_course_list);

        while ($course_result = Database::fetch_array($sql_result_courses)) {
            $course_list[] = $course_result;
        }

        $platform_visible_courses = '';
        // $setting_show_also_closed_courses
        if ($user_identified) {
            if ($setting_show_also_closed_courses) {
                $platform_visible_courses = '';
            } else {
                $platform_visible_courses = "  AND (t3.visibility='".COURSE_VISIBILITY_OPEN_WORLD."' OR t3.visibility='".COURSE_VISIBILITY_OPEN_PLATFORM."' )";
            }
        } else {
            if ($setting_show_also_closed_courses) {
                $platform_visible_courses = '';
            } else {
                $platform_visible_courses = "  AND (t3.visibility='".COURSE_VISIBILITY_OPEN_WORLD."' )";
            }
        }
        $sqlGetSubCatList = "
                    SELECT t1.name,t1.code,t1.parent_id,t1.children_count,COUNT(DISTINCT t3.code) AS nbCourse
                    FROM $main_category_table t1
                    LEFT JOIN $main_category_table t2 ON t1.code=t2.parent_id
                    LEFT JOIN $main_course_table t3 ON (t3.category_code=t1.code $platform_visible_courses)
                    WHERE t1.parent_id ".(empty($category) ? "IS NULL" : "='$category'")."
                    GROUP BY t1.name,t1.code,t1.parent_id,t1.children_count ORDER BY t1.tree_pos, t1.name";


        // Showing only the category of courses of the current access_url_id
        if (api_is_multiple_url_enabled()) {
            $url_access_id = api_get_current_access_url_id();
            if ($url_access_id != -1) {
                $tbl_url_rel_course = Database::get_main_table(TABLE_MAIN_ACCESS_URL_REL_COURSE);
                $sqlGetSubCatList   = "
                    SELECT t1.name,t1.code,t1.parent_id,t1.children_count,COUNT(DISTINCT t3.code) AS nbCourse
                    FROM $main_category_table t1
                    LEFT JOIN $main_category_table t2 ON t1.code=t2.parent_id
                    LEFT JOIN $main_course_table t3 ON (t3.category_code=t1.code $platform_visible_courses)
                    INNER JOIN $tbl_url_rel_course as url_rel_course
                        ON (url_rel_course.c_id = t3.id)
                    WHERE access_url_id = $url_access_id AND t1.parent_id ".(empty($category) ? "IS NULL" : "='$category'")."
                    GROUP BY t1.name,t1.code,t1.parent_id,t1.children_count ORDER BY t1.tree_pos, t1.name";
            }
        }

        $resCats       = Database::query($sqlGetSubCatList);
        $thereIsSubCat = false;
        if (Database::num_rows($resCats) > 0) {
            $htmlListCat = Display::page_header(get_lang('CatList'));
            $htmlListCat .= '<ul>';
            while ($catLine = Database::fetch_array($resCats)) {
                if ($catLine['code'] != $category) {
                    $category_has_open_courses = $this->category_has_open_courses($catLine['code']);
                    if ($category_has_open_courses) {
                        // The category contains courses accessible to anonymous visitors.
                        $htmlListCat .= '<li>';
                        $htmlListCat .= '<a href="'.api_get_self(
                        ).'?category='.$catLine['code'].'">'.$catLine['name'].'</a>';
                        if (api_get_setting('show_number_of_courses') == 'true') {
                            $htmlListCat .= ' ('.$catLine['nbCourse'].' '.get_lang('Courses').')';
                        }
                        $htmlListCat .= "</li>";
                        $thereIsSubCat = true;
                    } elseif ($catLine['children_count'] > 0) {
                        // The category has children, subcategories.
                        $htmlListCat .= '<li>';
                        $htmlListCat .= '<a href="'.api_get_self(
                        ).'?category='.$catLine['code'].'">'.$catLine['name'].'</a>';
                        $htmlListCat .= "</li>";
                        $thereIsSubCat = true;
                    } elseif (api_get_setting('show_empty_course_categories') == 'true') {
                        /* End changed code to eliminate the (0 courses) after empty categories. */
                        $htmlListCat .= '<li>';
                        $htmlListCat .= $catLine['name'];
                        $htmlListCat .= "</li>";
                        $thereIsSubCat = true;
                    } // Else don't set thereIsSubCat to true to avoid printing things if not requested.
                } else {
                    $htmlTitre = '<p>';
                    if (api_get_setting('show_back_link_on_top_of_tree') == 'true') {
                        $htmlTitre .= '<a href="'.api_get_self().'">&lt;&lt; '.get_lang('BackToHomePage').'</a>';
                    }
                    if (!is_null($catLine['parent_id']) ||
                        (api_get_setting('show_back_link_on_top_of_tree') != 'true' &&
                        !is_null($catLine['code']))
                    ) {
                        $htmlTitre .= '<a href="'.api_get_self(
                        ).'?category='.$catLine['parent_id'].'">&lt;&lt; '.get_lang('Up').'</a>';
                    }
                    $htmlTitre .= "</p>";
                    if ($category != "" && !is_null($catLine['code'])) {
                        $htmlTitre .= '<h3>'.$catLine['name']."</h3>";
                    } else {
                        $htmlTitre .= '<h3>'.get_lang('Categories')."</h3>";
                    }
                }
            }
            $htmlListCat .= "</ul>";
        }
        $result .= $htmlTitre;
        if ($thereIsSubCat) {
            $result .= $htmlListCat;
        }
        while ($categoryName = Database::fetch_array($resCats)) {
            $result .= '<h3>'.$categoryName['name']."</h3>\n";
        }
        $numrows             = Database::num_rows($sql_result_courses);
        $courses_list_string = '';
        $courses_shown       = 0;
        if ($numrows > 0) {

            $courses_list_string .= Display::page_header(get_lang('CourseList'));
            $courses_list_string .= "<ul>";

            if (api_get_user_id()) {
                $courses_of_user = $this->get_courses_of_user(api_get_user_id());
            }

            foreach ($course_list as $course) {
                // $setting_show_also_closed_courses
                if (!$setting_show_also_closed_courses) {
                    // If we do not show the closed courses
                    // we only show the courses that are open to the world (to everybody)
                    // and the courses that are open to the platform (if the current user is a registered user.
                    if (($user_identified && $course['visibility'] == COURSE_VISIBILITY_OPEN_PLATFORM) || ($course['visibility'] == COURSE_VISIBILITY_OPEN_WORLD)) {
                        $courses_shown++;
                        $courses_list_string .= "<li>\n";
                        $courses_list_string .= '<a href="'.$web_course_path.$course['directory'].'/">'.$course['title'].'</a><br />';
                        $course_details = array();
                        if (api_get_setting('display_coursecode_in_courselist') == 'true') {
                            $course_details[] = $course['visual_code'];
                        }
                        if (api_get_setting('display_teacher_in_courselist') == 'true') {
                            $course_details[] = $course['tutor_name'];
                        }
                        if (api_get_setting(
                            'show_different_course_language'
                        ) == 'true' && $course['course_language'] != api_get_setting('platformLanguage')
                        ) {
                            $course_details[] = $course['course_language'];
                        }
                        $courses_list_string .= implode(' - ', $course_details);
                        $courses_list_string .= "</li>\n";
                    }
                } else {
                    // We DO show the closed courses.
                    // The course is accessible if (link to the course homepage):
                    // 1. the course is open to the world (doesn't matter if the user is logged in or not): $course['visibility'] == COURSE_VISIBILITY_OPEN_WORLD);
                    // 2. the user is logged in and the course is open to the world or open to the platform: ($user_identified && $course['visibility'] == COURSE_VISIBILITY_OPEN_PLATFORM);
                    // 3. the user is logged in and the user is subscribed to the course and the course visibility is not COURSE_VISIBILITY_CLOSED;
                    // 4. the user is logged in and the user is course admin of te course (regardless of the course visibility setting);
                    // 5. the user is the platform admin api_is_platform_admin().
                    //
                    $courses_shown++;
                    $courses_list_string .= "<li>\n";
                    if ($course['visibility'] == COURSE_VISIBILITY_OPEN_WORLD
                        || ($user_identified && $course['visibility'] == COURSE_VISIBILITY_OPEN_PLATFORM)
                        || ($user_identified && key_exists(
                            $course['code'],
                            $courses_of_user
                        ) && $course['visibility'] != COURSE_VISIBILITY_CLOSED)
                        || $courses_of_user[$course['code']]['status'] == '1'
                        || api_is_platform_admin()
                    ) {
                        $courses_list_string .= '<a href="'.$web_course_path.$course['directory'].'/">';
                    }
                    $courses_list_string .= $course['title'];
                    if ($course['visibility'] == COURSE_VISIBILITY_OPEN_WORLD
                        || ($user_identified && $course['visibility'] == COURSE_VISIBILITY_OPEN_PLATFORM)
                        || ($user_identified && key_exists(
                            $course['code'],
                            $courses_of_user
                        ) && $course['visibility'] != COURSE_VISIBILITY_CLOSED)
                        || $courses_of_user[$course['code']]['status'] == '1'
                        || api_is_platform_admin()
                    ) {
                        $courses_list_string .= '</a><br />';
                    }
                    $course_details = array();
                    if (api_get_setting('display_coursecode_in_courselist') == 'true') {
                        $course_details[] = $course['visual_code'];
                    }
//                        if (api_get_setting('display_coursecode_in_courselist') == 'true' && api_get_setting('display_teacher_in_courselist') == 'true') {
//                        $courses_list_string .= ' - ';
//                }
                    if (api_get_setting('display_teacher_in_courselist') == 'true') {
                        $course_details[] = $course['tutor_name'];
                    }
                    if (api_get_setting(
                        'show_different_course_language'
                    ) == 'true' && $course['course_language'] != api_get_setting('platformLanguage')
                    ) {
                        $course_details[] = $course['course_language'];
                    }
                    if (api_get_setting(
                        'show_different_course_language'
                    ) == 'true' && $course['course_language'] != api_get_setting('platformLanguage')
                    ) {
                        $course_details[] = $course['course_language'];
                    }

                    $courses_list_string .= implode(' - ', $course_details);
                    // We display a subscription link if:
                    // 1. it is allowed to register for the course and if the course is not already in the courselist of the user and if the user is identiefied
                    // 2.
                    if ($user_identified && !array_key_exists($course['code'], $courses_of_user)) {
                        if ($course['subscribe'] == '1') {
                            $courses_list_string .= '<form action="main/auth/courses.php?action=subscribe&category='.Security::remove_XSS(
                                $_GET['category']
                            ).'" method="post">';
                            $courses_list_string .= '<input type="hidden" name="sec_token" value="'.$stok.'">';
                            $courses_list_string .= '<input type="hidden" name="subscribe" value="'.$course['code'].'" />';
                            $courses_list_string .= '<input type="image" name="unsub" src="'.api_get_path(WEB_IMG_PATH).'enroll.gif" alt="'.get_lang('Subscribe').'" />'.get_lang('Subscribe').'
                            </form>';
                        } else {
                            $courses_list_string .= '<br />'.get_lang('SubscribingNotAllowed');
                        }
                    }
                    $courses_list_string .= "</li>";
                } //end else
            } // end foreach
            $courses_list_string .= "</ul>";
        }
        if ($courses_shown > 0) {
            // Only display the list of courses and categories if there was more than
            // 0 courses visible to the world (we're in the anonymous list here).
            $result .= $courses_list_string;
        }
        if ($category != '') {
            $result .= '<p><a href="'.api_get_self().'"> '.Display :: return_icon('back.png', get_lang('BackToHomePage')).get_lang('BackToHomePage').'</a></p>';
        }

        return $result;
    }

    public function returnMyCourseCategories($user_id, $filter, $page)
    {
        if (empty($user_id)) {
            return false;
        }
        $loadDirs = api_get_setting('show_documents_preview') == 'true' ? true : false;
        $start    = ($page - 1) * $this->maxPerPage;

        $nbResults = (int)CourseManager::displayPersonalCourseCategories($user_id, $filter, $loadDirs, true);

        $html = CourseManager::displayPersonalCourseCategories(
            $user_id,
            $filter,
            $loadDirs,
            false,
            $start,
            $this->maxPerPage
        );

        $adapter    = new FixedAdapter($nbResults, array());
        $pagerfanta = new Pagerfanta($adapter);
        $pagerfanta->setMaxPerPage($this->maxPerPage); // 10 by default
        $pagerfanta->setCurrentPage($page); // 1 by default

        $this->app['pagerfanta.view.router.name']   = 'userportal';
        $this->app['pagerfanta.view.router.params'] = array(
            'filter' => $filter,
            'type'   => 'courses',
            'page'   => $page
        );
        $this->app['template']->assign('pagination', $pagerfanta);

        return $html;

    }

    function returnSpecialCourses($user_id, $filter, $page)
    {
        if (empty($user_id)) {
            return false;
        }

        $loadDirs = api_get_setting('show_documents_preview') == 'true' ? true : false;
        $start    = ($page - 1) * $this->maxPerPage;

        $nbResults = CourseManager::displaySpecialCourses($user_id, $filter, $loadDirs, true);

        $html = CourseManager::displaySpecialCourses($user_id, $filter, $loadDirs, false, $start, $this->maxPerPage);
        if (!empty($html)) {

            $adapter    = new FixedAdapter($nbResults, array());
            $pagerfanta = new Pagerfanta($adapter);
            $pagerfanta->setMaxPerPage($this->maxPerPage); // 10 by default
            $pagerfanta->setCurrentPage($page); // 1 by default
            $this->app['pagerfanta.view.router.name']   = 'userportal';
            $this->app['pagerfanta.view.router.params'] = array(
                'filter' => $filter,
                'type'   => 'courses',
                'page'   => $page
            );
            $this->app['template']->assign('pagination', $pagerfanta);
        }

        return $html;
    }

    /**
    * The most important function here, prints the session and course list (user_portal.php)
    *
    * @param int User id
    * @param string filter
    * @param int page
    * @return string HTML list of sessions and courses
    * @assert () === false
    *
    */
    public function returnCourses($user_id, $filter, $page)
    {
        if (empty($user_id)) {
            return false;
        }

        $loadDirs = api_get_setting('show_documents_preview') == 'true' ? true : false;
        $start    = ($page - 1) * $this->maxPerPage;

        $nbResults = CourseManager::displayCourses($user_id, $filter, $loadDirs, true);
        $html = CourseManager::displayCourses($user_id, $filter, $loadDirs, false, $start, $this->maxPerPage);

        if (!empty($html)) {
            $adapter    = new FixedAdapter($nbResults, array());
            $pagerfanta = new Pagerfanta($adapter);
            $pagerfanta->setMaxPerPage($this->maxPerPage); // 10 by default
            $pagerfanta->setCurrentPage($page); // 1 by default

            /*
            Original pagination construction
            $view = new TwitterBootstrapView();
            $routeGenerator = function($page) use ($app, $filter) {
                return $app['url_generator']->generate('userportal', array(
                    'filter' => $filter,
                    'type' => 'courses',
                    'page' => $page)
                );
            };
            $pagination = $view->render($pagerfanta, $routeGenerator, array(
                'proximity' => 3,
            ));
            */
            //Pagination using the pagerfanta silex service provider
            /*$this->app['pagerfanta.view.router.name']   = 'userportal';
            $this->app['pagerfanta.view.router.params'] = array(
                'filter' => $filter,
                'type'   => 'courses',
                'page'   => $page
            );
            $this->app['template']->assign('pagination', $pagerfanta);*/
            // {{ pagerfanta(my_pager, 'twitter_bootstrap3') }}
        }

        return $html;
    }

    public function returnSessionsCategories($user_id, $filter, $page)
    {
        if (empty($user_id)) {
            return false;
        }

        $load_history = (isset($filter) && $filter == 'history') ? true : false;

        $start = ($page - 1) * $this->maxPerPage;

        $nbResults          = UserManager::getCategories($user_id, false, true, true);
        $session_categories = UserManager::getCategories($user_id, false, false, true, $start, $this->maxPerPage);

        $html = null;
        //Showing history title
        if ($load_history) {
            $html .= Display::page_subheader(get_lang('HistoryTrainingSession'));
            if (empty($session_categories)) {
                $html .= get_lang('YouDoNotHaveAnySessionInItsHistory');
            }
        }

        $load_directories_preview = api_get_setting('show_documents_preview') == 'true' ? true : false;
        $sessions_with_category   = $html;

        if (isset($session_categories) && !empty($session_categories)) {
            foreach ($session_categories as $session_category) {
                $session_category_id = $session_category['session_category']['id'];

                // All sessions included in
                $count_courses_session = 0;
                $html_sessions         = '';
                foreach ($session_category['sessions'] as $session) {
                    $session_id = $session['session_id'];

                    // Don't show empty sessions.
                    if (count($session['courses']) < 1) {
                        continue;
                    }

                    $html_courses_session = '';
                    $count                = 0;
                    foreach ($session['courses'] as $course) {
                        if (api_get_setting('hide_courses_in_sessions') == 'false') {
                            $html_courses_session .= CourseManager::get_logged_user_course_html($course, $session_id);
                        }
                        $count_courses_session++;
                        $count++;
                    }

                    $params = array();
                    if ($count > 0) {
                        $params['icon'] = Display::return_icon(
                            'window_list.png',
                            $session['session_name'],
                            array('id' => 'session_img_'.$session_id),
                            ICON_SIZE_LARGE
                        );

                        //Default session name
                        $session_link   = $session['session_name'];
                        $params['link'] = null;

                        if (api_get_setting('session_page_enabled') == 'true' && !api_is_drh()) {
                            //session name with link
                            $session_link   = Display::tag(
                                'a',
                                $session['session_name'],
                                array('href' => api_get_path(WEB_CODE_PATH).'session/index.php?session_id='.$session_id)
                            );
                            $params['link'] = api_get_path(WEB_CODE_PATH).'session/index.php?session_id='.$session_id;
                        }

                        $params['title'] = $session_link;

                        $moved_status = SessionManager::get_session_change_user_reason($session['moved_status']);
                        $moved_status = isset($moved_status) && !empty($moved_status) ? ' ('.$moved_status.')' : null;

                        $params['subtitle'] = isset($session['coach_info']) ? $session['coach_info']['complete_name'] : null.$moved_status;
                        $params['dates']    = $session['date_message'];

                        if (api_is_platform_admin()) {
                            $params['right_actions'] = '<a href="'.api_get_path(
                                WEB_CODE_PATH
                            ).'admin/resume_session.php?id_session='.$session_id.'">'.Display::return_icon(
                                'edit.png',
                                get_lang('Edit'),
                                array('align' => 'absmiddle'),
                                ICON_SIZE_SMALL
                            ).'</a>';
                        }
                        $html_sessions .= CourseManager::course_item_html($params, true).$html_courses_session;
                    }
                }

                if ($count_courses_session > 0) {
                    $params         = array();
                    $params['icon'] = Display::return_icon(
                        'folder_blue.png',
                        $session_category['session_category']['name'],
                        array(),
                        ICON_SIZE_LARGE
                    );

                    if (api_is_platform_admin()) {
                        $params['right_actions'] = '<a href="'.api_get_path(
                            WEB_CODE_PATH
                        ).'admin/session_category_edit.php?&id='.$session_category['session_category']['id'].'">'.Display::return_icon(
                            'edit.png',
                            get_lang('Edit'),
                            array(),
                            ICON_SIZE_SMALL
                        ).'</a>';
                    }

                    $params['title'] = $session_category['session_category']['name'];

                    if (api_is_platform_admin()) {
                        $params['link'] = api_get_path(
                            WEB_CODE_PATH
                        ).'admin/session_category_edit.php?&id='.$session_category['session_category']['id'];
                    }

                    $session_category_start_date = $session_category['session_category']['date_start'];
                    $session_category_end_date   = $session_category['session_category']['date_end'];

                    if (!empty($session_category_start_date) && $session_category_start_date != '0000-00-00' && !empty($session_category_end_date) && $session_category_end_date != '0000-00-00') {
                        $params['subtitle'] = sprintf(
                            get_lang('FromDateXToDateY'),
                            $session_category['session_category']['date_start'],
                            $session_category['session_category']['date_end']
                        );
                    } else {
                        if (!empty($session_category_start_date) && $session_category_start_date != '0000-00-00') {
                            $params['subtitle'] = get_lang('From').' '.$session_category_start_date;
                        }
                        if (!empty($session_category_end_date) && $session_category_end_date != '0000-00-00') {
                            $params['subtitle'] = get_lang('Until').' '.$session_category_end_date;
                        }
                    }
                    $sessions_with_category .= CourseManager::course_item_parent(
                        CourseManager::course_item_html($params, true),
                        $html_sessions
                    );
                }
            }

            //Pagination
            $adapter    = new FixedAdapter($nbResults, array());
            $pagerfanta = new Pagerfanta($adapter);
            $pagerfanta->setMaxPerPage($this->maxPerPage); // 10 by default
            $pagerfanta->setCurrentPage($page); // 1 by default

            $this->app['pagerfanta.view.router.name']   = 'userportal';
            $this->app['pagerfanta.view.router.params'] = array(
                'filter' => $filter,
                'type'   => 'sessioncategories',
                'page'   => $page
            );
            $this->app['template']->assign('pagination', $pagerfanta);
        }

        return $sessions_with_category;
    }

    /**
     * @param int $user_id
     * @param string $filter current|history
     * @param int $page
     * @return bool|null|string
     */
    public function returnSessions($user_id, $filter, $page)
    {
        if (empty($user_id)) {
            return false;
        }
        $app = $this->app;

        $loadHistory = (isset($filter) && $filter == 'history') ? true : false;

        $app['session_menu'] = function ($app) use ($loadHistory) {
            $menu = $app['knp_menu.factory']->createItem(
                'root',
                array(
                    'childrenAttributes' => array(
                        'class'        => 'nav nav-tabs',
                        'currentClass' => 'active'
                    )
                )
            );

            $current = $menu->addChild(
                get_lang('Current'),
                array(
                    'route'           => 'userportal',
                    'routeParameters' => array(
                        'filter' => 'current',
                        'type'   => 'sessions'
                    )
                )
            );
            $history = $menu->addChild(
                get_lang('HistoryTrainingSession'),
                array(
                    'route'           => 'userportal',
                    'routeParameters' => array(
                        'filter' => 'history',
                        'type'   => 'sessions'
                    )
                )
            );
            //@todo use URIVoter
            if ($loadHistory) {
                $history->setCurrent(true);
            } else {
                $current->setCurrent(true);
            }

            return $menu;
        };

        //@todo move this in template
        $app['knp_menu.menus'] = array('actions_menu' => 'session_menu');

        $start = ($page - 1) * $this->maxPerPage;

        if ($loadHistory) {
            // Load sessions in category in *history*.
            $nbResults          = (int)UserManager::get_sessions_by_category(
                $user_id,
                true,
                true,
                true,
                null,
                null,
                'no_category'
            );
            $session_categories = UserManager::get_sessions_by_category(
                $user_id,
                true,
                false,
                true,
                $start,
                $this->maxPerPage,
                'no_category'
            );
        } else {
            // Load sessions in category.
            $nbResults = (int)UserManager::get_sessions_by_category(
                $user_id,
                false,
                true,
                false,
                null,
                null,
                'no_category'
            );

            $session_categories = UserManager::get_sessions_by_category(
                $user_id,
                false,
                false,
                false,
                $start,
                $this->maxPerPage,
                'no_category'
            );
        }

        $html = null;

        // Showing history title
        if ($loadHistory) {
            // $html .= Display::page_subheader(get_lang('HistoryTrainingSession'));
            if (empty($session_categories)) {
                $html .= get_lang('YouDoNotHaveAnySessionInItsHistory');
            }
        }

        $load_directories_preview = api_get_setting('show_documents_preview') == 'true' ? true : false;

        $sessions_with_no_category = $html;

        if (isset($session_categories) && !empty($session_categories)) {

            foreach ($session_categories as $session_category) {
                $session_category_id = $session_category['session_category']['id'];

                // Sessions does not belong to a session category
                if ($session_category_id == 0) {

                    // Independent sessions
                    if (isset($session_category['sessions'])) {
                        foreach ($session_category['sessions'] as $session) {

                            $session_id = $session['session_id'];

                            // Don't show empty sessions.
                            if (count($session['courses']) < 1) {
                                continue;
                            }

                            $html_courses_session  = '';
                            $count_courses_session = 0;

                            foreach ($session['courses'] as $course) {
                                //Read only and accessible
                                if (api_get_setting('hide_courses_in_sessions') == 'false') {
                                    $html_courses_session .= CourseManager::get_logged_user_course_html(
                                        $course,
                                        $session_id,
                                        $load_directories_preview
                                    );
                                }
                                $count_courses_session++;
                            }

                            if ($count_courses_session > 0) {
                                $params               = array();
                                $params['icon']       = Display::return_icon(
                                    'window_list.png',
                                    $session['session_name'],
                                    array('id' => 'session_img_'.$session_id),
                                    ICON_SIZE_LARGE
                                );
                                $params['is_session'] = true;
                                //Default session name
                                $session_link   = $session['session_name'];
                                $params['link'] = null;

                                if (api_get_setting('session_page_enabled') == 'true' && !api_is_drh()) {
                                    //session name with link
                                    $session_link   = Display::tag(
                                        'a',
                                        $session['session_name'],
                                        array(
                                            'href' => api_get_path(
                                                WEB_CODE_PATH
                                            ).'session/index.php?session_id='.$session_id
                                        )
                                    );
                                    $params['link'] = api_get_path(
                                        WEB_CODE_PATH
                                    ).'session/index.php?session_id='.$session_id;
                                }

                                $params['title'] = $session_link;

                                $moved_status = SessionManager::get_session_change_user_reason(
                                    $session['moved_status']
                                );
                                $moved_status = isset($moved_status) && !empty($moved_status) ? ' ('.$moved_status.')' : null;

                                $params['subtitle'] = isset($session['coach_info']) ? $session['coach_info']['complete_name'] : null.$moved_status;
                                $params['dates']    = $session['date_message'];

                                $params['right_actions'] = '';
                                if (api_is_platform_admin()) {
                                    $params['right_actions'] .= '<a href="'.api_get_path(
                                        WEB_CODE_PATH
                                    ).'admin/resume_session.php?id_session='.$session_id.'">';
                                    $params['right_actions'] .= Display::return_icon(
                                        'edit.png',
                                        get_lang('Edit'),
                                        array('align' => 'absmiddle'),
                                        ICON_SIZE_SMALL
                                    ).'</a>';
                                }

                                if (api_get_setting('hide_courses_in_sessions') == 'false') {
                                    //    $params['extra'] .=  $html_courses_session;
                                }
                                $sessions_with_no_category .= CourseManager::course_item_parent(
                                    CourseManager::course_item_html($params, true),
                                    $html_courses_session
                                );
                            }
                        }
                    }
                }
            }

            $adapter    = new FixedAdapter($nbResults, array());
            $pagerfanta = new Pagerfanta($adapter);
            $pagerfanta->setMaxPerPage($this->maxPerPage); // 10 by default
            $pagerfanta->setCurrentPage($page); // 1 by default

            $this->app['pagerfanta.view.router.name']   = 'userportal';
            $this->app['pagerfanta.view.router.params'] = array(
                'filter' => $filter,
                'type'   => 'sessions',
                'page'   => $page
            );
            $this->app['template']->assign('pagination', $pagerfanta);
        }

        return $sessions_with_no_category;
    }

    /**
     * Shows a welcome message when the user doesn't have any content in
     * the course list
     * @param object A Template object used to declare variables usable in the given template
     * @return void
     * @assert () === false
     */
    public function return_welcome_to_course_block($tpl)
    {
        if (empty($tpl)) {
            return false;
        }
        $count_courses = CourseManager::count_courses();

        $course_catalog_url = api_get_path(WEB_CODE_PATH).'auth/courses.php';
        $course_list_url    = api_get_path(WEB_PATH).'user_portal.php';

        $tpl->assign('course_catalog_url', $course_catalog_url);
        $tpl->assign('course_list_url', $course_list_url);
        $tpl->assign('course_catalog_link', Display::url(get_lang('here'), $course_catalog_url));
        $tpl->assign('course_list_link', Display::url(get_lang('here'), $course_list_url));
        $tpl->assign('count_courses', $count_courses);
        $tpl->assign('welcome_to_course_block', 1);
    }

     /**
     * @param array
     */
    public function returnNavigationLinks($items)
    {
        // Main navigation section.
        // Tabs that are deactivated are added here.
        if (!empty($items)) {
            $content = '<ul class="nav nav-list">';
            foreach ($items as $section => $navigation_info) {
                $current = isset($GLOBALS['this_section']) && $section == $GLOBALS['this_section'] ? ' id="current"' : '';
                $content .= '<li '.$current.'>';
                $content .= '<a href="'.$navigation_info['url'].'" target="_self">'.$navigation_info['title'].'</a>';
                $content .= '</li>';
            }
            $content .= '</ul>';
            $this->show_right_block(get_lang('MainNavigation'), null, 'navigation_block', array('content' => $content));
        }
    }

}
