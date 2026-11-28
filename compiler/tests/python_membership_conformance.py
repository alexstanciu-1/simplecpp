"""Compare Python-flavor membership against the host Python interpreter. Exit 1 on a mismatch."""
from source_suffix import source_suffix
import ast
import json
import operator
import subprocess
import sys
import tempfile
from pathlib import Path

from exceptions_native import fresh_build
from extends_native import ROOT, runtime
from parent_methods_native import write_app


# Share the exact literal spellings between both frontends. The annotation only
# selects the representation under test in the compiled program.
CASES = [
    ('[7]', '7', 'vector<int>'),
    ('[7]', '0', 'vector<int>'),
    ('[1, 2]', '2.0', 'vector<int>'),
    ('[9007199254740993]', '9007199254740992.0', 'vector<int>'),
    ('[1]', 'True', 'vector<int>'),
    ('{0: 7}', '7', 'hash<int,int>'),
    ('{0: 7}', '0', 'hash<int,int>'),
    ('{"a": "b"}', '"b"', 'hash<string,string>'),
    ('{1: "x"}', '1.0', 'hash<string,int>'),
    ('{1: "x"}', '"1"', 'hash<string,int>'),
    ('"abcd"', '"bc"', 'string'),
    ('"abcd"', '""', 'string'),
    ('42', '1', 'int'),
]


def reference(container, needle, negate):
    try:
        found = operator.contains(ast.literal_eval(container), ast.literal_eval(needle))
    except TypeError:
        return 'TypeError'
    return str(not found if negate else found)


def executable(work, units, library):
    program = work / 'program'
    subprocess.run(['clang++', '-std=c++23', '-I', ROOT / 'runtime/generated/public',
                    *units, library, '-o', program], check=True)
    return subprocess.run([program], capture_output=True, text=True, timeout=10)


def compiled_case(work, source, library):
    write_app(work/'app', 'py', {'main': source})
    bundle = work/'bundle'
    result = fresh_build(work/'app', bundle)
    if 'error' in result:
        return 'compile rejection: ' + result['error'].split('main.py:')[-1]
    units = json.loads((bundle/'manifest.json').read_text())['translation_units']
    run = executable(work, [bundle/unit for unit in units], library)
    if run.stdout or run.stderr or run.returncode not in (8, 9, 10):
        return f'native exit {run.returncode}: {run.stdout!r} {run.stderr!r}'
    return {8: 'True', 9: 'False', 10: 'TypeError'}[run.returncode]


def boxed_storage(work, library):
    # Exercise the exact source-key adapter used by generated code. Both public
    # box families also expose raw key APIs with different semantics.
    source = work/'storage.cpp'
    source.write_text("""
#include <scpp/mixed.hpp>
#include <scpp/dynamic.hpp>
#include <cstdio>

template<class Box> void observe(const char* family) {
    auto appended = Box::container();
    appended.append(Box::from_integer(scpp::int64{7}));
    auto keyed = Box::container();
    keyed.set(scpp::int64{0}, Box::from_integer(scpp::int64{7}));
    auto keys = Box::container();
    keys.source_set(scpp::boxed_key(scpp::int64{1}), Box::from_integer(scpp::int64{7}));
    keys.source_set(scpp::boxed_key(scpp::string{"1"}), Box::from_integer(scpp::int64{8}));
    std::printf("%s: append/set(0) same kind=%d, packed=%d/%d, distinct keys after 1 and string 1=%lld\\n",
        family, appended.kind() == keyed.kind(), int(bool(appended.is_packed_container())),
        int(bool(keyed.is_packed_container())), static_cast<long long>(keys.container_size()));
}
int main() { observe<scpp::mixed>("mixed"); observe<scpp::dynamic>("dynamic"); }
""")
    run = executable(work, [source], library)
    run.check_returncode()
    print(run.stdout, end='')


def peer_mapping(work, library, family, flavor):
    # No Python tag crosses this boundary. Packed key zero still denotes a mapping.
    case = work/f'{family}-{flavor}'
    case.mkdir()
    source = {
        'cpp': f'{family} produce() {{ {family} v = []; v[0] = 7; return v; }}',
        'php': f'function produce(): {family} {{ $v {family} = []; $v[0] = 7; return $v; }}',
        'js': f'function produce(): {family} {{ let v: {family} = []; v[0] = 7; return v; }}',
    }[flavor]
    body = f"""receiver: {family} = produce()
seq: {family} = [7]
mapping: {family} = {{0: 7, "1": 9}}
saved: {family} = seq
if 7 not in saved or 0 in saved or 7 in mapping:
    return 9
if "1" not in mapping or 1 in mapping:
    return 9
return 8 if 0 in receiver and 7 not in receiver else 9"""
    write_app(case/'app', 'py', {'main': body})
    (case/'app'/f'provider.{source_suffix(flavor)}').write_text(source)
    result = fresh_build(case/'app', case/'bundle')
    assert 'error' not in result, result
    manifest = json.loads((case/'bundle/manifest.json').read_text())
    run = executable(case, [case/'bundle'/unit for unit in manifest['translation_units']], library)
    assert (run.returncode, run.stdout, run.stderr) == (8, '', ''), run


def main():
    matched = total = 0
    with tempfile.TemporaryDirectory(prefix='scpp-python-membership-') as directory:
        work = Path(directory)
        library = runtime(work, 'clang++', [ROOT/'runtime'/name for name in (
            'generated/src/hash.cpp', 'generated/src/boxed.cpp', 'src/hash/packed_key.cpp',
            'src/boxed/boxed_value.cpp', 'src/boxed/boxed_conversion.cpp')])
        boxed_storage(work, library)
        for flavor in ('cpp', 'php', 'js'):
            for family in ('mixed', 'dynamic'):
                peer_mapping(work, library, family, flavor)
        for container, needle, typed in CASES:
            for family in (typed, 'mixed', 'dynamic'):
                for negate in (False, True):
                    operation = 'not in' if negate else 'in'
                    expected = reference(container, needle, negate)
                    body = f'return 8 if {needle} {operation} receiver else 9'
                    if expected == 'TypeError':
                        body = f'try:\n    {body}\nexcept TypeError as error:\n    return 10'
                    case = work/f'case-{total}'
                    case.mkdir()
                    actual = compiled_case(case, f'receiver: {family} = {container}\n{body}', library)
                    print(f'{family}: {needle} {operation} {container}: Python {expected}, compiler {actual}')
                    matched += actual == expected
                    total += 1
    print(f'{matched}/{total} membership results match Python {sys.version.split()[0]}')
    return int(matched != total)


if __name__ == '__main__':
    sys.exit(main())
