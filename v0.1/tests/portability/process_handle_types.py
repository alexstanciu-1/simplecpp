from pathlib import Path
import sys,json
import argparse
root=Path(__file__).resolve().parents[2]
p=argparse.ArgumentParser(description='Portable process handle boundaries and byte search, PHP and legacy native');p.add_argument('--results',type=Path,required=True)
out=p.parse_args().results.resolve();out.mkdir(parents=True,exist_ok=False);source=out/'source';source.mkdir()
(source/'main.php').write_text(r'''<?php
final class Process_Box {
 public ?\scpp\Process_Handle $handle = null;
 public function start(): void {
  $args /** vector<string> */ = ['-c', 'printf portable'];
  $this->handle = process_spawn('/bin/sh', $args, '', 2000, '/tmp');
 }
 public function get(): \scpp\Process_Handle { return $this->handle; }
 public function finish(\scpp\Process_Handle $input): void {
  while (!process_poll($input)) { dt_sleep_ms(1); }
  $output = process_output($input);
  echo $output->stdout_text;
  process_close($input);
  $this->handle = null;
 }
}
$box = new Process_Box();
$box->start();
$handle /** \scpp\Process_Handle */ = $box->get();
$box->finish($handle);
$bytes = string_byte_from_int(255) . 'z';
$found /** int */ = 0;
if (!take_false($found, string_byte_find($bytes, 'z'))) { throw new \RuntimeException('Missing byte marker'); }
echo $found;
if (take_false($found, string_byte_find($bytes, 'x'))) { throw new \RuntimeException('Invented byte marker'); }
if (!take_false($found, string_byte_find($bytes, '', 2))) { throw new \RuntimeException('Missing end offset'); }
echo $found;
''')
sys.path.insert(0,str(root/'compiler/my-try/tools'));from test_runner import CommandRunner,Task,run_tasks
with CommandRunner(out/'logs',root) as runner:
 def proof():
  assert runner.run('php',['php8.5','-r','require $argv[1]; require $argv[2];',root/'tools/php_portability/runtime/bootstrap.php',source/'main.php'])==b'portable12'
  project=out/'legacy'
  runner.run('convert',['php',root/'tools/php_portability/convert.php',source,project])
  runner.run('framework',['php',root/'tools/php_portability/install_native_runtime.php',project,'--os'])
  runner.run('init',['php',root/'bin/scpp.php','init','--php-profile=strict'],cwd=project)
  config_path=project/'prism.json';config=json.loads(config_path.read_text());config['runtime']['modules']=['compiler','process','datetime','filesystem'];config['build']['cxx']='clang++';config_path.write_text(json.dumps(config))
  runner.run('build',['php',root/'bin/scpp.php','build','--no-stan','--build-runtime'],cwd=project,timeout=300)
  assert runner.run('execute',[project/'.prism/build/main'])==b'portable12'
 run_tasks([Task('process-binding',proof)],jobs=1)
(out/'result.json').write_text(json.dumps({'passed':True,'php':'portable12','legacy_native':'portable12'}))
