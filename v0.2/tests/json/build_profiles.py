#!/usr/bin/env python3
"""Source-only production module composition and reconfiguration proof (manual)."""
import json
from pathlib import Path
import shutil
import subprocess
import tempfile
source = Path(__file__).resolve().parents[2]
with tempfile.TemporaryDirectory(prefix='scpp-json-profiles-') as temporary:
    root = Path(temporary)/'v0.2'
    shutil.copytree(source, root, ignore=shutil.ignore_patterns('build', 'target', 'generated', '__pycache__', 'compiler', '.cargo', 'Cargo.toml', 'Cargo.lock', 'rust-toolchain.toml'))
    def run(*args):
        result = subprocess.run(args, cwd=root, capture_output=True, text=True)
        assert result.returncode == 0, result.stdout+result.stderr
        return result.stdout
    build = root/'build/clang'
    for dt, js, profile in [(False, True, '-json'), (True, True, '-datetime-json'), (True, False, '-datetime'), (False, False, ''), (False, True, '-json')]:
        run('cmake', '--preset', 'default', '-DBUILD_TESTING=OFF',
            '-DSCPP_ENABLE_DATETIME='+('ON' if dt else 'OFF'), '-DSCPP_ENABLE_JSON='+('ON' if js else 'OFF'))
        run('cmake', '--build', '--preset', 'default')
        targets = run('ninja', '-C', str(build), '-t', 'targets', 'all')
        assert ('scpp_json:' in targets) == js
        assert ('scpp_datetime:' in targets) == dt
        plan = json.loads((build/('generated'+profile)/'prepared.json').read_text())
        assert ('json' in plan) == js and ('datetime' in plan) == dt
        assert ('boxed' in plan) == js
        assert run(str(build/'scpp_runtime_demo')).strip() == 'Ada: 21; ratio=7'
        if js:
            assert run(str(build/'scpp_json_demo')).strip() == '{"name":"Ada","values":[null,false,42]}'
        if dt:
            assert run(str(build/'scpp_datetime_demo')).strip() == '2024-02-29T12:34:56Z'
        symbols = run('nm', '-C', str(build/'libscpp_runtime.a'))
        assert 'scpp::json::' not in symbols and 'scpp::detail::json::' not in symbols
        assert 'scpp::dt::' not in symbols and 'scpp::detail::datetime::' not in symbols
        before = {str(p):p.stat().st_mtime_ns for p in build.rglob('*.o')}
        log = run('cmake', '--build', '--preset', 'default')
        assert before == {str(p):p.stat().st_mtime_ns for p in build.rglob('*.o')}
        assert 'Generating ' not in log and 'Building CXX' not in log
        print('production profile passed:', profile or 'core', flush=True)
