import json, os, shutil, sys
from pathlib import Path
BASE=Path(__file__).resolve().parent
REPO=BASE.parent/'repo'
sys.path.insert(0,str(REPO/'compiler/my-try/tools'))
from test_runner import CommandRunner, Task, run_tasks
APP=REPO/'compiler/my-try'
source=BASE/'source';source.mkdir(exist_ok=True)
for stage in ['01_prepare_inputs','02_tokenize','03_parse','04_analyze','05_backend','06_native','compiler']:
 shutil.copytree(APP/stage,source/stage,dirs_exist_ok=True)
(source/'main.php').write_text('''<?php
namespace scpp\\compiler;
$request = json_read(fs_read_text('request.json'));
$marks = fs_output_root($request->member('marks')->text());
fs_output_write($marks, '01-start', 'start');
$publication = new Output_Publication($request->member('published')->text());
try {
$modules /** vector<string> */ = ['core', 'strings', 'datetime', 'filesystem', 'json'];
Compiler_Lifecycle::configure_runtime(Runtime_Catalog_Loader::load($request->member('catalogs')->text(), $modules));
$compiler = new Compiler();
$compiler->jobs = 12;
$paths /** vector<string> */ = [$request->member('source')->text()];
$compiler->init($paths);
$compiler->select_entry('main', 'main.phs');
fs_output_write($marks, '02-setup', 'setup');
$changed /** vector<string> */ = [];
$compiler->sync($changed);
fs_output_write($marks, '03-sync', 'sync');
$compiler->prepare(true);
fs_output_write($marks, '04-prepare', 'prepare');
 $compiler->cpp($publication);
} finally { $publication->close(); }
fs_output_write($marks, '05-cpp-ready', 'cpp-ready');
$config = new native_ninja_config($request->member('clang')->text(), $request->member('runtime')->text(), $request->member('build')->text());
$config->reuse = $request->member('reuse')->boolean();
$config->jobs = 8;
$result = (new Native_Ninja_Build($config))->build($request->member('published')->text());
fs_output_write($marks, '06-build-done', 'build-done');
echo $result->output;
echo "EXECUTABLE=", $result->executable, "\\n";
''')
wrapper=BASE/'clang-o2'
wrapper.write_text('#!/usr/bin/python3\nimport os,sys\nargs=["-O2" if a in ("-O0","-O3") else a for a in sys.argv[1:]]\nos.execv("/usr/bin/clang++-18",["/usr/bin/clang++-18",*args])\n')
wrapper.chmod(0o755)
os.environ['SCPP_NINJA_JOBS']='8'
with CommandRunner(BASE/'compiler-logs-5', REPO,jobs=12) as runner:
 def build():
  project=BASE/'phpp'
  runner.run('convert',['php',REPO/'tools/php_portability/convert.php',source,project],timeout=300,cli_reason='Native compiler source conversion')
  runner.run('framework',['php',REPO/'tools/php_portability/install_native_runtime.php',project,'--filesystem','--os','--json'],cli_reason='Native framework assembly')
  if not (project/'prism.json').exists():
   runner.run('init',['php',REPO/'bin/scpp.php','init','--php-profile=strict'],cwd=project,cli_reason='Native compiler project initialization')
  config=json.loads((project/'prism.json').read_text())
  config['build']['cxx']=str(wrapper)
  config['runtime']['modules']=['compiler','filesystem','tasks','process','datetime','json']
  (project/'prism.json').write_text(json.dumps(config,indent=2))
  runner.run('build',['/usr/bin/time','-v','-o',BASE/'compiler-build-resource.txt','php',REPO/'bin/scpp.php','build','--build-runtime','--no-stan'],cwd=project,timeout=1800,cli_reason='Requested O2 native compiler rebuild')
 run_tasks([Task('native-O2-build',build)])
