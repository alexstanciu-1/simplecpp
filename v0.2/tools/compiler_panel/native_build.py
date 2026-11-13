"""Shared Ninja build graph for the local panel and native-build experiments."""
from pathlib import Path
from contextlib import contextmanager
import hashlib
import shlex
import shutil
import sys
import tempfile

ROOT = Path(__file__).resolve().parents[2]
QUALIFICATION = Path(__file__).resolve().parents[1] / 'layout_probe/qualification.py'
sys.path.insert(0, str(QUALIFICATION.parent))
from qualification import qualify


class NinjaCommand(list):
    """Ninja argv plus the qualification inputs chosen by the graph writer."""
    def __init__(self, ninja, qualification):
        super().__init__([ninja, '-j', '4', '-v', '-f', 'build.ninja'])
        self.qualification = qualification


@contextmanager
def qualified_ninja(command, work):
    """Use an in-process hit. Cold, expired and PCH builds run the protected graph."""
    if not isinstance(command, NinjaCommand) or command.qualification is None:
        yield command
        return
    cxx, flags, cache, link_flags, probe_rule = command.qualification
    graph = (Path(work) / 'build.ninja').read_text()
    guard = 'build qualify-native: qualify force-qualification'
    # Experiments may instrument the compilation rules. Require the original probe command and gate.
    if probe_rule not in graph or guard not in graph.splitlines():
        yield command
        return
    hit = qualify(cxx, flags, cache, link_flags, cached_only=True, build_dir=work)
    if hit is None:
        yield command
        return
    # Keep the saved graph protected. Only this launcher uses the temporary graph and only for this run.
    with tempfile.NamedTemporaryFile(mode='w', suffix='.ninja') as invocation:
        invocation.write(graph.replace(guard + '\n', 'build qualify-native: phony\n', 1))
        invocation.flush()
        args = list(command)
        args[args.index('-f') + 1] = invocation.name
        yield args


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
    probe = [sys.executable, '-B', QUALIFICATION, '--cxx', cxx, '--cache', library.parent / 'native-qualification',
             *['--flag=' + str(flag) for flag in common[1:]], '--link-flag=--ld-path=' + mold]
    lines = ['ninja_required_version = 1.10',
             'rule qualify', '  command = ' + ' '.join(map(shell, probe)) + ' $probe_flags',
             'build force-qualification: phony', 'build qualify-native: qualify force-qualification',
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
        lines.append('build runtime.pch: pch runtime_pch.hpp || qualify-native')
    pch_path = str(cached_pch) if cached_pch is not None else 'runtime.pch'
    pch_flags = ['-include-pch', pch_path]
    if not validate_pch:
        pch_flags += ['-Xclang', '-fno-validate-pch']
    if pch and any(Path(unit).name != 'entry.cpp' for unit in units):
        lines.extend([f'build qualify-pch: qualify force-qualification | {ninja_path(pch_path)}',
                      '  probe_flags = ' + ' '.join(shell('--flag=' + flag) for flag in pch_flags)])
    objects = []
    for unit in units:
        obj = 'unit-' + hashlib.sha256(str(Path(unit).resolve()).encode()).hexdigest() + '.o'
        objects.append(obj)
        # The tiny entry adapter uses no runtime headers and does not benefit from this PCH.
        use_pch = pch and Path(unit).name != 'entry.cpp'
        dependency = f' | {ninja_path(pch_path)} || qualify-pch' if use_pch else ' || qualify-native'
        lines.append(f'build {obj}: compile {ninja_path(Path(unit).resolve())}{dependency}')
        lines.append('  pchflag = ' + (' '.join(map(shell, pch_flags)) if use_pch else ''))
    lines.extend([f'build program: link {" ".join(objects)} {ninja_path(library)}', 'default program'])
    graph = work / 'build.ninja'
    text = '\n'.join(lines) + '\n'
    if not graph.exists() or graph.read_text() != text:
        graph.write_text(text)
    inputs = (cxx, list(map(str, common[1:])), library.parent / 'native-qualification',
              ['--ld-path=' + mold], '\n'.join(lines[1:3]) + '\n')
    return NinjaCommand(ninja, None if pch else inputs)
