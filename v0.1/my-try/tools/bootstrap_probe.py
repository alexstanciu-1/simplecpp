"""Snapshot/convert the compiler and measure PHP-hosted self-bootstrap; no native build."""
import argparse
import hashlib
import json
from pathlib import Path
import shutil
import subprocess
from test_runner import CommandRunner, Task, run_tasks

APP = Path(__file__).resolve().parents[1]
ROOT = APP.parents[1]
STAGES = ['01_prepare_inputs', '02_tokenize', '03_parse', '04_analyze', '05_backend', '06_native', 'compiler']
parser = argparse.ArgumentParser(description=__doc__)
parser.add_argument('--results', type=Path, required=True)
args = parser.parse_args()
out = args.results.resolve()
out.mkdir(parents=True, exist_ok=False)
source = out / 'source'
source.mkdir()
for stage in STAGES:
    shutil.copytree(APP / stage, source / stage)
def php_string(value):
    return "'" + str(value).replace('\\', '\\\\').replace("'", "\\'") + "'"
driver = (APP / 'tests/bootstrap_driver.php.in').read_text()
for key, value in {
    '__REQUEST_FILE__': php_string('request.txt'),
    '__OUTPUT_FILE__': php_string('output.txt'),
    '__RUNTIME_CATALOG_DIR__': php_string(ROOT / 'runtime/metadata/modules'),
}.items():
    driver = driver.replace(key, value)
(source / 'main.php').write_text(driver)
(out / 'source_hashes.json').write_text(json.dumps({str(p.relative_to(source)): hashlib.sha256(p.read_bytes()).hexdigest() for p in sorted(source.rglob('*.php'))}, indent=2) + '\n')
(out / 'revision.txt').write_bytes(subprocess.check_output(['git', 'rev-parse', 'HEAD'], cwd=ROOT))
(out / 'candidate.diff').write_bytes(subprocess.check_output(['git', 'diff'], cwd=ROOT))
project = out / 'phpp'
with CommandRunner(out / 'logs', ROOT) as runner:
    def probe():
        runner.run('convert', ['/usr/bin/time', '-v', '-o', out / 'convert.time', 'php8.5', ROOT / 'tools/php_portability/convert.php', source, project, '--target=v0.2'], timeout=300)
        runner.run('framework', ['php8.5', ROOT / 'tools/php_portability/install_native_runtime.php', project, '--filesystem', '--os', '--json'])
        (project / 'prism.json').write_text(json.dumps({'entrypoint': 'main.phs', 'runtime': {'languages': {'php': {'profile': 'strict'}}, 'modules': ['compiler', 'filesystem', 'tasks', 'process', 'datetime', 'json']}}))
        phs = list(project.rglob('*.phs'))
        (out / 'input.json').write_text(json.dumps({'files': len(phs), 'bytes': sum(p.stat().st_size for p in phs)}, indent=2) + '\n')
        runner.run('bootstrap', ['/usr/bin/time', '-v', '-o', out / 'bootstrap.time', 'php8.5', '-d', 'opcache.enable_cli=0', '-d', 'xdebug.mode=off', '-d', 'memory_limit=4G', APP / 'tools/bootstrap_probe.php', APP, project, out / 'bootstrap.json'], timeout=900)
    run_tasks([Task('bootstrap', probe)], jobs=1)
