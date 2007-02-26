<?php

/*
 Plugin Name: Group Restriction
 Plugin URI: http://code.google.com/p/wp-group-restriction/
 Description: Allows to define groups of users and their access to Edit and View Pages.
 Author: Tiago Pocinho, Siemens Networks, S.A.
 Version: 1.0 RC1
 */

class userGroups {
  /**
   * Constructor for the userGroups Class.
   * Adds wordpress actions and filters.   
   **/     
  function userGroups(){
  	add_action('activate_wp-group-restriction/wp-group-restriction.php',array(&$this,'install'));
  	add_action('admin_menu', array(&$this,'add_pages'));
  	add_action('edit_user_profile', array(&$this, 'userGroupsProfile'));
  	add_action('profile_personal_options', array(&$this, 'userGroupsView'));

  	if (strstr($_SERVER['REQUEST_URI'], 'user-edit.php') !== false && $_POST['editgroups'] == "true") {
  		add_action('init', array(&$this, 'handle_user_groups_edit'));
  	}
  	 
  	add_action('personal_options_update', array(&$this, 'handle_own_groups_edit'));

  	add_action('edit_page_form', array(&$this, 'page_edit_groups'));
  	add_action('save_post', array(&$this, 'page_edit_groups_submit'));
  	add_action('publish_post', array(&$this, 'page_edit_groups_submit'));

  	//on page removal
  	add_action('delete_post', array(&$this, 'page_remove'));

  	//on user removal
  	add_action('delete_user', array(&$this, 'user_remove'));

  	//tests page reading capabilities
  	add_filter('the_content', array(&$this, 'testContent'));
  	//tests page edit capabilities
  	add_filter('user_has_cap', array(&$this, 'check_the_user_group'),10,3);

  	//filter the search removing pages with no access
  	add_filter('posts_where', array(&$this,'exclude_from_search'));
  	add_filter('posts_where_paged', array(&$this,'exclude_from_edit'));
  }
  
  /**
   * Removes read restricted pages from the search results
   * 
   * @param string $where SQL query from wordpress search
   * @return string updated query with pages to exclude
   */
  function exclude_from_search($where) {
  	global $wp_query;
	if($_REQUEST["s"]!=""){
	  	//get pages to remove from search
	  	$pages = $this->getPagesToExclude();
	  	if($pages != ""){
		  	$pagesArray = explode(",",$pages);
		  	foreach($pagesArray as $page){
		  		$where .= " AND ID <> '$page' ";
		  		//remove this page child elements as well
		  		$where .= $this->exclude_children($page);
		  	}
	  	}
	}
  	return $where;
  }
  
  /**
   * Removes write restricted pages from the backoffice
   * 
   * @param string $where SQL query from wordpress search
   * @return string updated query with pages to exclude
   */
  function exclude_from_edit($where) {
  	global $wp_query;
  	if(strpos($_SERVER["PHP_SELF"],"edit-pages.php") != false){
  		$pages = $this->getPagesToExclude(true);
	  	if($pages != ""){
	  		$where = "AND ( 1=1 ". $where . " ) ";
		  	$pagesArray = explode(",",$pages);
		  	foreach($pagesArray as $page){
		  		$where .= " AND ID <> '$page' ";
		  		//remove this page child elements as well
		  		$where .= $this->exclude_children($page);
		  	}
	  	}
	}
	return $where;
  }
  
  /**
   * Get pages to exclude based on the parent
   *    
   * @param int $parentID Page identifier   
   * @return string children to be excluded
   */
  function exclude_children($parentID){
  	global $wpdb;
  	$returnVal = "";
  	if($parentID != "" && isset($parentID)){
  	   //added "OR post_type='page'" for wordpress 2.1 compatibility
  		$query = "SELECT * FROM ".$wpdb->posts." WHERE (post_status='static' OR post_type='page')
			AND post_parent = $parentID;";
  		$children = $wpdb->get_results($query);
  		if(isset($children) && $children != ""){
  			foreach ($children as $page){
  				$returnVal .= " AND ID <> '".$page->ID."' ";
  				$this->exclude_children($page->ID);
  			}
  		}
  	}
  	return $returnVal;
  }
  
  
  /**
   * Adds group selection options to the page editor
   **/     
  function page_edit_groups(){
  	global $profileuser, $post_ID;

  	$groups = $this->getAllGroupsWithPage($post_ID);

  	?>
<div id="plugin_group" class="dbx-group">
<div
	style="background: url(images/box-butt-left.gif) no-repeat bottom left;">
<fieldset id="groupsaccess"
	style="padding-right:0px;background: url(images/box-butt-right.gif) no-repeat bottom right;">
<div
	style="margin: 5px 0px 0px -7px;background: #fff url(images/box-head-left.gif) no-repeat top left;">
<h3 class="dbx-handle"
	style="margin-left: 7px; margin-bottom: -7px;padding: 6px 1em 0px 3px;	background: #2685af url(images/box-head-right.gif) no-repeat top right;">
Groups with read access</h3>
</div>
<div
	style="margin-left: 10px;margin-right: 0px;background: url(images/box-bg-left.gif) repeat-y left;">
<div
	style="margin-left: 8px; background: url(images/box-bg-right.gif) repeat-y right; padding: 10px 10px 27px 0px;">
	<?php
	if(isset($groups) && count($groups)>0){
		/*foreach ($groups as $result) {
			if($result->id_page != ""){
				$checked = "checked";
			}else{
				$checked = "";
			}
			echo '<div style="display: block;	float: left;width: 15em;height: 2.5em;margin-bottom: auto;"><label for="group-' .$result->id. '"> ' .
                    '<input type="checkbox" name="group[' . $result->id . ']" id="group-' . $result->id . '" ' . $checked . '/>&nbsp;'
                    . $result->name . '</label></div>';
		}
		echo '<input type="hidden" name="editgroups" value="true" />';*/
		echo '<script type="text/javascript"><!--
      
	      function select_all(name, value) {
	        formblock = document.getElementById("post");
	        forminputs = formblock.getElementsByTagName("input");
	        for (i = 0; i < forminputs.length; i++) {
	          // regex here to check name attribute
	          var regex = new RegExp(name, "i");
	          if (regex.test(forminputs[i].getAttribute("name"))) {
	            forminputs[i].checked = value;
	          }
	        }
	      }
      //--></script>';
		echo "<table id='the-list-x' style='width:100%;'>";
		echo "<tr class=\"thead\"><th rowspan='2'>Group</th><th colspan='2'>Access</th></tr>";
		echo "<tr class=\"thead\"><th style='width:7em'>Read</th><th style='width:7em'>Write</th></tr>";
		$alt = false;
		foreach ($groups as $result) {
			if($alt) {
		      	$style = 'class=\'alternate\'';
		    }  else {
		      	$style = '';
		    }
		    $alt = !$alt;
			if($result->exc_read != ""){
				$canRead = "checked";
			}else{
				$canRead = "";
			}
			if($result->exc_write != ""){
				$canWrite = "checked";
			}else{
				$canWrite = "";
			}
			echo "<tr $style><td>".$result->name. "</td>\n".
				"<td style=\"text-align:center\"><input type=\"checkbox\" name=\"groupRead[]\"  value=\"". $result->id ."\" id=\"groupRead-" . $result->id . "\" " . $canRead . "/></td>\n".
				"<td style=\"text-align:center\"><input type=\"checkbox\" name=\"groupWrite[]\" value=\"". $result->id ."\" id=\"groupWrite-" . $result->id . "\" " . $canWrite . "/></td>\n</tr>\n";
		}
		echo "<td scope='col'>&nbsp;</td>";
		echo "<td scope='col' style='text-align:center;'>".
        "<a href='#' onclick='select_all(\"groupRead\", true);'>All</a>".
        " / <a href='#' onclick='select_all(\"groupRead\", false);'>None</a></td>";
        echo "<td scope='col' style='text-align:center;'>".
        "<a href='#' onclick='select_all(\"groupWrite\", true);'>All</a>".
        " / <a href='#' onclick='select_all(\"groupWrite\", false);'>None</a></td>";
		echo "</table>";
		echo '<input type="hidden" name="editgroups" value="true" />';
	} else
		echo "No user groups defined.";
	?>

</div>
</div>
</fieldset>
</div>
</div>
<br />
	<?php
  }
  
  /**
   * On page save this function saves the groups with access to the page
   **/     
  function page_edit_groups_submit(){
  	global $profileuser;

  	$vargs = func_get_args();
  	$post_ID = $vargs[0];
  	
  	
  	$readable = array();
    $writeable = array();
    $groups = array();
    
    if(isset($_POST['groupRead']))
      foreach($_POST['groupRead'] as $id){
        $readable[$id]=1;
        $writeable[$id]=0;
        $groups[] =$id;
      }
    if(isset($_POST['groupWrite']))
      foreach($_POST['groupWrite'] as $id){
        if(!isset($readable[$id])){
          $readable[$id]=0;
          $groups[] =$id;
        }
        $writeable[$id]=1;
      }
    
    array_unique($groups);
   
    $this->setPageGroups($groups,$readable,$writeable,$post_ID);

  }
  
  /**
   * Clears the group-page relations when a page is deleted 
   **/
  function page_remove(){
  	$vargs = func_get_args();
  	$post_ID = $vargs[0];

  	$this->deletePageFromGroups($post_ID);
  }
  
  /**
   *  Removes a user from its groups when a user is deleted
   **/     
  function user_remove(){
  	$vargs = func_get_args();
  	$userID = $vargs[0];

  	$this->deleteUserFromGroups($userID);
  }

  /**
   *  Creates the menus in the wordpress backoffice
   **/     
  function add_pages() {
  	// Add a new top-level menu: Groups
  	add_menu_page('Manage Groups', 'Groups', 8, 'wp-group-restriction/wp-group-restriction.php', array('userGroups','manage_page'));

  	// Add a submenu to the Groups menu for group creation
  	//add_submenu_page('wp-group-restriction/wp-group-restriction.php', 'Create Group', 'Create', 8, 'wp-group-restriction/manage', array('userGroups','create_page'));
  	 
  	// Add a submenu to the Groups menu for group page relations
  	add_submenu_page('wp-group-restriction/wp-group-restriction.php', 'Groups with Pages', 'Pages Access', 8, 'wp-group-restriction/manage_pages', array('userGroups','manage_page_groups'));

  	// Add a submenu to the Groups menu for Page group relations
  	add_submenu_page('wp-group-restriction/wp-group-restriction.php', 'Pages with groups', 'Access per Page', 8, 'wp-group-restriction/manage_groups', array('userGroups','manage_groups_by_page'));

  	// Add a submenu to the Groups menu for Page group relations
  	add_submenu_page('wp-group-restriction/wp-group-restriction.php', 'Group Members', 'Members', 8, 'wp-group-restriction/group_members', array('userGroups','manage_group_users'));
  }
  
  /**
   * displays the page content for the Groups default submenu
   **/   
  function manage_page() {
  	include_once('wp-group-page.php');
  }
  
  /**
   * displays a page with the groups and the pages it can edit
   **/     
  function manage_page_groups() {
  	include_once('wp-group-manage-pages.php');
  }
  
  /**
   * displays a page with the pages and the groups with access
   **/     
  function manage_groups_by_page() {
  	include_once('wp-group-manage-page-groups.php');
  }
  
  /**
   * displays the groups users
   **/
  function manage_group_users() {
  	include_once('wp-group-manage-members.php');
  }
  
  /**
   * Checks if the user can edit the given page
   * @return array and empty array if no access is granted or the $allcals array ortherwise.
   **/
  function check_the_user_group() {
  	$vargs = func_get_args();
  	list($allcaps, $reqcaps, $args) = $vargs;
  	//gets the page ID
  	$idPostTemp = $_REQUEST['post'];
  	if(!isset($idPostTemp)){
  		global $post;
  		$idPostTemp = $post->ID;
  		if(!isset($idPostTemp)){
  			$idPostTemp = $_POST['post_ID'];
  			if(!isset($idPostTemp)){
  				$idPostTemp = $_POST['id'];
  			}
  		}
  	}
  	
  	
  	$is_edit = in_array('edit_pages',$reqcaps); // for wordpress <2.1
  	$is_edit = $is_edit || in_array('delete_pages',$reqcaps);
  	$is_edit = $is_edit || in_array('delete_published_pages',$reqcaps); // for wordpress >=2.1
    $is_edit = $is_edit || in_array('edit_published_pages',$reqcaps);// for wordpress >=2.1
  	
    //checks if it is a static post (page) and if the intent is to edit a page
  	if($idPostTemp && ('static' == get_post_status($idPostTemp) /* wordpress < 2.1 */
        || (function_exists('get_post_type') && 'page' == get_post_type($idPostTemp)) ) /*wordpress >=2.1*/ 
       && 'edit' == $_REQUEST['action'] && $is_edit && (is_numeric($args[2]))) {
      //if user is admin or the page is not group resticted give access
  		if(!$this->hasWritePageAccess($idPostTemp)){
  			return array();
  		}
  	} else {
  		//checks if the edit link should appear in the wordpress front-office for the given page
  		if($is_edit && (is_numeric($args[2]))) {
  			//if user is admin or the page is not group resticted give access
  			if(!$this->hasWritePageAccess($idPostTemp)){
  				return array();
  			}
  		}
  		
  	}
  	return $allcaps;
  }
  
  /**
   * Displays the groups for the current user in the edit user profile page
   * 
   * @param $read - boolean indicating if the user can only read the groups
   **/     
  function userGroupsProfile ($read=false){
  	global $profileuser;

  	$userId = $profileuser->id;

  	$groups = $this->getAllGroupsWithUser($userId);

    
    echo "<br style='clear:both;'/><h3>User Groups </h3>";
  	if(isset($groups)){
			$hasGroup = false;
  		foreach ($groups as $result) {
  			if($read){
  				if($result->id_user == $userId){
  				  $hasGroup = true;
  					echo "<div>$result->name</div>";
  				}
  			}else{
  			 
  				if($result->id_user == $userId){
  					$checked = "checked";
  				}else{
  					$checked = "";
  				}
  				echo '<div style="display: block;	float: left;width: 15em;height: 2.5em;margin-bottom: auto;">' .
               '<label for="group-' .$result->id. '"> ' .
               '<input type="checkbox" name="group[' . $result->id . ']" id="group-' .
               $result->id . '" ' . $checked . $readonly .'/>&nbsp;' . $result->name .
               '</label></div>';
  			}
  		}
  		if($read && (count($groups)==0 || !$hasGroup)){
				echo "<div>The user is not a member of any group.</div>";
			}
  		echo '<input type="hidden" name="editgroups" value="true" />';
  	} else
  	echo "No user groups defined.";
  }
  
  function userGroupsView(){
  	global $profileuser;
  	if($profileuser->user_level > 7){
  		//it is an admin so he can edit himself
  		$this->userGroupsProfile();
  	}else{
  		$this->userGroupsProfile(true);
  	}
  }
  
  /**
   * Updates the groups for the current user
   **/  
  function handle_own_groups_edit() {
  	$this->handle_user_groups_edit(true);
  }
  
  /**
   * updates the groups for the edited user
   * @param boolean $useCurrentUser Indicates if the user is the current user or another user
   **/     
  function handle_user_groups_edit($useCurrentUser=false) {
  	global $user_ID, $table_prefix, $wpdb;

  	get_currentuserinfo();

  	if($useCurrentUser){
  		$user = new WP_User($user_ID);
  	}else{
  		$user = new WP_User($_POST['user_id']);
  	}
  	 
  	$groups = $this->getAllGroupsWithUser($user->id);

  	$table = $table_prefix . "ug_GroupsUsers";
  	if(isset($groups))
  	foreach ($groups as $group) {
  		if(isset($_POST['group'][$group->id])){ //Group checked
  			//if the user does not have this group
  			if($group->id_user == ""){
  				$this->createGroupUser($group->id, $user->id);
  			}
  		}else{ //Group unchecked
  			//if the user has this group
  			if($group->id_user != ""){
  				$this->deleteGroupUser($group->id, $user->id);
  			}
  		}
  	}
  }
  
  /**
   * Installation function, creates tables if needed
   **/   
  function install () {
  	global $table_prefix, $wpdb;

  	/*wordpress tables*/
  	$table_users = $table_prefix . "users";
  	$table_categories = $table_prefix . "categories";
  	$table_pages = $table_prefix . "posts"; //post_status must be "static" or the post_type must be "page"
  	 
  	/*plugin tables*/
  	$table_groups = $table_prefix . "ug_Groups";
  	$table_groupsUsers = $table_prefix . "ug_GroupsUsers";
  	$table_groupsCategory = $table_prefix . "ug_GroupsCategory";
  	$table_groupsPage = $table_prefix . "ug_GroupsPage";

  	require_once(ABSPATH . 'wp-admin/upgrade-functions.php');

  	/*Groups table*/
  	if (!userGroups::tableExists($table_groups)) {
  		$sql = "CREATE TABLE ".$table_groups." (
        id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        name tinytext NOT NULL,
        homepage text,
        UNIQUE KEY id (id)
       ) ENGINE='INNODB';";


  		dbDelta($sql);
  	}
  	 	
    /*Groups-Users relation table*/
  	if (!userGroups::tableExists($table_groupsUsers)) {
  		$sql = "CREATE TABLE ".$table_groupsUsers." (
        id_group bigint(20) UNSIGNED NOT NULL,
        id_user bigint(20) UNSIGNED NOT NULL,
        FOREIGN KEY (id_group) REFERENCES ".$table_groups."(id)
                      ON DELETE CASCADE,
        FOREIGN KEY (id_user) REFERENCES ".$table_users."(ID)
                      ON DELETE CASCADE,
		PRIMARY KEY(id_group, id_user)
       ) ENGINE='INNODB';";

  		dbDelta($sql);
  	}
  	 
  	/*Groups-Page relation table*/
  	if (!userGroups::tableExists($table_groupsPage)) {
  		$sql = "CREATE TABLE ".$table_groupsPage." (
        id_group bigint(20)  UNSIGNED NOT NULL,
        id_page bigint(20) UNSIGNED NOT NULL,
        exc_read tinyint(1) NOT NULL,
        exc_write tinyint(1) NOT NULL,
        FOREIGN KEY (id_group) REFERENCES ".$table_groups."(id)
                      ON DELETE CASCADE,
        FOREIGN KEY (id_page) REFERENCES ".$table_pages."(ID)
                      ON DELETE CASCADE,
		PRIMARY KEY(id_group, id_page)
       ) ENGINE='INNODB';";

  		dbDelta($sql);
  	}
  	
  	/*other plugins support table*/
  	$table_groupsGeneric = $table_prefix . "ug_GroupsGeneric";
  	
  	if (!userGroups::tableExists($table_groupsGeneric)) {
		$sql = "CREATE TABLE ".$table_groupsGeneric." (
					id_group bigint(20) UNSIGNED NOT NULL,
					id_resource bigint(20) UNSIGNED NOT NULL,
					permission text NOT NULL,
					plugin_name VARCHAR(128) NOT NULL,
					description text,
					FOREIGN KEY (id_group) REFERENCES ".$table_groups."(id)
						ON DELETE CASCADE,
					PRIMARY KEY(id_group, id_resource, plugin_name)					
				) ENGINE='INNODB';";

  		dbDelta($sql);
  	}
  }
  
  /**
   * Gets the groups of the given user with a column for the user
   * 
   * @param int $id - user ID 
   * @return array query results with all the groups the user is member     
   **/     
  function getGroupsWithUser($id){
  	global $table_prefix, $wpdb;

  	$table_groups = $table_prefix . "ug_Groups";
  	$table_groupsUsers = $table_prefix . "ug_GroupsUsers";


  	$query = "SELECT id, name, homepage, id_user FROM `".$table_groups."`
              INNER JOIN (".$table_groupsUsers.") 
              ON (".$table_groups.".id = ".$table_groupsUsers.".id_group
              and ".$table_groupsUsers.".id_user='".$id."') ORDER BY name;";

  	$results = $wpdb->get_results( $query );

  	return $results;
  }
  
  /**
   *  Gets all groups with a column with the user id in case the
   *  group includes the user.
   *  
   *  @param int $id - user ID
   *  @return array query results with all the groups with the user in case he is their member  
   **/     
  function getAllGroupsWithUser($id){
  	global $table_prefix, $wpdb;

  	$table_groups = $table_prefix . "ug_Groups";
  	$table_groupsUsers = $table_prefix . "ug_GroupsUsers";


  	$query = "SELECT id, name, homepage, id_user FROM `".$table_groups."`
              LEFT JOIN (".$table_groupsUsers.") 
              ON (".$table_groups.".id = ".$table_groupsUsers.".id_group
              and ".$table_groupsUsers.".id_user='".$id."') ORDER BY name;";

  	$results = $wpdb->get_results( $query );

  	return $results;
  }
  
  /**
   * Gets the groups with a column with the page id and its access 
   *    if the page belongs to that group
   * 
   * @param int $id_page - page ID  
   * @return array Query results with all the groups with the page id or blank if the group does not have access restriction
   **/     
  function getAllGroupsWithPage($id_page){
  	global $table_prefix, $wpdb;

  	$table_groups = $table_prefix . "ug_Groups";
  	$table_groupsPage = $table_prefix . "ug_GroupsPage";

  	if($id_page != ""){
  		$query = "SELECT id, name, homepage, id_page, exc_read, exc_write
              FROM `$table_groups` tg 
              LEFT JOIN ($table_groupsPage gp) 
              ON (tg.id = gp.id_group 
              and gp.id_page='".$id_page."') ORDER BY name;";
  	}else{
  		$query = "SELECT id, name, homepage FROM `".$table_groups."`;";
  	}
  	$results = $wpdb->get_results( $query );

  	return $results;
  }
  
  /**
   * Gets all pages with a column where it is possible to see if 
   *    the page restrictions
   * 
   * @param int $groupId - group ID 
   * @return array Query results     
   **/ 
  function getAllGroupPages($groupId){
  	global $table_prefix, $wpdb;

  	/*wordpress tables*/
  	$table_users = $table_prefix . "users";
  	$table_pages = $table_prefix . "posts"; //post_status == static
  	 
  	/*plugin tables*/
  	$table_groups = $table_prefix . "ug_Groups";
  	$table_groupsPage = $table_prefix . "ug_GroupsPage";
    //added "OR post_type='page'" for wordpress 2.1 compatibility
  	$query = "SELECT * FROM
                (Select p.* from ".$table_pages." p
                INNER JOIN (".$table_groupsPage." gp )
                ON ((p.post_status='static' OR p.post_type='page') and gp.id_page = p.ID and gp.id_group='".$groupId."')
                UNION
                Select * from ".$table_pages." p2
                WHERE (post_status='static' OR post_type='page') and ID NOT IN 
                  (
                    Select p.id from ".$table_pages." p
                    INNER JOIN (".$table_groupsPage." gp )
                    ON (gp.id_page = p.ID)
                  )
                )t
                Left JOIN (".$table_groupsPage." gp )
                ON ((t.post_status='static' OR t.post_type='page') and gp.id_page = t.ID and gp.id_group='".$groupId."') ORDER BY post_title;";

  	$results = $wpdb->get_results( $query );

  	return $results;
  }
  
  /**
   * Sets the group for the given pages
   * 
   *  @param array $pages - array with the ids of the pages
   *  @param array $readPageIDs - ids of the pages with read access by the group   
   *  @param array $writePageIDs - ids of the pages with write access by the grou  
   *  @param int $groupID - group identifier
   **/     
  function setGroupPages ($pages,$readPageIDs, $writePageIDs, $groupID){
  	 
  	$this->deleteAllGroupPages($groupID);

  	foreach($pages as $page) {
  		$this->setGroupPage($page,$groupID, $readPageIDs[$page], $writePageIDs[$page]);
  	}
  }
  
  /**
   * Sets the groups for the given page
   * 
   *  @param array $groups - array with the ids of the groups
   *  @param array $readGroupIDs - ids of the groups with read access to the page   
   *  @param array $writeGroupIDs - ids of the groups with write access to the page      
   *  @param int $pageID - page identifier
   **/     
  function setPageGroups ($groups, $readGroupIDs, $writeGroupIDs, $pageID){
  	$this->deletePageFromGroups($pageID);

  	foreach($groups as $group) {
  		$this->setGroupPage($pageID,$group, $readGroupIDs[$group], $writeGroupIDs[$group]);
  	}
  }
  
  /**
   * Sets the group for the given page
   * 
   *  @param int $pageID - page identifier
   *  @param int $groupID - group identifier
   *  @param boolean $read - read access
   *  @param boolean $read - write access
   *  @return boolean True on success, false on error
   **/
  function setGroupPage ($pageID, $groupID, $read, $write){
  	global $table_prefix, $wpdb;
  	$table = $table_prefix . "ug_GroupsPage";
  	if(!isset($pageID) || !isset($groupID)){
  		return false;
  	}
  	 
  	if (userGroups::tableExists($table)) {
  		$insert = "INSERT INTO ".$table.
                " (id_group,id_page,exc_read,exc_write) ".
                "VALUES ('".$groupID."','".$pageID."','".$read."','".$write."');";

                $results = $wpdb->query( $insert );
                return $results != '';
  	}else{
  		return false;
  	}
  }
  
  
  /**
   * Gets all pages with a collumn with the read and write access
   * 
   * @param int $groupID - group identifier   
   * @return array Query results
   **/     
  function getAllPagesWithGroup($groupId){
  	global $table_prefix, $wpdb;

  	/*wordpress tables*/
  	$table_users = $table_prefix . "users";
  	$table_pages = $table_prefix . "posts"; //post_status == static
  	 
  	/*plugin tables*/
  	$table_groups = $table_prefix . "ug_Groups";
  	$table_groupsPage = $table_prefix . "ug_GroupsPage";
    //added "OR post_type='page'" for wordpress 2.1 compatibility
  	$query = "SELECT * from (Select * from ".$table_pages." p
              LEFT JOIN (".$table_groupsPage." gp )
              ON (gp.id_page = p.ID and gp.id_group='".$groupId."'))t
              WHERE (t.post_status='static' OR t.post_type='page')
              ORDER BY t.post_title;";

  	$results = $wpdb->get_results( $query );

  	return $results;
  }
  
  
  /**
   * Gets all pages with no parent with group information
   * 
   * @param int $groupId - group identifier
   * @return array Query results
   **/    
  function getAllMainPagesWithGroup($groupId){
  	return $this->getAllPagesWithGroupByParent($groupId, 0);
  }
  
  /**
   * Gets all pages with a given parent with group information
   * 
   * @param int $groupId - group identifier
   * @param int $parent - pages parent identifier
   * @return array Query results
   **/    
  function getAllPagesWithGroupByParent($groupId, $parent){
  	global $table_prefix, $wpdb;

  	$table_groupsPage = $table_prefix . "ug_GroupsPage";
    //added "OR post_type='page'" for wordpress 2.1 compatibility
  	$query = "SELECT * from (Select * from ".$wpdb->posts." p
              LEFT JOIN ($table_groupsPage gp )
              ON (gp.id_page = p.ID and gp.id_group='$groupId'))t
              WHERE (t.post_status='static' OR t.post_type='page') AND t.post_parent='$parent'
              ORDER BY t.post_title;";

  	$results = $wpdb->get_results( $query );

  	return $results;
  }
  
  
  
  /**
   * Gets all pages from a given group
   * 
   *  @param int $groupID - group identifier
   *  @param string $criteria - criteria to use in the query   
   **/
  function getGroupPages($groupId, $criteria=""){
  	global $table_prefix, $wpdb;

  	$table_pages = $wpdb->posts; //post_status == static
  	 
  	/*plugin tables*/
  	$table_groups = $table_prefix . "ug_Groups";
  	$table_groupsPage = $table_prefix . "ug_GroupsPage";
    //added "OR p.post_type='page'" for wordpress 2.1 compatibility
  	$query = "Select * from $table_pages p
              INNER JOIN ($table_groupsPage gp )
              ON ((p.post_status='static' OR p.post_type='page') and gp.id_page = p.ID 
                and gp.id_group='$groupId' $criteria) 
              ORDER BY post_title;";

  	$results = $wpdb->get_results( $query );

  	return $results;
  }
  
  /**
   * Gets all pages from a given group with read access
   * 
   * @param int $groupID - group identifier  
   * @return array Query results  
   **/
  function getGroupPagesWithRead($groupId){
  	return $this->getGroupPages($groupId, " and gp.exc_read='1' ");
  }
  
  /**
   * Gets all pages from a given group with write access
   * 
   * @param int $groupID - group identifier     and gp.exc_write='1'
   * @return array Query results
   **/
  function getGroupPagesWithWrite($groupId){
  	return $this->getGroupPages($groupId, " and gp.exc_write='1' ");
  }
  
  
  /**
   * Gets all pages without a group
   * 
   * @param string $criteria - criteria to use in the query
   * @return array Query results
   **/  
  function getGroupFreePages($criteria=""){
  	global $table_prefix, $wpdb;

  	/*wordpress tables*/
  	$table_users = $table_prefix . "users";
  	$table_pages = $table_prefix . "posts"; //post_status == static
  	 
  	/*plugin tables*/
  	$table_groups = $table_prefix . "ug_Groups";
  	$table_groupsPage = $table_prefix . "ug_GroupsPage";
    //added "OR post_type='page'" for wordpress 2.1 compatibility
  	$query = "Select * from $table_pages
              WHERE (post_status='static' OR post_type='page') and ID NOT IN (
              	Select p.id from $table_pages p
              	INNER JOIN ($table_groupsPage gp )
              	ON (gp.id_page = p.ID $criteria )
              ) ORDER BY post_title;";

  	$results = $wpdb->get_results( $query );

  	return $results;
  }
  
  /**
   * Gets all pages without a group with exclusive write
   * @return boolean True if the page has no groups for write, false otherwise.
   **/ 
  function getGroupFreeForWritePages(){
  	return $this->getGroupFreePages("and gp.exc_write='1' ");
  }
  
  /**
   * Gets all pages without a group with exclusive read
   * @return boolean True if the page has no groups for read, false otherwise.
   **/ 
  function getGroupFreeForReadPages(){
  	return $this->getGroupFreePages("and gp.exc_read='1' ");
  }
  
  /**
   *  Checks if a page has no groups
   *  
   *  @param int $id_page - Page Identifier   
   *  @param string $criteria - criteria to use in the query 
   *  @return boolean True if the page has no groups, false otherwise.
   **/     
  function isGroupFreePage($id_page, $criteria = ""){
  	global $table_prefix, $wpdb;

  	/*wordpress tables*/
  	$table_users = $table_prefix . "users";
  	$table_pages = $table_prefix . "posts"; //post_status == static
  	 
  	/*plugin tables*/
  	$table_groups = $table_prefix . "ug_Groups";
  	$table_groupsPage = $table_prefix . "ug_GroupsPage";
    //added "OR post_type='page'" for wordpress 2.1 compatibility
  	$query = "Select * from $table_pages
              WHERE ID=$id_page and (post_status='static' OR post_type='page') and ID NOT IN (
              	Select p.id from $table_pages p
              	INNER JOIN ($table_groupsPage gp )
              	ON (gp.id_page = p.ID $criteria )
              ) ORDER BY post_title;";
  	$results = $wpdb->get_results( $query );
  	 
  	return (!(!isset($results) || count($results) == 0)) && $this->isParentFree($id_page,$criteria);
  }
  
  /**
   *  Checks if a page parent is group free
   *  
   *  @param int $id_page - Page Identifier  
   *  @param string $criteria - criteria to use in the query   
   *  @return boolean True if the page parent has no restriction
   **/ 
  function isParentFree($idChild,$criteria){
  	global $wpdb;

  	$query = "Select post_parent from ".$wpdb->posts."
              WHERE ID=$idChild;";
  	$id_parent = $wpdb->get_var( $query );
  	if($id_parent == 0){
  		return true;
  	}
  	 
  	return $this->isGroupFreePage($id_parent, $criteria);
  }
  
  /**
   *  Checks if a page is free for read
   *  
   *  @param int $id_page - Page Identifier  
   *  @return boolean True if a page is free for read  
   **/     
  function isGroupFreePageForRead($id_page){
  	return $this->isGroupFreePage($id_page, " and (gp.exc_read='1' or gp.exc_write='1' )");
  }
  
  /**
   *  Checks if a page is free for write
   *  
   *  @param int $id_page - Page Identifier
   *  @return boolean True if a page is free for write       
   **/     
  function isGroupFreePageForWrite($id_page){
  	return $this->isGroupFreePage($id_page, " and gp.exc_write='1' ");
  }
  
  /**
   * Checks if a group with a given name exists
   * 
   * @param string $name - Name of the group to test
   * @return boolean True if the group exists, false otherwise.       
   **/     
  function groupExists($name) {
  	global $table_prefix, $wpdb;

  	$table_groups = $table_prefix . "ug_Groups";

  	$query = 'SELECT COUNT(*) FROM '.$table_groups.
             ' WHERE name="'.$name.'";';
             $results = $wpdb->get_var( $query );
             return $results != 0;
  }
  
  /**
   * gets an array of a given group members with their name
   * 
   * @param int $groupID -  Identifier of the group
   * @return array with all members of the group
   **/     
  function getGroupMembers($groupID) {
  	global $table_prefix, $wpdb;

  	$table = $table_prefix . "ug_GroupsUsers";

  	$query = "SELECT gu.id_user as id, u.display_name as name
              FROM $table gu, ".$wpdb->users." u
              WHERE gu.id_group='$groupID' AND gu.id_user=u.ID;";
  	$results = $wpdb->get_results( $query );

  	return $results;
  }
  
  /**
   * gets an array of users with the group id if the user belongs to the group
   * 
   * @param int $groupID - Identifier of the group
   * @return array with all users and and the group passed if they are members.   
   **/     
  function getUsersWithGroup($groupID) {
  	global $table_prefix, $wpdb;

  	$table = $table_prefix . "ug_GroupsUsers";

  	$query = "SELECT gu.id_user as isMember, u.display_name as name , u.ID as id
              FROM ".$wpdb->users." u
              LEFT JOIN $table gu
              ON gu.id_group='$groupID' AND gu.id_user=u.ID;";
  	$results = $wpdb->get_results( $query );

  	return $results;
  }
  
  /**
   * Writes the success/error messages
   * @param string $string - message to be displayed
   * @param boolean $success - boolean that defines if is a success(true) or error(false) message
   **/     
  function write($string, $success=true){
  	if($success){
  		echo '<div id="message" class="updated fade"><p>'.$string.'</p></div>';
  	}else{
  		echo '<div id="message" class="error fade"><p>'.$string.'</p></div>';
  	}
  }
  
  /**
   * Verifies if a group name is valid (for a new group)
   * 
   * @param string $string - Name of the group 
   * @return boolean True if the name is valid, false otherwise.     
   **/     
  function isValidName($string){
  	if($string == "" || $this->groupExists($string)){
  		return false;
  	}
  	return true;
  }
  
  /**
   * Gets all groups ordered by ID
   * 
   * @param boolean $orderByName - if set to true groups will be ordered by name otherwise by ID
   * 
   * @return array groups fetched from the database
   **/     
  function getGroups($orderByName = false) {
  	global $table_prefix, $wpdb;
  	
  	$order = "";
  	if($orderByName){
      //order by Name
      $order = "name";  
    }else{
      //order by ID
      $order = "id";  
    }

  	$table_groups = $table_prefix . "ug_Groups";

  	$query = 'SELECT * FROM '.$table_groups.' ORDER BY '.$order.';';

  	$results = $wpdb->get_results( $query );
  	return $results;
  }
  
/**
   * Gets the number of groups available
   * 
   * @return int number os groups
   **/     
  function getGroupsCount() {
  	global $table_prefix, $wpdb;
  	
  	$table_groups = $table_prefix . "ug_Groups";

  	$query = 'SELECT Count(*) FROM '.$table_groups.';';

  	$result = $wpdb->get_var( $query );
  	return $result;
  }
  
  /**
   * Gets a group with a given identifier
   * 
   * @param int $id - Group Identifier
   * @return Object An object with the group details
   **/     
  function getGroup($id) {
  	global $table_prefix, $wpdb;

  	$table_groups = $table_prefix . "ug_Groups";

  	$query = 'SELECT * FROM '.$table_groups." WHERE id='".$id."';";

  	$results = $wpdb->get_results( $query );
  	if(isset($results) && isset($results[0]))
  	return $results[0];
  }
  
  /**
   * Removes a given group
   * 
   * @param int $id - Identifier of the group to delete
   * @param boolean True if the deletion is successful    
   **/        
  function deleteGroup ($id){
  	global $table_prefix, $wpdb;

  	$table = $table_prefix . "ug_Groups";
  	if($id=="" || $this->getGroup($id) == ""){
  		return false;
  	}
  	 
  	if (userGroups::tableExists($table)) {
  		$delete = "DELETE FROM $table WHERE id='$id';";
      $results = $wpdb->query( $delete );

      $this->deleteAllGroupUser($id);
      $this->deleteAllGroupPages($id);//deletes correspondent relations group-pages
      
      return true;
  	}else{
  		return false;
  	}
  }
  
  /**
   * Checks if a table already exists
   * 
   * @param string $table - table name
   * @return boolean True if the table already exists      
   **/ 
  function tableExists($table){
  	global $wpdb;
  	
  	return strcasecmp($wpdb->get_var("show tables like '$table'"), $table) == 0;
  }
  
  /**
   * Creates a new Group
   * 
   * @param string $name - Name of the group
   * @param string $homepage - Link to the group Homepage (optional) 
   * @return boolean True on successful creation      
   **/     
  function createGroup ($name, $homepage = ''){
  	global $table_prefix, $wpdb;

  	$table = $table_prefix . "ug_Groups";
  	if(!$this->isValidName($name)){
  		return false;
  	}
  	 
  	if (userGroups::tableExists($table)) {
  		$insert = "INSERT INTO $table (name,homepage) VALUES ('$name','$homepage')";

      $results = $wpdb->query( $insert );
      return $results != '';
  	}else{
  		return false;
  	}
  }
  
  /**
   * Updates an existing Group
   * 
   * @param int $groupID - Group identifier   
   * @param string $name - Name of the group
   * @param string $homepage - Link to the group Homepage (optional)
   * @return boolean True on successful update
   **/     
  function updateGroup ($groupID, $name, $homepage = ''){
  	global $table_prefix, $wpdb;

  	$table = $table_prefix . "ug_Groups";

  	$prevName = $wpdb->get_var("SELECT name FROM $table_groups WHERE id='$groupID';");

  	if($prevName != $name && !$this->isValidName($name)){
  		return false;
  	}
  	 
  	if (userGroups::tableExists($table)) {
  		$query = "UPDATE $table SET name = '$name', homepage='$homepage'
                WHERE id='$groupID';";
  		$results = $wpdb->query( $query );
  		return true;
  	}else{
  		return false;
  	}
  }
  
  /**
   * Removes all User-Group relations of a given group
   * 
   * @param int $groupID - Group Identifier       
   **/     
  function deleteAllGroupUser ($groupID){
  	global $table_prefix, $wpdb;

  	$table = $table_prefix . "ug_GroupsUsers";

  	if (userGroups::tableExists($table)) {
  		$delete = "DELETE FROM ".$table.
                " WHERE id_group='".$groupID."';";

                $results = $wpdb->query( $delete );
  	}
  }
  
  /**
   * Removes all Page-Group relations of a given group
   * 
   * @param int $groupID - Group Identifier      
   **/
  function deleteAllGroupPages ($groupID){
  	global $table_prefix, $wpdb;

  	$table = $table_prefix . "ug_GroupsPage";

  	if (userGroups::tableExists($table)) {
  		$delete = "DELETE FROM $table WHERE id_group='$groupID';";
  		$results = $wpdb->query( $delete );
  	}
  }
  
  /**
   * Removes a page from all groups
   * 
   * @param int $pageID - Page Identifier      
   **/
  function deletePageFromGroups ($pageID){
  	global $table_prefix, $wpdb;

  	$table = $table_prefix . "ug_GroupsPage";

  	if (userGroups::tableExists($table)) {
  		$delete = "DELETE FROM $table WHERE id_page='$pageID';";

  		$results = $wpdb->query( $delete );
  	}
  }
  
  /**
   * Removes a User from all groups
   * 
   * @param int $user - User Identifier      
   **/
  function deleteUserFromGroups ($user){
  	global $table_prefix, $wpdb;

  	$table = $table_prefix . "ug_GroupsUsers";

  	if (userGroups::tableExists($table)) {
  		$delete = "DELETE FROM $table WHERE id_user='$user';";

  		$results = $wpdb->query( $delete );
  	}
  }
  
  /**
   * Removes a Page from a group
   * 
   * @param int $groupID - Group Identifier
   * @param int $pageID - Identifier of the Page to remove         
   **/
  function deleteGroupPage ($groupID, $pageID){
  	global $table_prefix, $wpdb;

  	$table = $table_prefix . "ug_GroupsPage";

  	if (userGroups::tableExists($table)) {
  		$delete = "DELETE FROM $table WHERE id_group='$groupID' and id_page='$pageID';";

  		$results = $wpdb->query( $delete );
  		return $results != '';
  	}else{
  		return false;
  	}
  }
  
  
  /**
   * Removes a User from a group
   * 
   * @param int $groupID - Group Identifier
   * @param int $userID - Identifier of the User to remove         
   **/
  function deleteGroupUser ($groupID, $userID){
  	global $table_prefix, $wpdb;

  	$table = $table_prefix . "ug_GroupsUsers";

  	if (userGroups::tableExists($table)) {
  		$delete = "DELETE FROM $table
                WHERE id_group='$groupID' and id_user='$userID';";

  		$results = $wpdb->query( $delete );
  		return $results != '';
  	}else{
  		return false;
  	}
  }
  
  /**
   * Adds a user to a group 
   * @param int $groupID - Group Identifier
   * @param int $userID - Identifier of the User to add 
   **/     
  function createGroupUser ($groupID, $userID){
  	global $table_prefix, $wpdb;

  	$table = $table_prefix . "ug_GroupsUsers";

  	if (userGroups::tableExists($table)) {
  		$insert = "INSERT INTO $table (id_group,id_user)
                VALUES ('$groupID','$userID');";
  		$results = $wpdb->query( $insert );
  	}
  }
  
  /**
   * Sets a group with an array of users
   * 
   * @param int $groupID Group identifier
   * @param array $users Array of users
   **/   
  function createGroupWithUsers ($groupID, $users){
  	global $table_prefix, $wpdb;

  	$table = $table_prefix . "ug_GroupsUsers";

  	if(isset($users) && count($users)>0){
  		foreach($users as $user){
  			if (userGroups::tableExists($table)) {
  				$insert = "INSERT INTO $table (id_group,id_user)
							VALUES ('$groupID','$user');";
  				$results = $wpdb->query( $insert );
  			}
  		}
  	}
  }
  
  /**
   * Gets a list ids of the pages the user has group access or is the author
   *    
   * @param int $userID - User identifier 
   * @param string $criteria - criteria to be added to the query WHERE statement  
   * @return array Array Objects with the page ids
   **/
  function getGroupPagesWithUser($userID, $criteria){
  	global $table_prefix, $wpdb;
  	$table_posts = $table_prefix . "posts";
  	$table_groupsUsers = $table_prefix . "ug_GroupsUsers";
  	$table_groupsPages = $table_prefix . "ug_GroupsPage";
    //added "OR post_type='page'" for wordpress 2.1 compatibility
  	$query = "SELECT DISTINCT gp.id_page as ID
              FROM $table_groupsPages gp,
            			(SELECT id_group FROM $table_groupsUsers 
            			WHERE id_user='$userID') gu
        			WHERE gp.id_group = gu.id_group $criteria 
              UNION
              SELECT ID FROM $table_posts
              WHERE post_author='$userID' and (post_status='static' OR post_type='page');";

  	$results = $wpdb->get_results( $query );

  	return $results;
  }
  
  /**
   * Gets a list ids of the pages the user has group access for read or is the author
   *    
   * @param int $userID - User identifier   
   * @return array Array Objects with the page ids;
   **/
  function getGroupPagesWithUserByRead($userID){
  	return $this->getGroupPagesWithUser($userID, " and gu.exc_read='1' ");
  }
  
  /**
   * Gets a list ids of the pages the user has group access for write or is the author
   *    
   * @param int $userID - User identifier   
   * @return array Array Objects with the page ids;
   **/
  function getGroupPagesWithUserByWrite($userID){
  	return $this->getGroupPagesWithUser($userID, " and gu.exc_write='1' ");
  }

  /**
   * Checks if a user has access to a page
   * 
   * @param int $postID - Page identifier
   * @return boolean True if user has access, false otherwise.      
   **/     
  function hasPageAccess($postID){
  	global $wpdb, $user_level, $wp_query, $user_ID;

  	get_currentuserinfo();
  	//if is an administrator or the page has no groups
  	if ($user_level >= 8 || $this->isGroupFreePage($postID)){ return true; }
  	
  	// is user already logged in?
  	if ('' != $user_ID) {
  		$pages =  $this->getGroupPagesWithUser($user_ID);
  		$access = false;
  		// is this page one of the allowed pages
  		//TODO consider changing to a direct query...
  		if(isset($pages))
  		foreach ($pages as $p) {
  			if($postID == $p->ID) {
  				//found a page
  				return true;
  			}
  		}
  	}
  	return false;
  }
  
  /**
   * Checks if a user has read access to a page
   * 
   * @param int $postID - Page identifier   
   * @return boolean True if user has read access, false otherwise.         
   **/     
  function hasReadPageAccess($postID){
  	return $this->hasPageAccessByCriteria($postID, "exc_read");
  }
  
  /**
   * Checks if a user has write access to a page
   * 
   * @param int $postID - Page identifier   
   * @return boolean True if user has read access, false otherwise.       
   **/     
  function hasWritePageAccess($postID){
  	return $this->hasPageAccessByCriteria($postID, "exc_write");
  }
  
  /**
   * Checks if a user has access to a page based on a criteria
   * 
   * @param int $postID - Page identifier
   * @param string $criteria Criteria to use in the search (exc_write or exc_read)
   * @return boolean True if user has access, false otherwise.       
   **/     
  function hasPageAccessByCriteria($postID, $criteria){
  	global $wpdb, $user_level, $wp_query, $user_ID, $table_prefix;

  	get_currentuserinfo();

  	$temp = "";
  	if ($criteria != "") {
  		$temp = " and gp.$criteria='1' ";
  	}
  	 
  	//if is an administrator or the page has no groups
  	if ($user_level >= 8 || $this->isGroupFreePage($postID, $temp)){
  		return true;
  	}
  	
  	// is user already logged in?
  	if ('' != $user_ID && $criteria != "") {
  		$table_groupsPages = $table_prefix . "ug_GroupsPage";
  		$table_groupsUsers = $table_prefix . "ug_GroupsUsers";
  		$table_groups = $table_prefix . "ug_Groups";

  		$query = "SELECT gp.$criteria FROM $table_groupsPages gp, $table_groups g,
               ".$wpdb->posts." p, $table_groupsUsers gu
                WHERE p.id = '$postID' AND p.id=gp.id_page AND gp.id_group=g.id
                  AND g.id=gu.id_group AND gu.id_user='$user_ID';";
  		$results = $wpdb->get_var( $query );

  		if($results==1){
  			return true;
  		}
  	}
  	return false;
  }
  
  /**
   * Gets a list of pages to exclude from the menus
   * @param boolean $forEdit if set to true will search for pages with edit access restriction
   * @return string Page ids separated by commas
   */
  function getPagesToExclude($forEdit = false){
  	global $wpdb, $user_level, $wp_query, $user_ID, $table_prefix;

  	get_currentuserinfo();

  	//if is an administrator or the page has no groups
  	if ($user_level >= 8){
  		return "";
  	}
  	
  	$accessType = "exc_read = '1'";
  	if($forEdit){
  		$accessType = "exc_write = '1'";
  	}
  	
  	// is user already logged in?
  	 
  	$table_groupsPages = $table_prefix . "ug_GroupsPage";
  	$table_groupsUsers = $table_prefix . "ug_GroupsUsers";
  	$table_groups = $table_prefix . "ug_Groups";

  	if($user_ID!= ""){
  	  //added "OR p.post_type='page'" for wordpress 2.1 compatibility
  		$query = "SELECT DISTINCT id_page
                  FROM $table_groupsPages 
                  WHERE exc_read = '1'
                    AND id_page NOT IN
                      (SELECT gp.id_page 
                        FROM $table_groupsPages gp, $table_groups g, 
                            ".$wpdb->posts." p, $table_groupsUsers gu
                        WHERE (p.post_status='static' OR p.post_type='page') 
                              AND p.id=gp.id_page 
                              AND gp.id_group=g.id
                              AND g.id=gu.id_group 
                              AND gu.id_user='$user_ID'
                              AND gp.$accessType);";
  	}else{
  		$query = "SELECT DISTINCT gp.id_page
                FROM $table_groupsPages gp 
                WHERE gp.$accessType;";
  	}

  	$results = $wpdb->get_results( $query );

    
  	$exclude ="";
  	if(isset($results) && $results!="" && count($results)>0){

  		$first = true;
  		foreach ($results as $page){
  			if(!$first)
  			$exclude .= ",";
  			else
  			$first = false;
  			$exclude .= $page->id_page;
  		}
  	}
  	return $exclude;

  }
  
  
  /**
   * Tests if the user can view the page content. If not, the content is blocked.
   * @return string The filtered content
   **/     
  function testContent($content){
  	global $post, $user_ID, $ug_contentBlocked;

  	//if:
  	// the post is a page
  	// the user has read access
  	if(($post->post_status == "static" /* for wordpress < 2.1 */
      || $post->post_type == "page") /* for wordpress < 2.1 */
      && !$this->hasReadPageAccess($post->ID)){//nao tem acesso
  		$content = "<h2>You don't have access to this content</h2>";
  	}
  	return $content;
  }
  
  
  
  /**********************************/
  /**** Plugin Support Methods ******/
  /**********************************/
  
  /**
   * Gets the groups with a certain permission and/or resource
   * To be used by other plugins
   * 
   * @param string $plugin_name - name of the plugin using this method
   * @param int $resource - resource identification
   * @param string $permission - name of the permission (if none is 
   * 		specified all plugin resources with all permissions are returned)
   * @return array The results or null if an error occurs; 
   **/     
  function ugGetGroupsOfPlugin($plugin_name, $resource="", $permission=""){
  	global $table_prefix, $wpdb;

  	$table_groups = $table_prefix . "ug_Groups";
  	$table_groupsGeneric = $table_prefix . "ug_GroupsGeneric";
	
  	$arg= "";
  	if($permission != ""){
  		$arg .= "AND tgg.permission='$permission' ";
  	}
  	if($resource != ""){
  		$arg .= "AND tgg.id_resource='$resource' ";
  	}
  	
  	if($plugin_name != ""){
  		$query = "SELECT * 
              FROM $table_groups tg, $table_groupsGeneric tgg 
              WHERE tgg.plugin_name = '$plugin_name' AND tg.id = tgg.id_group 
              $arg ORDER BY tg.name;";

  		return $wpdb->get_results( $query );
  	}else{
  		return null;
  	}
  }
  
/**
   * Gets the resources a user has not access
   * To be used by other plugins
   * 
   * @param int $user_id - User identifier
   * @param string $plugin_name - name of the plugin using this method
   * @param string $permission - name of the permission (if none is 
   * 		specified this restriction is ignored)
   * @return array Restricted resources IDs in array
   **/     
  function ugGetRestrictedResources($user_id, $plugin_name, $permission=""){
  	global $table_prefix, $wpdb, $user_level;
	
  	$excludes = array();
  	
  	get_currentuserinfo();
  	
  	//if is an administrator he has access to all resources
  	if ($user_level >= 8){
  		return $excludes;
  	}
  	
  	$table_groups = $table_prefix . "ug_Groups";
  	$table_groupsUsers = $table_prefix . "ug_GroupsUsers";
  	$table_groupsGeneric = $table_prefix . "ug_GroupsGeneric";
	
  	$arg= "";
  	if($plugin_name == ""){
  		return array();
  	}
  	
  	if($resource != ""){
  		$arg .= "AND gg.id_resource='$resource' ";
  	}

  	if($user_id!= ""){
  		$query = "SELECT DISTINCT id_resource
                  FROM $table_groupsGeneric 
                  WHERE id_resource NOT IN
                      (SELECT gg.id_resource 
                        FROM $table_groupsGeneric gg, $table_groups g, 
                             $table_groupsUsers gu
                        WHERE gg.id_group=g.id 
                              AND gg.plugin_name = '$plugin_name' 
							  AND g.id=gu.id_group 
                              AND gu.id_user='$user_id' 
                              $arg);";
  	}else{
  		$query = "SELECT DISTINCT id_resource
                FROM $table_groupsGeneric gg 
                WHERE gg.plugin_name = '$plugin_name' $arg;";
  	}
  	

  	$results = $wpdb->get_results( $query );
	
  	if(isset($results) && $results!="" && count($results)>0){
  		foreach ($results as $result){
	  		$excludes[] = $result->id_resource;
  		}
  	}
  	return $excludes;
  	
  }
  
  /**
   * Checks if the user has privileges to access a content
   * To be used by other plugins
   * 
   * @param int $user - ID of the user
   * @param int $resource - ID of the resource
   * @param string $permission - name of the permission 
   * @param string $plugin_name - name of the plugin using this method
   * @return boolean Returns true is the user has access or no access for the resource is specified, false otherwise    
   **/     
  function ugHasAccess($user, $resource, $permission, $plugin_name){
  	global $table_prefix, $wpdb, $user_level;
  	
  	if ($user_level >= 8){
  		return true;
  	}

  	$table_groups = $table_prefix . "ug_Groups";
  	$table_groupsGeneric = $table_prefix . "ug_GroupsGeneric";
    $table_groupsUsers = $table_prefix . "ug_GroupsUsers";
  	if($permission != "" && $resource != "" && $plugin_name != ""){
  		
  		$query = "SELECT COUNT(*) FROM $table_groupsGeneric tgg
                  WHERE tgg.plugin_name = '$plugin_name' AND tgg.id_resource = '$resource';";
  		$results = $wpdb->get_var( $query );
  		if($results != "" && $results == 0)
  			return true; //if this happens means that the resource has no groups locking it
  		
	  	if($user==""){//user is not logged in and the resourse has groups
	  		return false;
	  	}
	  	
  		$query = "SELECT COUNT(*) FROM $table_groupsGeneric tgg, $table_groups tg,
                $table_groupsUsers tgu
                WHERE tgg.plugin_name = '$plugin_name' AND tgg.id_resource = '$resource' AND tgg.id_group=tg.id
                  AND tg.id=tgu.id_group AND tgu.id_user='$user';";
  		$results = $wpdb->get_var( $query );

  		return $results != "" && $results > 0;
  	}else{
  		return false;
  	}
  }
  
  /**
   * Gets the user access to a given resource
   * To be used by other plugins
   * 
   * @param int $user - ID of the user
   * @param int $resource - ID of the resource
   * @param string $permission - name of the permission 
   * @param string $plugin_name - name of the plugin using this method
   * @return array The row with the access to the resource (Contains description if available)    
   **/     
  function ugGetUserAccess($user, $resource, $permission, $plugin_name){
  	global $table_prefix, $wpdb;

  	$table_groups = $table_prefix . "ug_Groups";
  	$table_groupsGeneric = $table_prefix . "ug_GroupsGeneric";

  	if($permission != "" && $resource != "" && $user!="" && $plugin_name != ""){
  		$query = "SELECT tgg.* FROM $table_groupsGeneric tgg, $table_groups tg,
                $table_groupsUsers tgu
                WHERE tgg.plugin_name = '$plugin_name' AND tgg.id_resource = '$resource' AND tgg.id_group=tg.id
                  AND tg.id=tgu.id_group AND tgu.id_user='$user';";
  		$results = $wpdb->get_results( $query );
  		return $results;
  	}else{
  		return array();
  	}
  }
  
  /**
   * Sets the group access for a given resource
   * To be used by other plugins
   * @param int $group - Group identifier
   * @param int $resource - resource identifier
   * @param string $permission - permission name
   * @param string $plugin_name - name of the plugin calling this method
   * @param string $description - description of the restriction
   * @return boolean True after successful insert, false otherwise
   **/
  function ugSetAccess ($group, $resource, $permission, $plugin_name, $description){
  	global $table_prefix, $wpdb;
  	$table = $table_prefix . "ug_GroupsGeneric";
  	
  	if(!isset($resource) || !isset($group) || !isset($permission) || !isset($plugin_name)){
  		return false;
  	}
  	 
  	if (userGroups::tableExists($table)) {
  		$insert = "INSERT INTO ".$table.
                " (id_group,id_resource,permission,plugin_name, description) ".
                "VALUES ('$group','$resource','$permission','$plugin_name','$description');";

        $results = $wpdb->query( $insert );
        return $results != '';
  	}else{
  		return false;
  	}
  }
  
  /**
   * Sets the group access for a given resource
   * To be used by other plugins
   * @param array $groupsList - Array of groups identifiers (int[])
   * @param int $resource - resource identifier
   * @param string $permission - permission name
   * @param string $plugin_name - name of the plugin calling this method
   * @param string $description - description of the restriction
   * @return boolean True if every group access was set, false if one or more have failed
   **/
  function ugSetGroupsAccess ($groupsList, $resource, $permission, $plugin_name, $description){
  	$retVal = true;
    userGroups::ugDeleteResource ($plugin_name, $resource, $permission);
    foreach($groupsList as $group){
      $bol = userGroups::ugSetAccess ($group, $resource, $permission, $plugin_name, $description);
      $retVal = $bol && $retVal;
    }
    return $retVal;
  }
  
  /**
   * Deletes the group access for the given resource
   * To be used by other plugins
   * @param int $group - Group identifier
   * @param int $resource - resource identifier (optional parameter, if not specified this creteria will not be considered)
   * @param string $permission - permission name (optional parameter, if not specified this creteria will not be considered)
   * @param string $plugin_name - name of the plugin calling this method
   * @return boolean True if the item has been deleted, false otherwise
   **/
  function ugDeleteAccess ($group, $plugin_name, $resource="" ,$permission=""){
  	global $table_prefix, $wpdb;
  	$table = $table_prefix . "ug_GroupsGeneric";
  	
  	if(!isset($resource) || !isset($group) || !isset($plugin_name)){
  		return false;
  	}
  	
  	$arg= "";
  	if($permission != ""){
  		$arg .= "AND permission='$permission' ";
  	}
	if($resource != ""){
  		$arg .= "AND id_resource='$resource' ";
  	}
  	 
  	if (userGroups::tableExists($table)) {
  		$delete = "DELETE FROM $table WHERE id_group='$group'
					AND plugin_name='$plugin_name' $arg;";

      $results = $wpdb->query( $delete );
      return $results != '';
  	}else{
  		return false;
  	}
  }
  
  /**
   * Deletes refereces to a resource
   * To be used by other plugins
   *
   * @param string $plugin_name - name of the plugin calling this method
   * @param int $resource - resource identifier (optional if not specified all resources from this plugin will be deleted)
   * @param string $permission - permission of the resource (optional if not specified resources with all permissions this plugin will be deleted)   
   * @return boolean True if the item has been deleted, false otherwise
   **/
  function ugDeleteResource ($plugin_name, $resource="", $permission=""){
  	global $table_prefix, $wpdb;
  	$table = $table_prefix . "ug_GroupsGeneric";
  	
  	if(!isset($plugin_name)){
  		return false;
  	}
  	
  	$arg= "";
  	if($resource != ""){
  		$arg .= "AND id_resource='$resource' ";
  	}
  	if($permission != ""){
  		$arg .= "AND permission='$permission' ";
  	}
  	 
  	if (userGroups::tableExists($table)) {
  		$delete = "DELETE FROM $table WHERE plugin_name='$plugin_name' $arg;";

      $results = $wpdb->query( $delete );
      return $results != '';
  	}else{
  		return false;
  	}
  }
  
  
  /**
   * Gets the Group with the given Name
   * To be used by other plugins
   *
   * @param string $name - Name of the Group
   * @return array Groups with the name provided (should be allways a single result)
   **/
  function ugGetGroupByName($name) {
  	global $table_prefix, $wpdb;

  	$table_groups = $table_prefix . "ug_Groups";

  	$query = 'SELECT * FROM '.$table_groups.
             ' WHERE name="'.$name.'";';
             $results = $wpdb->get_results( $query );
             return $results;
  }
}

$userGroups = new userGroups();

?>
