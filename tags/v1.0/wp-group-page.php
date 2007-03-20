<?php
/*************************************************************
 * This File loads the "Groups -> Groups" Tab
 * It allows to manage Groups by editing, adding or deleting
 ************************************************************/

$groups = new userGroups();

$mode = $_REQUEST['mode'];
$cancel = $_REQUEST['cancel'];

$errorMessage = "";

switch($mode){
	case "add":
		if(!$groups->isValidName($_POST['groupName'])){
			if($_POST['groupName'] == ""){
				$errorMessage="Please specify a name for the group.";
			}else{
				$errorMessage="A group with the name <b>".$_POST['groupName']."</b> already exists.";
			}
		}else{
			if($groups->createGroup ($_POST['groupName'], $_POST['groupPage'], $_POST['groupDesc'])){
				$message = "Group <b>".$_POST['groupName']."</b> created successfuly. ";
					
				$group = $groups->getGroupByName($_POST['groupName']);
					
				$message .= "<a href=\"?page=wp-group-restriction/group_members&mode=edit&id={$group->id}\">Add users to the group &raquo;</a>";
				$groups->write($message);
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
		$group->description = $_POST['groupDesc'];

		if(!$groups->isValidName($_POST['groupName']) && $_POST['groupName'] != $_POST['prevName']){
			if($_POST['groupName'] == ""){
				$errorMessage="Please specify a name for the group.";
				$mode = "edit";
			}else{
				$errorMessage="A group with the name <b>".$_POST['groupName']."</b> already exists.";
				$mode = "edit";
			}
		}else{
			if($groups->updateGroup ($_POST['groupID'], $_POST['groupName'], $_POST['groupPage'], $_POST['groupDesc'])){
				$groups->write("Group <b>".$_POST['groupName']."</b> updated successfuly.");
				$group = "";
			}else{
				$errorMessage="Group <b>".$_POST['prevName']."</b> was not updated successfuly.";
				$mode = "edit";
			}
		}
		break;
	case "delete":
		$idDel = $_REQUEST['id'];
		if($idDel != ""){
			if($groups->deleteGroup($idDel)){
				$groups->write("Group Deleted.");
			}else{
				$groups->write("Invalid group. No groups were deleted.",false);
			}
			$_REQUEST['id'] = "";
		}
		break;
	default:
		switch($cancel){
			case 1:
				$groups->write("Group edit canceled.");
				break;
			default:
				break;
		}
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
	?> <script type="text/javascript">
function DelConfirm(name){
	var message= 'You are about to delete the group "'+name+'", do you wish to continue?';
	return confirm(message);
}
</script>
<table width="100%" border="0" cellspacing="3" cellpadding="3">
	<tr class="thead">
		<th><?php _e('Name'); ?></th>
		<th><?php _e('Description'); ?></th>
		<th><?php _e('Homepage'); ?></th>
		<th style="width:5em;">&nbsp;</th>
		<th style="width:5em;">&nbsp;</th>
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
		<td><?php echo $result->description; ?></td>
		<td><?php 

		if( $result->homepage != ""){
			$link = $result->homepage;
			if(strcasecmp(substr($result->homepage, 0, 5),"http:")){
				$link = "http://" . $link;
			}
			echo '<a href="'.$link.'">'.$result->homepage.'</a>';
		}else{
			echo "(none)";
		}

		?></td>
		<td><a class="edit"
			href="<?php echo $_SERVER['PHP_SELF'];?>?page=wp-group-restriction/wp-group-restriction.php&amp;mode=edit&amp;id=<?php echo $result->id; ?>">Edit</a>
		</td>
		<td><a class="delete"
			href="<?php echo $_SERVER['PHP_SELF'];?>?page=wp-group-restriction/wp-group-restriction.php&amp;mode=delete&amp;id=<?php echo $result->id; ?>"
			onClick="return DelConfirm('<?php echo $result->name; ?>');">Delete</a></td>
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
?></div>
<?php
if($errorMessage != ""){
	$groups->write($errorMessage,false, "msg");
}
if($mode!="edit"){
	?>
<div class="wrap" id="new">
<h2>Create Group</h2>
<form
	action="<?php echo $_SERVER['PHP_SELF'];?>?page=wp-group-restriction/wp-group-restriction.php&amp;mode=add#msg"
	method="post"><?php
	$submitName = "Create";
}else{
	$submitName = "Update";
	?>
<div class="wrap">
<h2>Edit Group</h2>
<form
	action="<?php echo $_SERVER['PHP_SELF'];?>?page=wp-group-restriction/wp-group-restriction.php&amp;mode=editSubmit#msg"
	method="post"><input type="hidden" name="prevName"
	value="<?php echo $group->prev_name; ?>" /> <input type="hidden"
	name="groupID" value="<?php echo $group->id; ?>" /> <?php

}

?>
<fieldset>
<table>
	<tr>
		<td style="width:0.7em;"><b>*</b></td>
		<td style="width:4em;"><b>Name:</b></td>
		<td><input style="width: 250px;" type="text" name="groupName"
		<?php if(isset($group)) echo 'value="'.$group->name.'"';  ?> /></td>
	</tr>
	<tr>
		<td>&nbsp;</td>
		<td style="vertical-align:top;"><b>Description:</b></td>
		<td><textarea style="width: 470px;" name="groupDesc" id="groupDesc"><?php if(isset($group)) echo $group->description; ?></textarea>
		</td>
	</tr>
	<tr>
		<td>&nbsp;</td>
		<td><b>Homepage:</b></td>
		<td><input style="width: 470px;" type="text" name="groupPage"
		<?php if(isset($group)) echo 'value="'.$group->homepage.'"' ?> /></td>
	</tr>
</table>
<div class="submit"><input type="submit"
	value="<?php echo $submitName; ?>" class="button" /> <?php if($mode=="edit"){ ?>
<input type="button"
	onclick="javascript:location.href='?page=wp-group-restriction/wp-group-restriction.php&amp;cancel=1'"
	value="Cancel" class="button" /> <?php } ?></div>
</fieldset>
</form>
</div>