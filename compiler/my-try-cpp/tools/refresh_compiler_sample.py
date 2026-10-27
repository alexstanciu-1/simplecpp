"""Convert current my-try sources into a persistent PHS input sample; no compilation."""
import hashlib
import json
import os
from pathlib import Path
import shutil
import subprocess
import sys
import tempfile

PROJECT = Path(__file__).resolve().parents[1]
ROOT = PROJECT.parents[1]
APP = ROOT / 'compiler/my-try'
sys.path.insert(0, str(APP / 'tools'))
from test_runner import CommandRunner, Task, run_tasks

STAGES = ('01_prepare_inputs', '02_tokenize', '03_parse', '04_analyze',
          '05_backend', '06_native', 'compiler')


def main():
    output = PROJECT / 'samples/my-try'
    output.mkdir(parents=True, exist_ok=True)
    project = output / 'phpp'
    php = shutil.which('php8.5') or shutil.which('php')
    if php is None:
        raise RuntimeError('PHP is required for conversion')
    with tempfile.TemporaryDirectory(prefix='my-try-sample-') as temporary:
        source = Path(temporary) / 'source'
        source.mkdir()
        for stage in STAGES:
            shutil.copytree(APP / stage, source / stage)
        driver = (APP / 'tests/bootstrap_driver.php.in').read_text()
        # Resolve catalogs relative to this sample when executed from its project root.
        catalog_path = os.path.relpath(ROOT / 'runtime/metadata/modules', project)
        for key, value in {
            '__REQUEST_FILE__': 'request.txt',
            '__OUTPUT_FILE__': 'output.txt',
            '__RUNTIME_CATALOG_DIR__': catalog_path,
        }.items():
            driver = driver.replace(key, "'" + value + "'")
        (source / 'main.php').write_text(driver)
        hashes = {str(p.relative_to(source)): hashlib.sha256(p.read_bytes()).hexdigest()
                  for p in sorted(source.rglob('*.php'))}
        with CommandRunner(output / 'logs', ROOT) as runner:
            def convert():
                runner.run('convert', [php, ROOT / 'tools/php_portability/convert.php',
                                      source, project, '--target=v0.2', '--stats'], timeout=300)
                runner.run('framework', [php, ROOT / 'tools/php_portability/install_native_runtime.php',
                                        project, '--filesystem', '--os', '--json'])
            run_tasks([Task('convert-compiler-sample', convert)], jobs=1)
        manifest = {'entrypoint': 'main.phs', 'runtime': {
            'languages': {'php': {'profile': 'strict'}},
            'modules': ['compiler', 'filesystem', 'tasks', 'process', 'datetime', 'json']}}
        (project / 'prism.json').write_text(json.dumps(manifest, indent=2) + '\n')
        files = sorted(project.rglob('*.phs'))
        assert (project / catalog_path).resolve() == ROOT / 'runtime/metadata/modules'
        metrics = {'files': len(files), 'bytes': sum(p.stat().st_size for p in files),
                   'largest_file_bytes': max(p.stat().st_size for p in files)}
        record = {'source': 'compiler/my-try', 'target': 'v0.2',
                  'revision': subprocess.check_output(['git', 'rev-parse', 'HEAD'], cwd=ROOT, text=True).strip(),
                  'validation': 'conversion and framework assembly only; no semantic/native build',
                  'source_sha256': hashes, 'metrics': metrics}
        (output / 'provenance.json').write_text(json.dumps(record, indent=2) + '\n')
        print(json.dumps(metrics))


if __name__ == '__main__':
    main()
