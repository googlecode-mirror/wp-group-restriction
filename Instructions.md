## Installation ##


### Simple Installation ###

After downloading this plugin, extract the directory "wp-group-restriction".

Go to the Wordpress back-office and activate the plugin. This will create the needed tables in the database.

### Advanced Installation ###


To filter out access from the front office, update your theme to call:
```
  if(class_exists("userGroups")){
    $pagesToExclude = userGroups::getPagesToExclude();
  }
```
and add the returning value to
```
wp_list_pages("exclude=" . $pagesToExclude . "&sort_column=menu_order&depth=1&title_li=");
```
This allows to hide pages the user has no reading access. Notice that this code should be between PHP tags: `<?php` and `?>`.

#### For Wordpress < 2.1 ####
For Wordpress versions **prior to 2.1**, a hack is needed to filter out the pages the current user can edit from the pages list (Manage Pages) in the backoffice.
In order to do so, replace the line:

**wordpress\wp-admin\edit-pages.php Line 27**
```
else
  $posts = $wpdb->get_results("SELECT * FROM $wpdb->posts WHERE post_status = 'static'");
```

by

```
else{
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
```

and

**wordpress\wp-admin\edit-pages.php around line 60**
```
  if ( isset($_GET['s']) ) {
	...
     endforeach;
  } else {
```
replace `page_rows();` by `page_rows(0,0,$posts);` getting something like

```
  if ( isset($_GET['s']) ) {
	...
     endforeach;
  } else {
     page_rows(0,0,$posts);
  }
```

## Configuration ##

### Group Management ###

Access "Groups" from the main menu in order to manage the groups:
  * "Groups > Groups" - to manage Existing Groups
  * "Groups > Groups Access" - to add and remove pages from groups
  * "Groups > Pages Access" - to add and remove groups from pages
  * "Groups > Members" - to add and remove users from groups

**Note:** "Pages with free access" are pages without a group.

### User Management ###
Access "Users > Authors & Users" and edit a user to add or remove users from a group.

### Page Edit ###

While editing or creating a page, the user can choose the groups with access to the page, by checking/unchecking the groups after the page edit panel.

Keep in mind that administrators can always read and write pages even if restricted.