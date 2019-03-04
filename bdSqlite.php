<?php


 class MyDB extends SQLite3 {
      function __construct() {
         $this->open('test.db');
      }
   }
   
  $db = new MyDB();
   if(!$db) {
      echo $db->lastErrorMsg();
   } else {
      echo "Opened database successfully\n";
   }
	
	$sql =<<<EOF
      CREATE TABLE file_(
	   ID INTEGER PRIMARY KEY AUTOINCREMENT,
	   NAME           TEXT      NOT NULL);
EOF;

   $ret = $db->exec($sql);
   if($ret){
      echo "Table created successfully\n";
   }
   $db->close();


function inserta_registro($file){
	$db = new MyDB();
   if(!$db){
      echo $db->lastErrorMsg();
   }

   $ret = $db->exec(" INSERT INTO file_ (NAME)  VALUES ('$file')");
   if(!$ret) {
      echo $db->lastErrorMsg();
   }
   
   $sql =<<<EOF
      SELECT COUNT(id) AS 'lastId' FROM file_;
EOF;
   $ret = $db->query($sql);
   while($row = $ret->fetchArray(SQLITE3_ASSOC) ) {
      $lastId = $row['lastId'];
   }

   $db->close();
   
   return $lastId;
}

function existe_registro($file){

	$db = new MyDB();

	$rows = $db->query("SELECT COUNT(*) as count FROM file_ where NAME=$file");
	$row = $rows->fetchArray();
	$numRows = $row['count'];
	
	$existe = false;
	if((int)$numRows > 0){
		$existe = true;
	}
	$db->close();
	return $existe;
}

?>
