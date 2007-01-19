<?php
$groups = new userGroups();

$mode = $_REQUEST['mode'];

if($mode == "update"){
  $groups->write("Group pages updated");
}


if($_REQUEST['id'] == "" && $mode == "edit"){
  $groups->write("Error: invalid arguments...");
}

?>

<div class="wrap">
<?php 

/**
 *  prints the page and the access values
 **/ 
function printPage($pagina, &$alt, $groups, $group_id, $level){
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
  pagesByParent($children, &$alt, $groups, $group_id, $level+1);
}

function pagesByParent($paginas,&$alt, $groups, $group_id, $level = 0){
  if(isset($paginas) && count($paginas)>0){
    foreach ($paginas as $pagina) {
      printPage($pagina, &$alt, $groups, $group_id, $level);
    }
  }
}

switch($mode){
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
  case "edit":
    if(isset($_REQUEST['id'])){
      $group = $groups->getGroup($_REQUEST['id']);
      $paginas = $groups->getAllPagesWithGroup($group->id);
    
      
      echo "<h2>Edit Pages Access for '".$group->name."'</h2>";
       
      echo '<form id="readWrite" name="readWrite" action="'.$_SERVER['PHP_SELF'].'?page=wp-group-restriction/manage_pages&amp;mode=update&amp;id='.$group->id.'" method="post">';
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
        echo "<tr>";
        echo "<th scope='col' rowspan='2'>Page</th>";
        echo "<th scope='col' colspan='3'>Exclusive</th>";
        echo "</tr>";
        echo "<tr>";
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

<h2><?php _e('Group Pages'); ?></h2>
<table width="100%"  border="0" cellspacing="3" cellpadding="3">
	<tr>
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
      
      if(isset($paginas))
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
      echo "</td><td><a class=\"edit\"  href='".$_SERVER['PHP_SELF'].
        "?page=wp-group-restriction/manage_pages&amp;mode=edit&amp;id="
        .$result->id."'>Edit</a></td></tr>";
      ?>
    
      
      <?php 
      $i++;
	}
	 
    if($i%2 == 0) {
    	$style = 'class=\'alternate\'';
    }  else {
    	$style = '';
    }
    echo "<tr $style><td>Free Access Pages</td><td>";
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
