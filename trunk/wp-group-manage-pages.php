<?php
/************************************************************
 * This File loads the "Groups -> Pages" Tab
 * It allows to manage the groups' access to pages.
 * The information is presented from a Group Point of View. 
 ************************************************************/
 
$groups = new userGroups();

$mode = $_REQUEST['mode'];
$cancel = $_REQUEST['cancel'];

if($mode == "update"){
	$message = "Pages access rights for group <b>".$_REQUEST['groupName']."</b> were updated sucessfully.";
  	$groups->write($message);
}

switch($cancel){
	case 1:
		$groups->write("Group access edit canceled.");
		break;
	default: 
		break;
}


if($_REQUEST['id'] == "" && $mode == "edit"){
  $groups->write("Invalid page.");
}

?>

<div class="wrap">
<?php 

/**
 *  prints the page and the access values
 **/ 
function printPage($pagina, $alt, $groups, $group_id, $level){
  if($alt){
    echo "<tr class='alternate'>";
  }else{
    echo "<tr>";
  }
  $alt = !$alt;
  
  $spacer = "";
  for($cont = 0; $cont < $level; $cont++){
    $spacer .= "-";
  }
  
  echo "<td>$spacer $pagina->post_title</td>";
  echo "<td style='text-align:center;'><input type='checkbox' name='pages_read[]' value='$pagina->ID'";
  if($pagina->exc_read) echo "CHECKED";
  echo "/></td>";
  echo "<td style='text-align:center;'><input type='checkbox' name='pages_write[]' value='$pagina->ID'";
  if($pagina->exc_write) echo "CHECKED";
  echo "/></td>";
  echo "</tr>";
  
  $children = $groups->getAllPagesWithGroupByParent($group_id, $pagina->ID);
  $alt = pagesByParent($children, $alt, $groups, $group_id, $level+1);
  return $alt;
}

function pagesByParent($paginas,$alt, $groups, $group_id, $level = 0){
  if(isset($paginas) && count($paginas)>0){
    foreach ($paginas as $pagina) {
    	$alt = printPage($pagina, $alt, $groups, $group_id, $level);
    }
  }
  return $alt;
}

switch($mode){
  case "edit":
    if(isset($_REQUEST['id'])){
      $group = $groups->getGroup($_REQUEST['id']);
      $paginas = $groups->getAllPagesWithGroup($group->id);
    
      
      echo "<h2>Edit Pages Access for '".$group->name."'</h2>";
       
      echo '<form id="readWrite" name="readWrite" action="'.$_SERVER['PHP_SELF'].'?page=wp-group-restriction/manage_pages&amp;mode=update&amp;id='.$group->id.'" method="post">';
      echo "<input type=\"hidden\" name=\"groupName\" id=\"groupName\"  value=\"".$group->name."\" />";
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
      $paginas = $groups->getAllMainPagesWithGroup($group->id);
      
      
      if(isset($paginas)){
        echo "<table id='the-list-x' width='100%' cellpadding='3' cellspacing='3'>";
        echo "<tr class=\"thead\">";
        echo "<th scope='col' rowspan='2'>Page</th>";
        echo "<th scope='col' colspan='3'>Exclusive Access</th>";
        echo "</tr>";
        echo "<tr class=\"thead\">";
        echo "<th scope='col' style='width:7em'>Read</th>";
        echo "<th scope='col' style='width:7em'>Write</th>";
        echo "</tr>";
        $alt = true;
        print pagesByParent($paginas, &$alt, $groups, $group->id);
        echo "<tr >";
        echo "<td scope='col'>&nbsp;</td>";
        echo "<td scope='col' style='text-align:center;'>".
        "<a href='#' onclick='select_all(\"pages_read\", true);'>All</a>".
        " / <a href='#' onclick='select_all(\"pages_read\", false);'>None</a></td>";
        echo "<td scope='col' style='text-align:center;'>".
        "<a href='#' onclick='select_all(\"pages_write\", true);'>All</a>".
        " / <a href='#' onclick='select_all(\"pages_write\", false);'>None</a></td>";
  		echo "</tr>";
        echo "</table>";
        $groups->printExplanation();
      }
      ?>
      <br />
      <div class="submit">
      <input  type="submit" value="Update" />
      <input type="button"
			onclick="javascript:location.href = '?page=wp-group-restriction/manage_pages&amp;cancel=1'"
			value="Cancel" class="button" />
	  </div>
     </form>
      
      <?php
    }

  
  
    break;
  case "update":
    //merge the two access arrays
    $readable = array();
    $writeable = array();
    $pages = array();
    
    if(isset($_POST['pages_read']))
      foreach($_POST['pages_read'] as $id){
        $readable[$id]=1;
        $writeable[$id]=0;
        $pages[] =$id;
      }
    if(isset($_POST['pages_write']))
      foreach($_POST['pages_write'] as $id){
        if(!isset($readable[$id])){
          $readable[$id]=0;
          $pages[] =$id;
        }
        $writeable[$id]=1;
      }
    
    
    $groups->setGroupPages($pages,$readable,$writeable,$_REQUEST['id']);
  default:
?>

<h2><?php _e('Manage Groups Access'); ?></h2>
<table width="100%"  border="0" cellspacing="3" cellpadding="3">
	<tr class="thead">
		<th><?php _e('Group Name'); ?></th>
		<th><?php _e('Pages'); ?></th>
    <th>&nbsp;</th>	
	</tr>
	
	<?php
    $groups = new userGroups();
    
    $results = $groups->getGroups();
    
    $i = 0;
    if(isset($results))
    foreach ($results as $result) {
      if($i%2 == 0) {
      	$style = 'class=\'alternate\'';
      }  else {
      	$style = '';
      }
      echo "<tr $style><td>".$result->name."</td><td>";
      $paginas = $groups->getGroupPages($result->id);
      
      if(isset($paginas) && count($paginas) > 0){
	      foreach ($paginas as $pagina) {
	        $perms = " (";
	        if($pagina->exc_read){
	          $perms .= "R";
	        }
	        if($pagina->exc_write){
	          $perms .= "W";
	        }
	        $perms .= ")";
	        
	          
	        echo "- ".$pagina->post_title. "$perms<br />"; 
	      }
      }else{
      	 echo "(no pages)";
      }
      echo "</td><td><a class=\"edit\"  href='".$_SERVER['PHP_SELF'].
        "?page=wp-group-restriction/manage_pages&amp;mode=edit&amp;id="
        .$result->id."'>Edit</a></td></tr>";
      $i++;
	}
	
   	echo "<tr><td colspan='3'><div style='font-size:1px;border-bottom:1px dashed #999'>&nbsp;</div></td></tr>";
    echo "<tr><td>Pages with free access</td><td>";
    $paginas = $groups->getGroupFreePages();
    
    if(isset($paginas))
    foreach ($paginas as $pagina) {
      echo "- ".$pagina->post_title."<br />"; 
    }
    echo "</td><td>&nbsp;</td></tr>";
      ?>

  </table>
<?php
}
?>
</div>
