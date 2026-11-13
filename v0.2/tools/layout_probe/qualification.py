"""Cache native C++ call-argument qualification for a specific toolchain and flag list."""
import argparse
import fcntl
import hashlib
import json
import os
from pathlib import Path
import shlex
import shutil
import subprocess
import tempfile
import time

from probe import sha, write

SOURCE = Path(__file__).with_name('argument_order.cpp')
WEEK = 7 * 24 * 60 * 60
CASES = {'divide', 'remainder', 'divide_zero', 'remainder_zero'}


def execute(command, **kwargs):
    result = subprocess.run(command, capture_output=True, check=False, **kwargs)
    if result.returncode:
        raise ValueError(f'{shlex.join(map(str, command))}: exit {result.returncode}: '
                         + result.stderr.decode(errors='replace').strip())
    return result


def file_hash(path):
    digest = hashlib.sha256()
    with path.open('rb') as stream:
        for chunk in iter(lambda: stream.read(1024 * 1024), b''):
            digest.update(chunk)
    return digest.hexdigest()


def compiler_components(compiler, flags):
    # Ask the actual driver: aliases and flags such as GCC -B can select a different frontend.
    plan = execute([compiler, *flags, '-###', '-c', SOURCE]).stderr.decode()
    paths = {compiler}
    for line in plan.splitlines():
        if not line.startswith(' '):
            continue
        command = shlex.split(line)
        if command and ('-cc1' in command or Path(command[0]).name == 'cc1plus'):
            frontend = Path(shutil.which(command[0]) or command[0]).absolute()
            paths.add(frontend)
            break
    else:
        raise ValueError(f'Cannot identify Clang or GCC C++ frontend for {compiler}')

    for executable in sorted(paths):
        result = subprocess.run(['ldd', executable], capture_output=True, env={**os.environ, 'LC_ALL': 'C'})
        libraries = (result.stdout + result.stderr).decode()
        if result.returncode and 'not a dynamic executable' not in libraries:
            raise ValueError(f'Cannot inspect {executable}: {libraries.strip()}')
        for line in libraries.splitlines():
            name, arrow, location = line.strip().partition(' => ')
            if arrow and name.startswith(('libclang', 'libLLVM')):
                paths.add(Path(location.rsplit(' (', 1)[0]))
    return {str(path): file_hash(path) for path in sorted(paths)}


def valid_traces(traces):
    return isinstance(traces, dict) and traces.keys() == CASES and all(
        trace in ('LR', 'RL') for trace in traces.values())


def qualify(compiler_name, flags, cache, link_flags=()):
    compiler = Path(shutil.which(compiler_name) or compiler_name).absolute()
    # Common Ninja/CMake invocations use absolute inputs and can share a record across build dirs.
    options = [*flags, *link_flags]
    extra = []
    fast = True
    args = iter(options)
    for option in args:
        if option in ('-I', '-include-pch'):
            path = next(args, '')
        elif option.startswith('--ld-path='):
            path = option.partition('=')[2]
        elif option == '-Xclang':
            fast &= next(args, '') == '-fno-validate-pch'
            continue
        elif option.startswith(('-std=', '-stdlib=')) or option in (
            '-O0', '-O1', '-O2', '-O3', '-Os', '-Oz', '-g', '-g0', '-DNDEBUG', '-Wall', '-Wextra', '-Wpedantic', '-Werror'
        ):
            continue
        else:
            # Unknown options can select inputs indirectly (e.g. response files).
            fast = False
            continue
        extra.append(os.path.abspath(path))
    relative = any(not os.path.isabs(option) for option in options if not option.startswith('-'))
    env_keys = ('PATH', 'CPATH', 'CPLUS_INCLUDE_PATH', 'C_INCLUDE_PATH', 'GCC_EXEC_PREFIX',
                'COMPILER_PATH', 'LIBRARY_PATH', 'LD_LIBRARY_PATH', 'LD_PRELOAD', 'SDKROOT', 'CCC_OVERRIDE_OPTIONS')
    env = {k: os.environ[k] for k in env_keys if k in os.environ}
    # Relative environment search paths are interpreted from the invocation directory too.
    relative |= any(not os.path.isabs(item) for k, v in env.items() if k not in ('CCC_OVERRIDE_OPTIONS', 'LD_PRELOAD')
                    for item in v.split(os.pathsep))
    fast &= not (env.get('LD_PRELOAD') or env.get('CCC_OVERRIDE_OPTIONS'))
    request = [str(compiler), str(SOURCE), options, len(flags), env, os.getcwd() if relative else None]
    slot = sha(json.dumps(request, sort_keys=True).encode())
    cache.mkdir(parents=True, exist_ok=True)
    index = cache / ('inputs-' + slot + '.json')
    with (cache / (slot + '.lock')).open('a') as lock:
        fcntl.flock(lock, fcntl.LOCK_EX)
        try:
            cached = json.loads(index.read_bytes())
            saved = json.loads((cache / (cached['key'] + '.json')).read_bytes())
            if (fast and 0 <= time.time() - saved['tested_at'] < WEEK and valid_traces(saved['traces'])
                    and sha(json.dumps(saved['context'], sort_keys=True).encode()) == cached['key']
                    and cached['inputs'] and all(stamp(path) == old for path, old in cached['inputs'].items())):
                return cached['key'], saved['traces'], 'cached'
        except (OSError, ValueError, KeyError, TypeError, AttributeError):
            pass

        with tempfile.TemporaryDirectory(dir=cache) as temporary:
            depfile = Path(temporary) / 'probe.d'
            preprocessed = execute([compiler, *flags, '-E', '-P', '-MD', '-MF', depfile, '-MT', 'probe', SOURCE]).stdout
            context = {
                'request': slot, 'compiler': str(compiler), 'components': compiler_components(compiler, flags),
                'version': execute([compiler, '--version']).stdout.decode().strip(),
                'target': execute([compiler, *flags, '-dumpmachine']).stdout.decode().strip(),
                'flags': flags, 'link_flags': list(link_flags), 'probe_sha256': file_hash(SOURCE), 'driver_sha256': file_hash(Path(__file__)),
                'preprocessed_sha256': sha(preprocessed),
            }
            key = sha(json.dumps(context, sort_keys=True).encode())
            deps = depfile.read_text().replace(chr(92) + chr(10), ' ').removeprefix('probe:')
            paths = {*shlex.split(deps.replace('$$', '$')), *context['components'], *extra, str(compiler), str(SOURCE), __file__}
            # Include/library search roots can legally be absent. Track their creation as a change.
            paths.update(item for k in ('CPATH', 'CPLUS_INCLUDE_PATH', 'C_INCLUDE_PATH', 'LD_LIBRARY_PATH', 'LIBRARY_PATH')
                         for item in env.get(k, '').split(os.pathsep) if item)
            inputs = {os.path.abspath(p): stamp(p) for p in paths}
            if not fast:
                try:
                    saved = json.loads((cache / (key + '.json')).read_bytes())
                    if (saved['context'] == context and 0 <= time.time() - saved['tested_at'] < WEEK
                            and valid_traces(saved['traces'])):
                        return key, saved['traces'], 'cached'
                except (OSError, ValueError, KeyError, TypeError):
                    pass
            binary = Path(temporary) / 'probe'
            execute([compiler, *flags, SOURCE, *link_flags, '-o', binary])
            output = execute([binary]).stdout.decode()
            lines = output.splitlines()
            traces = dict(line.split() for line in lines)
            if len(lines) != len(CASES) or not valid_traces(traces):
                raise ValueError(f'Invalid native argument observations: {output!r}')
        record = cache / (key + '.json')
        write(record, json.dumps(dict(context=context, tested_at=time.time(), traces=traces),
                                 sort_keys=True, indent=2).encode() + b'\n')
        write(index, json.dumps(dict(key=key, inputs=inputs)).encode())
        return key, traces, 'qualified'


def stamp(path):
    try:
        stat = os.stat(path)
    except FileNotFoundError:
        return None
    return [stat.st_dev, stat.st_ino, stat.st_size, stat.st_mtime_ns, stat.st_ctime_ns]


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--cxx', required=True)
    parser.add_argument('--flag', action='append', default=[])
    parser.add_argument('--link-flag', action='append', default=[])
    parser.add_argument('--cache', type=Path, required=True)
    args = parser.parse_args()
    key, traces, status = qualify(args.cxx, args.flag, args.cache, args.link_flag)
    print(f'Native argument probe {status}: {key} {json.dumps(traces, sort_keys=True)}')


if __name__ == '__main__':
    try:
        main()
    except (OSError, ValueError) as error:
        raise SystemExit(str(error))
