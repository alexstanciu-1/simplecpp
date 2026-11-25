<?php
require __DIR__ . '/../repo/generators/php/bin/bootstrap.php';
$paths=[];
foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__.'/phpp')) as $f) {
 if ($f->isFile() && $f->getExtension()==='phs') { $paths[]=$f->getPathname(); }
}
$catalog=(new Scpp\S2S\Analysis\DeclarationCatalogBuilder())->buildCatalogFromSources($paths);
file_put_contents(__DIR__.'/declaration-catalog.json',json_encode($catalog));
foreach (["05_backend/cpp/data_construction"] as $relative) {
 $t=new Scpp\S2S\Transpiler(phpProfile:'strict');
 $t->setDeclaredTypeKinds($catalog['declared_type_kinds']);
 $t->setAccessorDeclarations($catalog['accessor_declarations']);
 $result=$t->transpile(__DIR__.'/phpp/'.$relative.'.phs',false,false);
 if ($result->errors!==[]) { throw new RuntimeException(implode("\n",$result->errors)); }
 $dir=__DIR__.'/generated/'.dirname($relative); if (!is_dir($dir)) { mkdir($dir,0777,true); }
 file_put_contents(__DIR__.'/generated/'.$relative.'.hpp',implode("\n",$result->headerLines)."\n");
 file_put_contents(__DIR__.'/generated/'.$relative.'.cpp',implode("\n",$result->sourceLines)."\n");
}
