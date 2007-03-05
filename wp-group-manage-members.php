<?php
/****************************************************
 * This File loads the Groups -> Members Tab
 * It allows to manage the groups members (users) 
 ****************************************************/ 

$groups = new userGroups();

$mode = $_REQUEST['mode'];

if($mode == "update"){
  $group_temp = $groups->getGroup(($_REQUEST['id']));
	$groups->write("Groups access to '".$group_temp->name."' updated.");
}


if($_REQUEST['id'] == "" && ($mode == "edit" || $mode == "update")){
	$groups->write("Error: invalid arguments.");
}

?>

<div class="wrap">
<?php

//prints a page with its groups and then prints the same for its children
function userGroups_PrintGroupMembers(){
	$groups = new userGroups();

	$results = $groups->getGroups();
	
	$alt = true;
	
	if(isset($results) && count($results)>0){
		echo "\n<table width=\"100%\" border=\"0\" cellspacing=\"3\" cellpadding=\"3\">";
		echo "\n\t<tr class=\"thead\">";
		echo "\n\t\t<th>Group Name</th>\n\t\t<th>Members</th>\n\t\t<th>&nbsp;</th>";
		echo "\n\t</tr>";
		
		foreach ($results as $result) {
			if($alt) {
				$style = 'class=\'alternate\'';
			}  else {
				$style = '';
			}
			$alt = !$alt;
	
			echo "<tr ".$style."><td>".$result->name."</td><td>";
	
			$members = $groups->getGroupMembers($result->id);
	
			if(isset($members) && count($members) > 0){
				
				$number = count($members);
				if($number == 1)
				echo "<b>1 user:</b><br />";
				else
				echo "<b>$number users:</b><br />";
	
				foreach ($members as $member) {
					echo "- ".$member->name. "<br />";
				}
			}else{
				echo "<b>No users</b>";
			}
			echo "</td><td ".$style."><a class=\"edit\"  href='".$_SERVER['PHP_SELF'].
	        "?page=wp-group-restriction/group_members&amp;mode=edit&amp;id="
	        .$result->id."'>Edit</a></td></tr>";
		}
		echo "\n</table>";
	}else{
		//No groups available...
		echo "<p><strong>No groups available.</strong></p>";
	}
}

switch($mode){
	case "edit":
		if(isset($_REQUEST['id'])){
			$groupID = $_REQUEST['id'];

			$group = $groups->getGroup($groupID);


			echo "<h2>Edit members of '".$group->name."' group</h2>";

			echo '<form id="readWrite" name="readWrite" action="'.$_SERVER['PHP_SELF'].'?page=wp-group-restriction/group_members&amp;mode=update&amp;id='.$groupID.'" method="post">';
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
			$members = $groups->getUsersWithGroup($groupID);

			if(isset($members)){
				foreach($members as $member){
					
					if($member->isMember){
						$checked = " checked ";
					}else {
						$checked = "";
					}


					echo "<input type='checkbox' name='users[]' value='".$member->id."' id='rw".$member->id."' $checked />".
					"<label for='rw".$member->id."'> ".$member->name."</label><br />";

				}
			}

			?> <br />
	<div class="submit">
		<input type="submit" value="Update" />
		<input type="button"
			onclick="javascript:location.href = '?page=wp-group-restriction/manage_pages'"
			value="Cancel" class="button" />
	</div>
</form>
      
<?php
		}
		
		break;
	case "update":
		//update groups members
		if($_REQUEST['id']!= ""){
			$groups->deleteAllGroupUser($_REQUEST['id']);
			$groups->createGroupWithUsers($_REQUEST['id'],$_POST['users']);
		}
	default:
		?>

<h2>Groups Members</h2>
<?php
	userGroups_PrintGroupMembers();
}
?></div>
