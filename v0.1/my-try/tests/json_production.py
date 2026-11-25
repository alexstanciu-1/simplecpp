"""JSON value/document linkage via published module requirements, without injected sources."""
import argparse
import json
from pathlib import Path
import shutil
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
    catalogs = out / 'catalogs'
    shutil.copytree(ROOT / 'runtime/metadata/modules', catalogs)
    source = out / 'source'
    source.mkdir()
    shutil.copyfile(APP / 'tests/fixtures/json_production.phs', source / 'main.phs')
    catalog = catalogs / 'json.json'
    fixed = json.loads(catalog.read_text())
    assert fixed['cpp_build']['sources'] == ['modules/json/document.cpp', 'modules/json/json.cpp']
    old = json.loads(catalog.read_text())
    old['cpp_build']['sources'].remove('modules/json/json.cpp')
    published = out / 'published'
    observations = []
    with CommandRunner(out / 'logs', ROOT, jobs=12) as runner:
        def proof():
            def publish(name, metadata):
                catalog.write_text(json.dumps(metadata))
                runner.run(name + '-publish', ['php', APP / 'tests/fixtures/runtime_build_publish.php',
                    catalogs, source, published, 'json'], cli_reason='Reload metadata and republish')
                manifest = json.loads((published / '.scpp-output.manifest').read_text())
                (out / (name + '-manifest.json')).write_text(json.dumps(manifest, indent=2))
                return manifest['runtime_build']['sources']

            def build(name, cache, expected=0):
                payload = runner.run(name, ['php', APP / 'tests/fixtures/native_cache_driver.php',
                    published, cache, ROOT / 'runtime/include', '/usr/bin/clang++'],
                    expected=expected, timeout=300, cli_reason='Production native build')
                if expected:
                    error = (out / 'logs' / (name + '.stderr')).read_text()
                    assert 'undefined reference' in error and 'decode_dynamic' in error and 'decode_mixed' in error, error
                    return
                result = json.loads(payload)
                actual = runner.run(name + '-execute', [result['executable']])
                assert actual == b'{"n":7}|{"n":9}|array:17', actual
                observations.append(dict(name=name, **result))
                (out / 'observations.json').write_text(json.dumps(observations, indent=2))
                return result

            before = publish('missing-provider', old)
            build('missing-provider', out / 'transition-cache', expected=1)
            after = publish('fixed', fixed)
            assert after == before + ['modules/json/json.cpp'], (before, after)
            # The native consumer must use the publication, not reopen catalogs.
            catalog.rename(catalogs / 'json.saved')
            transitioned = build('changed-requirements', out / 'transition-cache')
            assert transitioned['compiled'] == 1 and transitioned['linked'] == 1, transitioned
            cold = build('cold', out / 'fresh-cache')
            assert cold['compiled'] > 1 and cold['pch_built'] == 1 and cold['linked'] == 1, cold
            warm = build('warm', out / 'fresh-cache')
            assert (warm['compiled'], warm['pch_built'], warm['linked']) == (0, 0, 0), warm
        run_tasks([Task('json-production', proof)])
    (out / 'summary.json').write_text(json.dumps(dict(passed=True, observations=observations,
        manual_provider_injection=False, runtime_snapshot=False, native_compiler_rebuilt=False), indent=2) + '\n')


if __name__ == '__main__':
    main()
