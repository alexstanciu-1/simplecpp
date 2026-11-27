$db=new mysqli("localhost","root","","test",0,"@SOCKET@");
if($db->connect_errno!==0 || $db->connect_error!==""){return 1;}
if(!$db->set_charset("utf8mb4")){return 2;}
$dml=$db->query("CREATE TEMPORARY TABLE proof (id INT, data BLOB) ENGINE=InnoDB");
if($dml!==true){return 3;}
$missing=$db->query("SELECT * FROM no_such_scpp_table");
if($missing!==false || $db->errno===0 || $db->error===""){return 4;}
$res mysqli_result=$db->query("SELECT id FROM proof");
if($res->num_rows!==0 || $res->fetch_row()!==null){return 5;}
$guarded=$db->prepare("SELECT 1");
if(!$guarded){return 20;}
if(!$guarded->execute()){return 21;}
$guarded->close();
$guardedRows=$db->query("SELECT 1");
if($guardedRows->num_rows!==1){return 23;}
if($guardedRows->fetch_row()===null){return 24;}
$invalid=$db->prepare("SELECT FROM");
if($invalid){return 22;}
$id=1;$payload="a\0b";
$stmt mysqli_stmt=$db->prepare("INSERT INTO proof VALUES (?,?)");
if(!$stmt->bind_param("ib",$id,$payload)){return 6;}
if(!$db->begin_transaction() || !$stmt->execute() || !$db->rollback()){return 7;}
$id=2;
if(!$db->begin_transaction() || !$stmt->execute() || $stmt->affected_rows!==1 || !$db->commit()){return 8;}
$res=$db->query("SELECT id,data,NULL AS nothing FROM proof");
$row=$res->fetch_assoc(); $n int=$row["id"]; $bytes string=$row["data"];
if($n!==2 || $bytes!=="a\0b" || $row["nothing"]!==null || $res->fetch_assoc()!==null){return 9;}
$select mysqli_stmt=$db->prepare("SELECT data FROM proof WHERE id=?");
if(!$select->bind_param("i",$id) || !$select->execute()){return 10;}
$kept mysqli_result=$select->get_result();
$id=3;
if(!$select->execute()){return 11;}
$empty mysqli_result=$select->get_result();
if($empty->num_rows!==0){return 12;}
$direct mysqli=db_connect("localhost","root","","test",0,"@SOCKET@");
$other mysqli_stmt=db_prepare($direct,"SELECT ?,?,?");
$blob=str_repeat("xy",1000);
$args dynamic=[$blob,1.2345678901234567,null];
if(!db_execute($other,"bds",$args)){return 15;}
$results mysqli_result=db_statement_get_result($other);
db_statement_close($other);
db_close($direct);
$values=db_fetch_row($results);$binary string=$values[0];$real float=$values[1];
if($binary!==$blob){return 16;} if($real!==1.2345678901234567){return 18;} if($values[2]!==null){return 19;}
$failed=new mysqli("localhost","root","","test",0,"@SOCKET@.missing");
if($failed->connect_errno===0 || $failed->connect_error===""){return 17;}
$db->close();
if($stmt->execute() || $stmt->errno_code===0){return 13;}
$row=$kept->fetch_row();$bytes=$row[0];
if($bytes!=="a\0b" || $kept->fetch_row()!==null){return 14;}
return 0;
