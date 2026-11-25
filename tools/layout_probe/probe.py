#!/usr/bin/env python3
"""Native-only probe driver. Recheck compiler/header/flag identity on every invocation."""
import argparse
import hashlib
import json
from pathlib import Path
import platform
import shutil
import subprocess
import tempfile


def sha(data):
    return hashlib.sha256(data).hexdigest()


def run(command):
    return subprocess.check_output(command, stderr=subprocess.PIPE)


def write(path, data):
    # Atomic per-file publication; a context mismatch detects an interrupted multi-file update.
    with tempfile.NamedTemporaryFile(dir=path.parent, delete=False) as stream:
        stream.write(data)
        temporary = Path(stream.name)
    temporary.replace(path)


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--cxx', required=True)
    parser.add_argument('--flag', action='append', default=[])
    parser.add_argument('--out', type=Path, required=True)
    parser.add_argument('--cross-compiling', choices=['TRUE', 'FALSE'], default='FALSE')
    args = parser.parse_args()
    if args.cross_compiling == 'TRUE' or platform.system() != 'Linux' or platform.machine() != 'x86_64':
        raise ValueError('G2 probe supports native x86_64 Linux only; cross-target execution is not supported')
    # Preserve argv[0]: resolving clang++ to clang changes the link driver's language mode.
    compiler = Path(shutil.which(args.cxx) or args.cxx).absolute()
    compiler.resolve(strict=True)
    flags = [*args.flag, '-std=c++23']
    triple = run([str(compiler), *flags, '-dumpmachine']).decode().strip()
    if not triple.startswith('x86_64-') or 'linux' not in triple:
        raise ValueError('cross-target compiler is not supported by the native probe')
    source = Path(__file__).with_name('probe.cpp')
    preprocessed = run([str(compiler), *flags, '-E', '-P', str(source)])
    configuration = {
        'target': triple, 'compiler': str(compiler),
        'compiler_version': run([str(compiler), '--version']).decode().strip(),
        'compiler_sha256': sha(compiler.read_bytes()), 'flags': flags,
        'probe_sha256': sha(source.read_bytes()),
        'preprocessed_sha256': sha(preprocessed),
        'driver_sha256': sha(Path(__file__).read_bytes()),
    }
    context_id = sha(json.dumps(configuration, sort_keys=True, separators=(',', ':')).encode())
    context = {'schema': 1, 'context_id': context_id, 'configuration': configuration}
    args.out.mkdir(parents=True, exist_ok=True)
    executable, facts_file = args.out / 'probe', args.out / 'facts.json'
    context_file, cache_file = args.out / 'context.json', args.out / 'cache.json'
    try:
        cache = json.loads(cache_file.read_bytes())
        if (json.loads(context_file.read_bytes()) == context and cache['context_id'] == context_id
                and cache['facts_sha256'] == sha(facts_file.read_bytes())
                and cache['executable_sha256'] == sha(executable.read_bytes())):
            print('Native probe facts are current')
            return
    except (OSError, ValueError, KeyError, TypeError):
        pass
    with tempfile.TemporaryDirectory(dir=args.out) as temporary:
        candidate = Path(temporary) / 'probe'
        run([str(compiler), *flags, f'-DSCPP_PROBE_CONTEXT_ID="{context_id}"', str(source), '-o', str(candidate)])
        facts = run([str(candidate)])
        decoded = json.loads(facts)
        if decoded['context_id'] != context_id or decoded['pointer_bytes'] != 8:
            raise ValueError('probe context or native pointer width mismatch')
        candidate.replace(executable)
    write(facts_file, facts)
    write(context_file, (json.dumps(context, sort_keys=True, indent=2) + '\n').encode())
    write(cache_file, (json.dumps({'context_id': context_id, 'facts_sha256': sha(facts),
                                  'executable_sha256': sha(executable.read_bytes())}, sort_keys=True) + '\n').encode())
    print('Generated native probe facts')


if __name__ == '__main__':
    try:
        main()
    except subprocess.CalledProcessError as error:
        raise SystemExit(error.stderr.decode(errors='replace') or str(error))
    except (OSError, ValueError) as error:
        raise SystemExit(str(error))
