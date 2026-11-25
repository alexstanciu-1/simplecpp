"""Portable dependency parser: continuations, byte paths and first-rule boundaries."""
import argparse
import json
from pathlib import Path
import shutil
import sys
ROOT = Path(__file__).resolve().parents[2]
APP = ROOT / 'compiler/my-try'
sys.path.insert(0, str(APP / 'tools'))
from test_runner import CommandRunner, Task, run_tasks
p = argparse.ArgumentParser(description=__doc__)
p.add_argument('--results', type=Path, required=True)
out = p.parse_args().results.resolve()
out.mkdir(parents=True, exist_ok=False)
source = out / 'source'
source.mkdir()
shutil.copyfile(APP / '06_native/depfiles.php', source / 'depfiles.php')
(source / 'main.php').write_text(r'''<?php
namespace scpp\compiler;
$rule = 'output: one\\ two /absolute/hash\\#name dollar$$name ' . "\\\n" . " one\\ two\nignored:\n";
echo q_implode('|', Native_Depfiles::paths($rule, '/inputs')), "\n";
echo q_implode('|', Native_Depfiles::paths("out: split\\\nname tail", '/inputs')), "\n";
echo q_implode('|', Native_Depfiles::paths("out: end\\\n", '/inputs')), "\n";
echo q_implode('|', Native_Depfiles::paths('out: ' . string_byte_from_int(255), '/inputs')), "\n";
$invalid /** vector<string> */ = ['no rule', 'output: broken\\', 'output: $expansion', "out: $\n"];
foreach ($invalid as $bad) {
 $rejected = false;
 try { Native_Depfiles::paths($bad, '/inputs'); }
 catch (\RuntimeException $error) { $rejected = true; }
 if (!$rejected) { throw new \RuntimeException('Malformed dependency accepted'); }
}
$long_rule = 'out: ';
for ($i = 0; $i < 1024; $i++) { $long_rule .= 'path_' . $i . " \\\n"; }
$paths = Native_Depfiles::paths($long_rule, '/inputs');
echo q_count($paths), ':', $paths[1023];
''')
expected = b'/inputs/one two|/absolute/hash#name|/inputs/dollar$name\n/inputs/splitname|/inputs/tail\n/inputs/end\n/inputs/\xff\n1024:/inputs/path_1023'
with CommandRunner(out / 'logs', ROOT) as runner:
    def proof():
        assert runner.run('php', ['php', '-r', 'require $argv[1]; require $argv[2]; require $argv[3];', ROOT / 'tools/php_portability/runtime/bootstrap.php', source / 'depfiles.php', source / 'main.php']) == expected
        project = out / 'legacy'
        runner.run('convert', ['php', ROOT / 'tools/php_portability/convert.php', source, project])
        runner.run('framework', ['php', ROOT / 'tools/php_portability/install_native_runtime.php', project])
        runner.run('init', ['php', ROOT / 'bin/scpp.php', 'init', '--php-profile=strict'], cwd=project)
        config_path = project / 'prism.json'
        config = json.loads(config_path.read_text())
        config['build']['cxx'] = 'clang++'
        config_path.write_text(json.dumps(config))
        runner.run('build', ['php', ROOT / 'bin/scpp.php', 'build', '--no-stan', '--build-runtime'], cwd=project, timeout=300)
        assert runner.run('execute', [project / '.prism/build/main']) == expected
    run_tasks([Task('portable-depfiles', proof)], jobs=1)
(out / 'result.json').write_text(json.dumps({'passed': True, 'php': True, 'legacy_native': True}) + '\n')
