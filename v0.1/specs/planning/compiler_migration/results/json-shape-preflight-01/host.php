<?php
declare(strict_types=1);
require '/home/alexv/__AI/simple_cpp/simple_cpp_01/compiler/bootstrap.php';
$inputs=['{}','[]','{"0":"src"}','["src"]','{"source_folders":{},"entry":"a.phs"}','{"source_folders":[],"entry":"a.phs"}','{"source_folders":{"0":"src"},"entry":"a.phs"}','{"source_folders":["src"],"entry":"a.phs"}'];
foreach($inputs as $text) {
 echo $text,' => ',json_encode(json_decode($text,flags:JSON_THROW_ON_ERROR),JSON_THROW_ON_ERROR),' | ';
 try { read_manifest\Manifest_Syntax::parse('probe.json',$text);echo 'accepted'; }
 catch(Exception $e) {echo $e->getMessage();}
 echo "\n";
}
