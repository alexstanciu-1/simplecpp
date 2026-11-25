import json
from pathlib import Path
import runpy
import shutil
import sys
sys.dont_write_bytecode = True
root = Path('compiler/my-try').resolve()
api = runpy.run_path(str(root / 'tests/run.py'))
output = Path('/tmp/my-try-pch-runner-integration-20261003')
output.mkdir()
for suite, existing in [('s2s', '/tmp/my-try-pch-implementation-nullable-20261003/programs'),
                        ('control_flow', '/tmp/my-try-pch-implementation-control-20261003/programs')]:
    target = output / suite
    target.mkdir()
    fixtures = json.loads((Path(existing) / 'executions.json').read_text())
    for fixture in fixtures:
        source = Path(fixture['path'])
        shutil.copy2(source, target / source.name)
        fixture['path'] = str(target / source.name)
    (target / 'executions.json').write_text(json.dumps(fixtures))
(output / 'llvm').mkdir()
(output / 'llvm/executions.json').write_text('[]')
with api['CommandRunner'](output / 'logs', root.parents[1], php_executor='cli') as runner:
    print(api['verify_programs'](root, output, runner, 12))
