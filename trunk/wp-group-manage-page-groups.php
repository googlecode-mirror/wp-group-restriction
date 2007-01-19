<?php
$groups = new userGroups();

$mode = $_REQUEST['mode'];

if($mode == "update"){
  $groups->write("Groups access to '".get_the_title($_REQUEST['id'])."' updated");
}


if($_REQUEST['id'] == "" && $mode == "edit"){
  $groups->write("Error: invalid arguments...");
}

?>

<div class="wrap">
<?php 

//prints a page with its groups and then prints the same for its children
function userGroups_PrintPagesWithGroups($level=0, $parentID=0, $alt=true){
  $groups = new userGroups();
  global $wpdb;
  
  
  $results = $wpdb->get_results("SELECT * FROM ".$wpdb->posts. 
                  " WHERE post_status='static' AND post_parent='$parentID';");
  
  
  if(isset($results))
    foreach ($results as $result) {
      if($alt) {
      	$style = 'class=\'alternate\'';
      }  else {
      	$style = '';
      }
      
      $spacer = "";
      for($cont = 0; $cont < $level; $cont++){
        $spacer .= "-";
      }
      
      echo "<tr ".$style."><td>$spacer ".$result->post_title."</td><td>";
      $pageGroups = $groups->getAllGroupsWithPage($result->ID);
      
      if(isset($pageGroups))
      foreach ($pageGroups as $grp) {
        if($grp->exc_read || $grp->exc_write){
          $perms = " (";
          if($grp->exc_read){
            $perms .= "R";
          }
          if($grp->exc_write){
            $perms .= "W";
          }
          $perms .= ")";
          
            
          echo "- ".$grp->name. "$perms<br />";
        } 
      }
      echo "</td><td ".$style."><a class=\"edit\"  href='".$_SERVER['PHP_SELF'].
        "?page=wp-group-restriction/manage_groups&amp;mode=edit&amp;id="
        .$result->ID."'>Edit</a></td></tr>";
      ?>
    
      
      <?php 
      $alt = !$alt;
      userGroups_PrintPagesWithGroups($level + 1, $result->ID, &$alt);
	 }
}

switch($mode){
  case "update":
    //colocar p�ginas com o id na lista na tabela de rela��o
    //retirar as outras
    
    //merge the two access arrays
    $readable = array();
    $writeable = array();
    $groupsList = array();
    
    if(isset($_POST['groups_read']))
      foreach($_POST['groups_read'] as $id){
        $readable[$id]=1;
        $writeable[$id]=0;
        $groupsList[] =$id;
      }
    if(isset($_POST['groups_write']))
      foreach($_POST['groups_write'] as $id){
        if(!isset($readable[$id])){
          $readable[$id]=0;
          $groupsList[] =$id;
        }
        $writeable[$id]=1;
      }
    $groups->setPageGroups($groupsList,$readable,$writeable,$_REQUEST['id']);
  case "edit":
    if(isset($_REQUEST['id'])){
      $pageID = $_REQUEST['id'];
      
      
    
      
      echo "<h2>Edit Pages Access for '".get_the_title($pageID)."'</h2>";
       
      echo '<form id="readWrite" name="readWrite" action="'.$_SERVER['PHP_SELF'].'?page=wp-group-restriction/manage_groups&amp;mode=update&amp;id='.$pageID.'" method="post">';
      echo '<script type="text/javascript"><!--
      
      function select_all(name, value) {
        formblock = document.getElementById("readWrite");
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
      $pageGroups = $groups->getAllGroupsWithPage($pageID);
      
      
      if(isset($pageGroups)){
        echo "<table id='the-list-x' width='100%' cellpadding='3' cellspacing='3'>";
        echo "<tr>";
        echo "<th scope='col' rowspan='2'>Page</th>";
        echo "<th scope='col' colspan='3'>Exclusive</th>";
        echo "</tr>";
        echo "<tr>";
        echo "<th scope='col' style='width:7em'>Read</th>";
        echo "<th scope='col' style='width:7em'>Write</th>";
        echo "</tr>";
        $alt = true;
        
        //print groups!!!
        
        foreach($pageGroups as $group){
          if($alt) {
          	$style = 'class=\'alternate\'';
          }  else {
          	$style = '';
          }
          $alt = !$alt;
          
          echo "<tr $style>";
          echo "<td>".$group->name."</td>";
          $checked = "";
          
          if($group->exc_read)
            $checked = " checked ";
          echo "<td  style='text-align:center;'><input type='checkbox' name='groups_read[]' value='".$group->id."' $checked/></td>";
          $checked = "";
          if($group->exc_write)
            $checked = " checked ";
          echo "<td  style='text-align:center;'><input type='checkbox' name='groups_write[]' value='".$group->id."' $checked/></td>";
          echo "</tr>";
        }
        
        echo "<tr>";
        echo "<td>&nbsp;</td>";
        echo "<td scope='col' style='text-align:center;'>".
        "<a href='#' onclick='select_all(\"groups_read\", true);'>All</a>".
        " / <a href='#' onclick='select_all(\"groups_read\", false);'>None</a></td>";
        echo "<td scope='col' style='text-align:center;'>".
        "<a href='#' onclick='select_all(\"groups_write\", true);'>All</a>".
        " / <a href='#' onclick='select_all(\"groups_write\", false);'>None</a></td>";
        //echo "<td><input type='checkbox' id='allRead' onclick='select_all(\"groups_read\", this.checked)'/><label for='allRead'>All/None</label></td>";
        //echo "<td><input type='checkbox' id='allWrite' onclick='select_all(\"groups_write\", this.checked)'/><label for='allWrite'>All/None</label></td>";
        echo "</tr>";
        echo "</table>";
        echo "<hr /><b>Note:</b> If a page has exclusive read, only users with no group or belonging ";
        echo "to a group with read access will be able to read the pages.<br />";
        echo "The same concept applies to exclusive write. However, if a page is only locked to ";
        echo "write, others can still read it.";
      }
      ?>
      <br />
      <div class="submit"><input  type="submit" value="Update &raquo;" /></div>
     </form>
      
      <?php
    }

  
  
    break;
  default:
?>

<h2><?php _e('Pages detailed access'); ?></h2>
<table width="100%"  border="0" cellspacing="3" cellpadding="3">
	<tr>
		<th><?php _e('Page Title'); ?></th>
		<th><?php _e('Groups'); ?></th>
    <th>&nbsp;</th>
	</tr>
	
	<?php
    
    userGroups_PrintPagesWithGroups();
    
      ?>

  </table>
<?php
}
?>
</div>