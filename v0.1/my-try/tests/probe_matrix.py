"""H09: exactly 39 generated collection-probe rows and bounded positive controls."""
import argparse
from collections import Counter
import hashlib
import json
from pathlib import Path
import re
import sys

APP = Path(__file__).resolve().parents[1]
ROOT = APP.parents[1]
sys.path.insert(0, str(APP / 'tools'))
from test_runner import CommandRunner, Task, run_tasks
from cpp_fixture import CppFixtureCompiler


def state_predicate(profile):
    profile = profile.removeprefix('mixed.')
    if profile == 'null':
        return 'v.is_null().native_value()'
    if profile.startswith('hash.'):
        size = 0 if profile == 'hash.empty' else 1
        extra = '' if size == 0 else ' && v.table_if()->at(scpp::string_t{"k"}).get_int().native_value() == 1'
        return f'v.table_if() == identity && v.table_if()->size() == {size}' + extra
    values = {'bool.false': ('bool', 'false'), 'bool.true': ('bool', 'true'),
              'int.zero': ('int', '0'), 'int.nonzero': ('int', '7'),
              'float.zero': ('float', '0.0'), 'float.nonzero': ('float', '3.5'),
              'string.empty': ('string', '""'), 'string.zero_string': ('string', '"0"'),
              'string.bool_false_literal': ('string', '"false"'),
              'string.bool_true_literal': ('string', '"true"'),
              'string.nonempty_nonzero_nonbool_literal': ('string', '"hello"')}
    kind, value = values[profile]
    return f'v.is_{kind}().native_value() && v.get_{kind}().native_value() == {value}'


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--results', required=True, type=Path)
    parser.add_argument('--jobs', type=int, default=12)
    args = parser.parse_args()
    out = args.results.resolve()
    out.mkdir(parents=True, exist_ok=False)
    generated = out / 'generated'
    outcomes = []
    with CommandRunner(out / 'logs', ROOT, jobs=args.jobs) as runner:
        runner.run('generate-39', ['php', ROOT / 'tools/operator_matrix/tests/probe_throw_rows_test.php', generated])
        # Existing emitter routing/expectation regressions; no whole-matrix execution.
        runner.run('emitter-regressions', ['php', ROOT / 'tools/operator_matrix/tests/emitter_regression_test.php'])
        seeds = json.loads((generated / 'selected-seeds.json').read_text())
        manifest = json.loads((generated / 'tests/php-matrix/_manifest.json').read_text())
        entries = {entry['seed_id']: entry for entry in manifest['entries']}
        assert len(entries) == len(manifest['entries']) == len(seeds) == 39
        cpp = CppFixtureCompiler(runner, ROOT, out, pch=True, runtime=True)

        def generate(name, source):
            path = out / (name + '.phs')
            path.write_text(source)
            payload = runner.run(name + '-prepare', ['php', APP / 'tests/probe_matrix.php', path])
            return json.loads(payload), hashlib.sha256(path.read_bytes()).hexdigest()

        def execute(name, source, observer):
            result, digest = generate(name, source)
            assert result['accepted'], result
            text = result['cpp'].replace('int main(int argc, char** argv)', 'int unused_entry(int argc, char** argv)')
            path = out / (name + '.cpp')
            path.write_text('#include <scpp/hash_t.hpp>\n' + text + '\n' + observer)
            exe = out / name
            cpp.compile(name + '-build', path, exe)
            runner.run(name + '-execute', [exe])
            return digest

        def check(index, seed):
            name = f'row-{index:02d}'
            entry = entries[seed['seed_id']]
            emitted = (generated / entry['source_path']).read_text()
            declaration = re.search(r'^\$lhs /\*\* ([^*]+) \*/ = (.*);$', emitted, re.M)
            call = re.search(r'^var_dump\((.*)\);$', emitted, re.M)
            assert declaration and call, emitted
            kind, literal = declaration.groups()
            expression = call[1]
            expected_expression = {'count_value': 'count($lhs)', 'empty_value': 'empty($lhs)',
                                   'empty_keyed': 'empty($lhs["k"])'}[seed['item_id']]
            assert expression == expected_expression
            record = {'row_id': seed['source_row_ids'][0], 'emitted_sha256': hashlib.sha256(emitted.encode()).hexdigest()}
            if kind != 'mixed':
                assert seed['item_id'] == 'empty_value' and kind in ('bool', 'int', 'float')
                result, digest = generate(name, f'$lhs {kind} = {literal}; {expression};')
                assert not result['accepted'] and result['stage'] == 'prepare' and result['empty_artifacts'], result
                assert 'empty requires' in result['diagnostic'], result
                record.update(stage='preparation-rejection', diagnostic=result['diagnostic'], source_sha256=digest)
            else:
                returns = 'int' if seed['item_id'] == 'count_value' else 'bool'
                source = (f'function make(): mixed {{ $lhs mixed = {literal}; return $lhs; }} '
                          f'function probe(mixed &$lhs): {returns} {{ return {expression}; }}')
                state = state_predicate(seed['operands']['lhs']['profile'])
                catch = ('catch (const std::runtime_error& error) { caught = std::string(error.what()).find("expects hash-compatible mixed_t") != std::string::npos; }'
                         if seed['item_id'] == 'count_value' else
                         'catch (const scpp::runtime_error& error) { caught = error.code() == "invalid_empty_operand"; }')
                observer = ('int main() { auto v = function_make(); auto identity = v.table_if(); '
                            f'if (!({state})) return 2; bool caught = false; '
                            f'try {{ (void)function_probe(v); }} {catch} '
                            f'return caught && ({state}) ? 0 : 1; }}\n')
                record.update(stage='runtime-rejection', source_sha256=execute(name, source, observer))
            record['passed'] = True
            (out / (name + '.json')).write_text(json.dumps(record, indent=2))
            outcomes.append(record)

        run_tasks([Task(str(i), lambda i=i, seed=seed: check(i, seed)) for i, seed in enumerate(seeds)], jobs=args.jobs)
        control = ('function control(): int { $v vector<int> = [1, 2]; $empty vector<int> = []; '
                   '$h mixed = {k: [1]}; $zero mixed = {}; '
                   'if (count($v) !== 2) { return 1; } if (count($h) !== 1) { return 2; } '
                   'if (empty($v)) { return 3; } if (!empty($empty)) { return 4; } '
                   'if (empty($h["k"])) { return 5; } if (!empty($zero)) { return 6; } '
                   'return count($h) === 1 ? 0 : 7; }')
        execute('positive-controls', control, 'int main() { return function_control().native_value(); }\n')
        counts = Counter(row['stage'] for row in outcomes)
        assert counts == {'preparation-rejection': 6, 'runtime-rejection': 33}, counts
        (out / 'summary.json').write_text(json.dumps({'passed': True, 'rows': len(outcomes),
            'counts': dict(counts), 'positive_controls': True, 'full_matrix_run': False}, indent=2) + '\n')


if __name__ == '__main__':
    main()
