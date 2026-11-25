import shutil,sys
from pathlib import Path
B=Path(__file__).resolve().parent;R=B.parent/'repo';old=B.parent/'optimization-policy-20261016'
sys.path.insert(0,str(R/'compiler/my-try/tools'))
from test_runner import CommandRunner,Task,run_tasks
changed=['05_backend/cpp/implementation_groups','05_backend/cpp/policy','05_backend/cpp/lowering_report','main']
for stage in ['01_prepare_inputs','02_tokenize','03_parse','04_analyze','05_backend','06_native','compiler']:
 shutil.copytree(R/'compiler/my-try'/stage,B/'source'/stage,dirs_exist_ok=True)
shutil.copy2(B/'driver.php',B/'source/main.php')
shutil.copytree(old/'phpp/scpp_framework',B/'phpp/scpp_framework',dirs_exist_ok=True)
(B/'regenerate.php').write_text('''<?php
require __DIR__ . '/../repo/generators/php/bin/bootstrap.php';
$paths=[];
foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__.'/phpp')) as $f) {
 if ($f->isFile() && $f->getExtension()==='phs') { $paths[]=$f->getPathname(); }
}
$catalog=(new Scpp\\S2S\\Analysis\\DeclarationCatalogBuilder())->buildCatalogFromSources($paths);
file_put_contents(__DIR__.'/declaration-catalog.json',json_encode($catalog));
foreach ('''+repr(changed).replace("'",'"')+''' as $relative) {
 $t=new Scpp\\S2S\\Transpiler(phpProfile:'strict');
 $t->setDeclaredTypeKinds($catalog['declared_type_kinds']);
 $t->setAccessorDeclarations($catalog['accessor_declarations']);
 $result=$t->transpile(__DIR__.'/phpp/'.$relative.'.phs',false,$relative === 'main');
 if ($result->errors!==[]) { throw new RuntimeException(implode("\\n",$result->errors)); }
 $dir=__DIR__.'/generated/'.dirname($relative); if (!is_dir($dir)) { mkdir($dir,0777,true); }
 file_put_contents(__DIR__.'/generated/'.$relative.'.hpp',implode("\\n",$result->headerLines)."\\n");
 file_put_contents(__DIR__.'/generated/'.$relative.'.cpp',implode("\\n",$result->sourceLines)."\\n");
}
''')
with CommandRunner(B/'native-prepare-logs',R) as runner:
 def prepare():
  runner.run('convert',['php',R/'tools/php_portability/convert.php',B/'source',B/'phpp'],timeout=300,cli_reason='Authored lowering diagnostics to native compiler source')
  runner.run('regenerate',['php',B/'regenerate.php'],timeout=300,cli_reason='Legacy S2S with full project declaration catalog; no STAN')
 run_tasks([Task('prepare-native-compiler',prepare)])
