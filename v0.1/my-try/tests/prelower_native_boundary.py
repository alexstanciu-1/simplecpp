#!/usr/bin/env python3
"""Provider closure, PCH rejection, native readiness and cache recovery."""
import argparse
import json
from pathlib import Path
import sys

APP = Path(__file__).resolve().parents[1]
ROOT = APP.parents[1]
sys.path.insert(0, str(APP / 'tools'))
from test_runner import CommandRunner, Task, run_tasks


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--results', type=Path, required=True)
    out = parser.parse_args().results.resolve()
    out.mkdir(parents=True, exist_ok=False)
    runtime = out / 'runtime'
    runtime.mkdir()
    (runtime / 'provider.hpp').write_text('#include "nested.hpp"\ninline int provider_value(){ return 0; }\n')
    clean = ('// template<class T> is only a comment\n'
             'inline const char* text = R"(template<class T>)";\n'
             '#if 0\ntemplate<class T> struct Inactive {};\n#endif\n')
    nested = runtime / 'nested.hpp'
    nested.write_text(clean)
    published = out / 'published'
    cache = out / 'cache'
    with CommandRunner(out / 'logs', ROOT, jobs=12) as runner:
        def proof():
            def publish(mode, label=None, entry='entry.cpp'):
                runner.run('publish-' + (label or mode), ['php', APP / 'tests/fixtures/prelower_native_boundary.php',
                           published, mode, entry])

            def build(name, pch=False, expected=0):
                command = ['php', APP / 'tests/fixtures/native_cache_driver.php',
                           published, cache, runtime, '/usr/bin/clang++']
                if not pch:
                    command.append('--no-pch')
                else:
                    command.append('/usr/bin/ld')
                command.extend(['0', '4'])
                result = runner.run(name, command, expected=expected, cli_reason='Native build')
                if expected:
                    assert (cache / '.scpp-native.status').read_text() == 'pending\n'
                    return (out / 'logs' / (name + '.stderr')).read_text()
                return json.loads(result)

            publish('strict')
            assert 'runtime PCH is incompatible' in build('pch-reject', pch=True, expected=1)
            cold = build('cold')
            assert cold['compiled'] == 2 and cold['linked'] == 1, cold
            runner.run('execute', [cold['executable']])
            warm = build('warm')
            assert warm['compiled'] == warm['linked'] == 0, warm

            # Fresh transitive discovery must invalidate a previously accepted object.
            nested.write_text(clean + '#define DECL template<class T>\nDECL struct Leaked {};\n')
            assert 'template token in application input' in build('provider-reject', expected=1)
            nested.write_text(clean)
            repaired = build('repaired')
            assert repaired['compiled'] == repaired['linked'] == 0, repaired

            # An ordinary-mode object must not satisfy the strict checked-object key.
            nested.write_text('template<class T> struct Leaked {};\n')
            publish('ordinary')
            ordinary = build('ordinary')
            publish('strict', 'strict-again')
            assert 'template token in application input' in build('ordinary-cache-reject', expected=1)
            nested.write_text(clean)
            final = build('final-repair')
            assert final['compiled'] == final['linked'] == 0, final
            publish('strict', 'unknown-unit', 'helpers/entry.cpp')
            assert 'unclassified application translation unit' in build('unknown-unit', expected=1)
            publish('strict', 'missing-entry', 'specializations/entry.cpp')
            assert 'missing application entry' in build('missing-entry', expected=1)
            (out / 'result.json').write_text(json.dumps(dict(cold=cold, warm=warm,
                repaired=repaired, ordinary=ordinary, final=final), indent=2) + '\n')
        def ordinary_pch():
            source = out / 'ordinary-source'
            source.mkdir()
            (source / 'main.phs').write_text('return 0;')
            target = out / 'ordinary-published'
            runner.run('ordinary-publish', ['php', APP / 'main.php', '--s2s', source, '--no-prelower-templates',
                       '--output', target], cli_reason='Publication CLI contract')
            command = ['php', APP / 'tests/fixtures/native_cache_driver.php', target,
                       out / 'ordinary-cache', ROOT / 'runtime/include', '/usr/bin/clang++', '/usr/bin/ld', '0', '4']
            built = json.loads(runner.run('ordinary-pch', command, cli_reason='Native build'))
            assert built['pch_built'] == 1, built
            runner.run('ordinary-execute', [built['executable']])
            warm = json.loads(runner.run('ordinary-pch-warm', command, cli_reason='Native build'))
            assert warm['compiled'] == warm['pch_built'] == warm['linked'] == 0, warm
        run_tasks([Task('provider-boundary', proof), Task('ordinary-pch', ordinary_pch)])


if __name__ == '__main__':
    main()
