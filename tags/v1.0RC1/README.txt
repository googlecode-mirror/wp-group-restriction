---=[ Index ]=------------------------------------------------------------------
1 - General Information
2 - License
3 - Installation
4 - Configuration
5 - Use this plugin in other wordpress plugins


---=[ 1 - General Information ]=------------------------------------------------

Version: 1.0 RC1
Date: 25/01/2006
Author: Tiago Pocinho, Siemens Networks, S.A.

This plugin is still under development.

Currently it supports filtering pages by groups, assigning users and pages to groups.

Support for other plugins is also available.

---=[ 2- Licence ]=-------------------------------------------------------------

This plugin is protected under the GNU General Public License.

---=[ 3 - Installation ]=-------------------------------------------------------

3.1) Simple Instalation
_______________________

After downloading this plugin, extract the directory "wp-group-restriction".

Go to the wordpress back-office and activate the plugin. This will create the needed tables in the database.


3.2) Advanced Instalation
_________________________


To filter out access from the front office, update your theme, where you use the function wp_list_pages, to call: 
  if(class_exists("userGroups")){
    $groups = new userGroups();
    $pagesToExclude = userGroups::getPagesToExclude();
  }
and add the returning value to 
	wp_list_pages("exclude=" . $pagesToExclude . "&sort_column=menu_order&depth=1&title_li="); ?>
This allows to hide pages the user has no reading access.



If your wordpress version is prior to 2.1, you can use the following hack to prevent pages from beeing displayed in the backoffice replace:


+-wordpress\wp-admin\edit-pages.php Line 28------------------------------------------------+                                                                                          
else  
   $posts = $wpdb->get_results("SELECT * FROM $wpdb->posts WHERE post_status = 'static'");
                                                                                          
+------------------------------------------------------------------------------------------+


by 

+-wordpress\wp-admin\edit-pages.php Line 28------------------------------------------------+

else {
    /*plugin tables*/
  $table_groupsPage = $table_prefix . "ug_GroupsPage";
  $table_groups = $table_prefix . "ug_Groups";
  $table_groupsUsers = $table_prefix . "ug_GroupsUsers";
  
  $query = "SELECT * FROM $table_groupsUsers tgu
              WHERE tgu.id_user='$user_ID';";
              
  $results = $wpdb->get_results( $query );
  
  $stringGrupos;
  $cont = 0;
  if(isset($results))
  foreach ($results as $res){
    if($cont > 0){
      $stringGrupos .= " OR ";
    }
    $stringGrupos .= "gp.id_group='$res->id_group' ";
    $cont ++;
  }
  if($cont > 0){
    $stringGrupos ="SELECT p.* from ".$wpdb->posts." p, $table_groupsPage gp
              WHERE p.ID=gp.id_page and ( $stringGrupos )
              UNION
              ";
    
  }
  //if the user is not an administrator or if the table does not exist
  if($user_level < 8 && $wpdb->get_var("show tables like '$table_groupsPage'") != $table_groupsPage){ 
    
    $query = "$stringGrupos
              SELECT * from wp_posts
              WHERE post_status='static' and (ID NOT IN (
              	SELECT DISTINCT p.id from ".$wpdb->posts." p
              	INNER JOIN ($table_groupsPage gp )
              	ON (gp.id_page = p.ID and gp.exc_write='1')
              ) or post_author=$user_ID) ORDER BY post_title;";
  }else{
    //default query
    $query ="SELECT * FROM $wpdb->posts WHERE post_status = 'static'";
  
  }
  //$query ="SELECT * FROM $wpdb->posts WHERE post_status = 'static'";
  $posts = $wpdb->get_results($query);
}

+------------------------------------------------------------------------------------------+

and 

+-wordpress\wp-admin\edit-pages.php around line 60 ----------------------------------------+                                                                                          
  if ( isset($_GET['s']) ) {
	...
     endforeach;
  } else {
                                                                                          
+------------------------------------------------------------------------------------------+

replace "page_rows();" by "page_rows(0,0,$posts);" getting something like

+-wordpress\wp-admin\edit-pages.php around line 68 ----------------------------------------+                                                                                          
  if ( isset($_GET['s']) ) {
	...
     endforeach;
  } else {
     page_rows(0,0,$posts);
  }
                                                                                          
+------------------------------------------------------------------------------------------+

---=[ 4 - Configuration]=-------------------------------------------------------

4.1) Group Management
_____________________

Access "Groups" from the main menu in order to manage the groups:
* "Groups > Groups" - to manage Existing Groups
* "Groups > Pages Access" - to add and remove pages from groups
* "Groups > Access per Page" - to add and remove groups from pages
* "Groups > Members" - to add and remove users from groups 

Note: Free Access Pages are pages without a group.

4.2) User Management
____________________

Access "Users > Authors & Users" and edit a user to add or remove users from a group.

4.3) Page Edit
______________

While editing or creating a page, the user can choose the groups with access to the page, by checking/unchecking the groups after the page edit panel.

Keep in mind that both the administrators and the author can read the page.


---=[ 5 - Use this plugin in other wordpress plugins]=--------------------------

  /**
   * Gets the groups with a certain permission and/or resource
   * To be used by other plugins
   * 
   * @param $plugin_name - name of the plugin using this method
   * @param $resource - ID of the resource  
   * @param $permission - name of the permission (if none is 
   * 		specified all plugin resources with all permissions are returned) 
   **/     
  function ugGetGroupsOfPlugin($plugin_name, $resource , $permission);
  
  /**
   * Checks if the user has privileges to access a content
   * To be used by other plugins
   * 
   * @param $user - ID of the user
   * @param $resource - ID of the resource
   * @param $permission - name of the permission 
   * @param $plugin_name - name of the plugin using this method
   * 
   * @return returns true is the user has access or no access for the resource is specified, false otherwise    
   **/     
  function ugHasAccess($user, $resource, $permission, $plugin_name);
  
  /**
   * Gets the user access to a given resource
   * To be used by other plugins
   * 
   * @param $user - ID of the user
   * @param $resource - ID of the resource
   * @param $permission - name of the permission 
   * @param $plugin_name - name of the plugin using this method
   * 
   * @return The row with the access to the resource (Contains description if available)    
   **/     
  function ugGetUserAccess($user, $resource, $permission, $plugin_name);
  
  /**
   * Sets the group access for a given resource
   * To be used by other plugins
   * @param $group - Group identifier
   * @param $resource - resource identifier
   * @param $permission - permission name
   * @param $plugin_name - name of the plugin calling this method
   * @param $description - description of the restriction
   * 
   * @return true after successful insert, false otherwise
   **/
  function ugSetAccess ($group, $resource, $permission, $plugin_name, $description);
  
  /**
   * Sets the group access for a given resource
   * To be used by other plugins
   * @param $groupsList - List of groups identifiers
   * @param $resource - resource identifier
   * @param $permission - permission name
   * @param $plugin_name - name of the plugin calling this method
   * @param $description - description of the restriction
   **/
  function ugSetGroupsAccess ($groupsList, $resource, $permission, $plugin_name, $description);
  
  /**
   * Deletes the group access for the given resource
   * To be used by other plugins
   * @param $group - Group identifier
   * @param $resource - resource identifier (optional parameter, if not specified this creteria will not be considered)
   * @param $permission - permission name (optional parameter, if not specified this creteria will not be considered)
   * @param $plugin_name - name of the plugin calling this method
   * 
   * @return true if the item has been deleted, false otherwise
   **/
  function ugDeleteAccess ($group, $plugin_name, $resource , $permission);
  
  /**
   * Deletes refereces to a resource
   * To be used by other plugins
   *
   * @param $plugin_name - name of the plugin calling this method
   * @param $resource - resource identifier (optional if not specified all resources from this plugin will be deleted)
   * @param $permission - permission of the resource (optional if not specified resources with all permissions this plugin will be deleted)   
   * 
   * @return true if the item has been deleted, false otherwise
   **/
  function ugDeleteResource ($plugin_name, $resource , $permission);
  
  
  /**
   * Gets the Group with the given Name
   * To be used by other plugins
   *
   * @param $name - Name of the Group
   * @return Array of groups with the name provided (should be allways a single result)
   **/
  function ugGetGroupByName($name);
