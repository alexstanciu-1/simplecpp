"""Exercise production call consumers with strong and observed slots, without changing the checkout."""
from pathlib import Path
import os
import shutil
import subprocess
import tempfile

from consumer_pilot import HERE, ROOT, execute, run

with tempfile.TemporaryDirectory() as temp:
    work = Path(temp)
    reference = None
    for mode in ('strong', 'observed'):
        directory = work / mode
        compiler = directory / 'compiler'
        shutil.copytree(ROOT / 'compiler', compiler)
        for name in ('runtime', 'tools'):
            (directory / name).symlink_to(ROOT / name, target_is_directory=True)
        if mode == 'observed':
            subprocess.run(['patch', '--fuzz=0', '--batch', '-p1', '-i', HERE / 'current_slot.patch'],
                           cwd=compiler, check=True)
        for check in ('functions', 'declaration_facts', 'globals', 'imports', 'async'):
            subprocess.run(['php', compiler / 'tests' / (check + '.php')], check=True,
                           env={**os.environ, 'XDEBUG_MODE': 'off'})
        out = directory / 'out'
        # Both hosts see the same absolute application paths, including in diagnostics.
        app = work / 'app'
        if app.exists():
            shutil.rmtree(app)
        rows = run(compiler, app, out, 'verify')
        execute(out, directory / 'app-native')
        if reference is None:
            reference = rows
        elif rows != reference:
            raise RuntimeError(f'Differing phases: {[key for key in rows if rows[key] != reference[key]]}')
        print(mode, ', '.join(rows), flush=True)
