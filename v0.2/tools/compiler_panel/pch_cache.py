"""Persistent runtime-only PCH, with Ninja dependency validation and inter-process exclusion."""
from contextlib import contextmanager
import fcntl
import hashlib
import json
import os
from pathlib import Path
import shutil

from native_build import ROOT, write_build


def signature(optimization):
    compiler = Path(shutil.which('clang++') or 'clang++').resolve(strict=True)
    stat = compiler.stat()
    environment = {name: os.environ.get(name, '') for name in
                   ('CPATH', 'CPLUS_INCLUDE_PATH', 'C_INCLUDE_PATH', 'SDKROOT', 'MACOSX_DEPLOYMENT_TARGET')}
    return {'compiler': str(compiler), 'compiler_identity': [stat.st_ino, stat.st_size, stat.st_mtime_ns],
            'optimization': optimization, 'environment': environment, 'root': str(ROOT)}


def clear_application_outputs(directory):
    # Fixed object names avoid a separate output identity for every request.
    for pattern in ('unit-*.o', 'unit-*.o.d', 'program'):
        for path in directory.glob(pattern):
            path.unlink(missing_ok=True)


@contextmanager
def runtime_pch(cache_root, work, optimization):
    probe = Path(work) / 'pch-plan'
    probe.mkdir()
    write_build(probe, [], optimization, True)
    graph = (probe / 'build.ninja').read_text()
    header = (probe / 'runtime_pch.hpp').read_text()
    identity = {**signature(optimization), 'layout': 2, 'graph': graph, 'header': header}
    key = hashlib.sha256(json.dumps(identity, sort_keys=True).encode()).hexdigest()
    directory = Path(cache_root) / key
    directory.mkdir(parents=True, exist_ok=True, mode=0o700)
    with (directory / 'cache.lock').open('a') as lock:
        fcntl.flock(lock, fcntl.LOCK_EX)
        (directory / 'identity.json').write_text(json.dumps(identity, indent=2))
        artifact = directory / 'runtime.pch'
        before = artifact.stat().st_mtime_ns if artifact.exists() else None
        clear_application_outputs(directory)
        report = {'status': 'pending', 'key': key, 'path': str(artifact), 'dependency_check': 'integrated into application Ninja invocation'}
        try:
            yield directory, report
        finally:
            if not report.pop('success', False) or not artifact.exists():
                artifact.unlink(missing_ok=True)
                report['status'] = 'failed'
            else:
                report['status'] = 'reused' if before == artifact.stat().st_mtime_ns else ('rebuilt' if before is not None else 'built')
            clear_application_outputs(directory)
