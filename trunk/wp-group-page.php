<?php
/*************************************************************
 * This File loads the "Groups -> Groups" Tab
 * It allows to manage Groups by editing, adding or deleting
 ************************************************************/ 
  
$groups = new userGroups();

$mode = $_REQUEST['mode'];

switch($mode){
  case "add":
    if(!$groups->isValidName($_POST['groupName'])){
      if($_POST['groupName'] == ""){
        $groups->write("Please specify a name for the group.", false);
      }else{
        $groups->write("A group with the name ".$_POST['groupName']." already exists !", false);
      }
    }else{
      if($groups->createGroup ($_POST['groupName'], $_POST['groupPage'])){
        $groups->write("Group <b>".$_POST['groupName']."</b> created successfuly");
      }
    }
    break;
  case "edit":
    $id = $_REQUEST['id'];
    $group = $groups->getGroup($id);
    $group->prev_name = $group->name;
    break;
  case "editSubmit":
    //to continue edit
    $group->name = $_POST['groupName'];
    $group->prev_name = $_POST['prevName'];
    $group->id = $_POST['groupID'];
    $group->homepage = $_POST['groupPage'];
    
    if(!$groups->isValidName($_POST['groupName']) && $_POST['groupName'] != $_POST['prevName']){
      if($_POST['groupName'] == ""){
        $groups->write("Please specify a name for the group.", false);
        $mode = "edit";
      }else{
        $groups->write("A group with the name ".$_POST['groupName']." already exists !", false);
        $mode = "edit";
      }
    }else{
      if($groups->updateGroup ($_POST['groupID'], $_POST['groupName'], $_POST['groupPage'])){
        $groups->write("Group <b>".$_POST['groupName']."</b> updated successfuly");
        $group = "";
      }else{
        $groups->write("Group <b>".$_POST['prevName']."</b> was not updated successfuly",false);
        $mode = "edit";
      }
    }
    break;
  case "delete":
    $idDel = $_REQUEST['id'];
    if($idDel != ""){
      if($groups->deleteGroup($idDel)){
        $groups->write("Group Deleted !");
      }else{
        $groups->write("Invalid group",false);
      }
      $_REQUEST['id'] = "";
    }  
  	break;
  default:
    break;
}
?>
<div class="wrap">
<h2><?php _e('Existing Groups'); 

if($mode!="edit"){?> (<a href="#new">add new</a>)<?php } ?></h2>
<?php
    $groups = new userGroups();
    
    $results = $groups->getGroups(true);
    
    $i = 0;
    if(isset($results) && count($results)>0) {
?>

<table width="100%"  border="0" cellspacing="3" cellpadding="3">
	<tr class="thead">
		<th width="40%"><?php _e('Group Name'); ?></th>
		<th width="40%"><?php _e('HomePage'); ?></th>
    <th>&nbsp;</th>	
    <th>&nbsp;</th>		
	</tr>
	
	<?php

    foreach ($results as $result) {
      if($i%2 == 0) {
      	$style = 'class=\'alternate\'';
      }  else {
      	$style = '';
      }
	 ?>
	 <tr <?php echo $style; ?>>
		<td><?php echo $result->name; ?></td>
		<td><?php 
    
    if( $result->homepage != ""){
      $link = $result->homepage;
      if(strcasecmp(substr($result->homepage, 0, 5),"http:")){
        $link = "http://" . $link;
      }
      echo '<a href="'.$link.'">'.$result->homepage.'</a>';
    }else{
      echo "N/A";
    }
    
    ?></td>
    <td> <a class="edit" href="<?php echo $_SERVER['PHP_SELF'];?>?page=wp-group-restriction/wp-group-restriction.php&amp;mode=edit&amp;id=<?php echo $result->id; ?>">Edit</a>	</td>
    <td> <a class="delete" href="<?php echo $_SERVER['PHP_SELF'];?>?page=wp-group-restriction/wp-group-restriction.php&amp;mode=delete&amp;id=<?php echo $result->id; ?>">Delete</a></td>
	 </tr>
	 
	 <?php
      $i++;
    }
	
	
	?>
	
  </table>
<? 
    }//close if(isset($results) && count($results)>0)
    else{
    	echo "<p><strong>No groups available.</strong></p>";
    }
?>
</div>
<?php
if($mode!="edit"){
?>
<div class="wrap" id="new">
  <h2>Create Group</h2>
  <form action="<?php echo $_SERVER['PHP_SELF'];?>?page=wp-group-restriction/wp-group-restriction.php&amp;mode=add" method="post">
    
<?php
$submitName = "Create";
}else{
$submitName = "Update";
?>
<div class="wrap">
  <h2>Edit Group</h2>
  <form action="<?php echo $_SERVER['PHP_SELF'];?>?page=wp-group-restriction/wp-group-restriction.php&amp;mode=editSubmit" method="post">
    <input type="hidden" name="prevName" value="<?php echo $group->prev_name; ?>" />
    <input type="hidden" name="groupID" value="<?php echo $group->id; ?>" />
<?php

}

?>
   <table>
      <tr>
        <td>Group Name:</td>
        <td>
          <input type="text" name="groupName" <?php if(isset($group)) echo 'value="'.$group->name.'"';  ?> />
        </td>
      </tr>
      <tr>
        <td>Homepage:</td>
        <td>
          <input type="text" name="groupPage" <?php if(isset($group)) echo 'value="'.$group->homepage.'"' ?>/>
        </td>
      </tr>
      <tr>
        <td colspan="2" class="submit">                    
          <input type="submit" value="<?php echo $submitName; ?>" class="button" />
        </td>
      </tr>
    </table>
  </form>
</div>

