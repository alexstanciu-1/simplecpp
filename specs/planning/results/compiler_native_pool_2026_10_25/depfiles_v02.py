from pathlib import Path
import json,sys,shutil
root=Path.cwd();app=root/'compiler/my-try';out=Path('/tmp/portable-depfiles-v02');out.mkdir()
sys.path.insert(0,str(app/'tools'));from test_runner import CommandRunner,Task,run_tasks
source=Path('/tmp/portable-depfiles-01/source');expected=(Path('/tmp/portable-depfiles-01/logs/php.stdout')).read_bytes()
driver=out/'publish.php';driver.write_text('''<?php
namespace scpp\\compiler;
require $argv[1];
Compiler_Lifecycle::configure_runtime(Runtime_Catalog_Loader::load($argv[2], ['compiler','strings']));
$publication=new Output_Publication($argv[4]);
try { $compiler=new Compiler(new cpp_lowering_options($argv[5] === 'concrete')); $compiler->init([$argv[3]]); $compiler->select_entry('main','main.phs'); $compiler->exec_cpp($publication,new cpp_lowering_policy(2)); } finally {$publication->close();}
''')
with CommandRunner(out/'logs',root) as r:
 def proof(mode):
  folder=out/mode;folder.mkdir();project=folder/'source'
  r.run(mode+'-convert',['php',root/'tools/php_portability/convert.php',source,project,'--target=v0.2'])
  r.run(mode+'-framework',['php',root/'tools/php_portability/install_native_runtime.php',project])
  r.run(mode+'-publish',['php',driver,app/'boot.php',root/'runtime/metadata/modules',project,folder/'published',mode],timeout=120)
  built=json.loads(r.run(mode+'-build',['php',app/'tools/build_cpp.php',folder/'published',folder/'cache','-O2','--jobs=4'],timeout=300))
  assert r.run(mode+'-run',[built['executable']])==expected
 run_tasks([Task(mode,lambda mode=mode:proof(mode)) for mode in ['ordinary','concrete']],jobs=2)
