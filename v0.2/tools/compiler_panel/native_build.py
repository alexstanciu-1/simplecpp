"""Shared Ninja build graph for the local panel and native-build experiments."""
from pathlib import Path
import hashlib
import shlex
import shutil

ROOT = Path(__file__).resolve().parents[2]


def ninja_path(path):
    return str(path).replace('$', '$$').replace(' ', '$ ').replace(':', '$:')


def shell(value):
    return shlex.quote(str(value)).replace('$', '$$')


def write_build(work, units, optimization='O2', pch=False, cached_pch=None, validate_pch=True):
    if optimization not in ('O0', 'O1', 'O2') or not isinstance(pch, bool):
        raise ValueError('Invalid native build options')
    cxx = shutil.which('clang++')
    mold = shutil.which('ld.mold') or shutil.which('mold')
    ninja = shutil.which('ninja')
    if not all((cxx, mold, ninja)):
        raise RuntimeError('Native builds require clang++, Ninja and mold on PATH')
    work = Path(work).resolve()
    include = ROOT / 'runtime/generated/public'
    library = ROOT / 'build/clang/libscpp_runtime.a'
    common = [cxx, '-' + optimization, '-g0', '-std=c++23', '-stdlib=libc++', '-Wall', '-Wextra', '-Werror', '-I', include]
    flags = ' '.join(map(shell, common))
    lines = ['ninja_required_version = 1.10',
             'rule compile', f'  command = {flags} $pchflag -MD -MF $out.d -c $in -o $out',
             '  depfile = $out.d', '  deps = gcc',
             'rule pch', f'  command = {flags} -x c++-header -MD -MF $out.d $in -o $out',
             '  depfile = $out.d', '  deps = gcc',
             'rule link', f'  command = {shell(cxx)} -g0 -stdlib=libc++ --ld-path={shell(mold)} $in -o $out']
    if pch and cached_pch is None:
        header = work / 'runtime_pch.hpp'
        text = '#include <scpp/output.hpp>\n#include <scpp/entry.hpp>\n'
        if not header.exists() or header.read_text() != text:
            header.write_text(text)
        lines.append('build runtime.pch: pch runtime_pch.hpp')
    objects = []
    for unit in units:
        obj = 'unit-' + hashlib.sha256(str(Path(unit).resolve()).encode()).hexdigest() + '.o'
        objects.append(obj)
        # The tiny entry adapter uses no runtime headers and does not benefit from this PCH.
        use_pch = pch and Path(unit).name != 'entry.cpp'
        pch_path = str(cached_pch) if cached_pch is not None else 'runtime.pch'
        lines.append(f'build {obj}: compile {ninja_path(Path(unit).resolve())}' + (' | ' + ninja_path(pch_path) if use_pch else ''))
        lines.append('  pchflag = ' + ('-include-pch ' + shell(pch_path) + (' -Xclang -fno-validate-pch' if not validate_pch else '') if use_pch else ''))
    lines.extend([f'build program: link {" ".join(objects)} {ninja_path(library)}', 'default program'])
    graph = work / 'build.ninja'
    text = '\n'.join(lines) + '\n'
    if not graph.exists() or graph.read_text() != text:
        graph.write_text(text)
    return [ninja, '-j', '4', '-v', '-f', 'build.ninja']
