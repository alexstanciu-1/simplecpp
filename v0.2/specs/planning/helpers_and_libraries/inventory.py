"""Reconcile pinned donor spellings with a generated v0.2 compiler and runtime publication."""
import argparse
import csv
import json
import re
import subprocess
from collections import defaultdict
from pathlib import Path

HERE = Path(__file__).resolve().parent
ROOT = HERE.parents[3]
RELEASE = 'd2f63e37e'
MY_TRY = '624ab8a2d'


def git(*args):
    return subprocess.check_output(['git', *args], cwd=ROOT, text=True)


def donor(rev, path):
    return json.loads(git('show', f'{rev}:{path}'))


def registry_keys(path):
    text = path.read_text().split('final class runtime_functions\n', 1)[1]
    arms = text.split('return match ($key) {', 1)[1].split('default => null', 1)[0]
    result = set()
    for line in arms.splitlines():
        if not line.strip():
            continue
        keys, _ = line.split(' => ', 1)
        labels = re.findall(r"'([^']+)'", keys)
        assert labels and all(':' in key and key not in result for key in labels), line
        result.update(labels)
    return result


def write(path, headers, rows):
    with path.open('w', newline='') as stream:
        writer = csv.writer(stream, delimiter='\t', lineterminator='\n')
        writer.writerow(headers)
        for row in rows:
            writer.writerow([cell if cell != '' else '—' for cell in row])


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('registry', type=Path)
    parser.add_argument('prepared', type=Path)
    parser.add_argument('--output', type=Path, default=HERE)
    args = parser.parse_args()
    args.output.mkdir(exist_ok=True, parents=True)
    maps = {mode: donor(RELEASE, f'generators/php/specs/php_runtime_symbols_{mode}.json')['php_runtime_symbol_targets']
            for mode in ('legacy', 'strict')}
    contracts = donor(RELEASE, 'generators/php/specs/php_runtime_symbol_contracts_strict.json')
    ids, families, cpp_targets = defaultdict(list), defaultdict(set), defaultdict(set)
    entities = []
    for file in git('ls-tree', '--name-only', MY_TRY + ':runtime/metadata/modules').splitlines():
        data = donor(MY_TRY, 'runtime/metadata/modules/' + file)
        bindings = {r['id']: r for r in data['cpp']}
        for kind in ('types', 'properties', 'constants'):
            for row in data.get(kind, []):
                entities.append(['my-try', data['module'], kind, row['name'], json.dumps(row, separators=(',', ':'))])
        for row in data['functions']:
            symbol = bindings[row['id']].get('symbol', '')
            cpp_targets[row['name']].add(symbol.removeprefix('scpp::'))
            ids[row['name']].append(row['id'])
            families[row['name']].add(data['module'])

    # STAN class members are outside the v0.1 free-function maps. Retain the
    # owner and signature, including profile-specific differences.
    stubs = defaultdict(dict)
    for mode, ext in [('legacy', 'php'), ('strict', 'phs')]:
        namespace = owner = ''
        path = f'runtime/generated/stan/runtime_symbols_{mode}.{ext}'
        for line in git('show', RELEASE + ':' + path).splitlines():
            if line.startswith('function '):
                name = line.split('(', 1)[0].split()[1]
                stubs[name][mode] = line.removesuffix(' {}').removeprefix('function ')
            elif line.startswith('namespace '):
                namespace = line.split()[1].rstrip(';')
            elif line.startswith('class '):
                owner = line.split()[1]
                entities.append([mode, namespace, 'class', owner, line])
            elif owner and line.startswith('}'):
                owner = ''
            elif owner and line.strip().startswith('public '):
                signature = line.strip().removesuffix(' {}')
                entities.append([mode, namespace, 'member', owner, signature])
    write(args.output / 'donor_entities.tsv', ['source', 'module or namespace', 'kind', 'owner or name', 'donor declaration'], sorted(entities))

    # Native targets can differ even when the historical source name is identical.
    plan = json.loads(args.prepared.read_bytes())
    native = defaultdict(list)
    for family in ['datetime', 'json']:
        for op in plan[family]['operations']:
            native[plan[family]['namespace'].removeprefix('scpp::') + '::' + op['name']].append(
                op.get('id', op['name']))
    keys = defaultdict(list)
    for key in sorted(registry_keys(args.registry)):
        keys[key.split(':')[-1]].append(key)
    intrinsic = {
        name: 'v0.2/compiler/04_analyze/prepare/prepare.php'
        for name in ('count', 'empty', 'hash_contains', 'hash_find', 'hash_get_or_null', 'mt_join',
                     'async_wait', 'enum_from_value', 'enum_try_from_value', 'enum_value', 'enum_name')
    }
    intrinsic.update({'isset': 'v0.2/compiler/03_parse/parser.php', 'unset': 'v0.2/compiler/03_parse/parser.php',
                      'make': 'v0.2/compiler/types/exception.php', 'get_message': 'v0.2/compiler/types/exception.php', 'is_empty': 'v0.2/compiler/types/struct.php'})

    # Do not silently exclude documented release APIs missing from the free-function maps.
    specs = {}
    for path in git('ls-tree', '-r', '--name-only', RELEASE, 'specs/builtins').splitlines():
        p = Path(path)
        if len(p.parts) == 4 and p.stem not in {'README', 'examples', 'first_pass', 'file_locks', 'strict_io', 'mysqli', 'token_buffer'}:
            specs[p.stem] = path
    pilot = {'string_byte_at', 'string_byte_slice_equals', 'file_get_contents'}
    names = sorted(set().union(*maps.values(), ids, stubs, specs, keys, intrinsic))
    rows = []
    for name in names:
        legacy, strict = (maps[m].get(name, '') for m in ('legacy', 'strict'))
        groups = sorted(families.get(name, []))
        source = keys.get(name, [])
        targets = sorted({op for target in (cpp_targets[name] | {strict, legacy}) for op in native.get(target, [])})
        contract = contracts['symbol_contracts'].get(name, contracts['default_symbol_contract'])
        evidence = specs.get(name, '') or (contract.get('contract_authority_source', '') if strict else '')
        if evidence == 'default_missing_contract':
            evidence = ''
        if source:
            state = 'registry spelling'
        elif name in intrinsic:
            state = 'compiler path'
        elif name in pilot:
            state = 'pilot only'
        elif targets:
            state = 'native target'
        else:
            state = 'review'
        group = ', '.join(groups) or (Path(evidence).parent.name if evidence.startswith('specs/builtins/') else '')
        if not group:
            group = (strict or legacy).split('::')[0] if (strict or legacy) else (
                'v0.1 shallow stub' if name in stubs else 'v0.2')
        rows.append([name, group, state, ' '.join(source), intrinsic.get(name, ''), ' '.join(targets),
                     legacy, strict, ' '.join(sorted(ids.get(name, []))), evidence, stubs[name].get('legacy', ''), stubs[name].get('strict', '')])
    write(args.output / 'intake.tsv',
          ['spelling', 'donor family or target prefix', 'evidence level', 'v0.2 registry keys',
           'other compiler evidence', 'v0.2 native operation IDs (target match)', 'legacy target', 'strict target',
           'my-try callable IDs', 'donor contract evidence', 'legacy shallow signature', 'strict shallow signature'], rows)


if __name__ == '__main__':
    main()
