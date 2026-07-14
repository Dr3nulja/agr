<?php
$m = new mysqli('127.0.0.1','root','','agr');
if($m->connect_error){
    echo 'CONNECT_ERROR:'.$m->connect_error.PHP_EOL;
    exit(1);
}
$res = $m->query("SHOW COLUMNS FROM `log`");
if(!$res){
    echo 'QUERY_ERROR:'.$m->error.PHP_EOL;
    exit(1);
}
while($r = $res->fetch_assoc()){
    echo $r['Field'] . "\t" . $r['Type'] . PHP_EOL;
}
$m->close();
