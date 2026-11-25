"""V-E01/V-E03 focused host and isolated database acceptance; run on dev002."""
import argparse
import copy
import json
import sys
from dataclasses import asdict
from pathlib import Path
APP = Path(__file__).resolve().parents[1]
ROOT = APP.parents[1]
sys.path.insert(0, str(APP/'tools'))
from test_runner import CommandRunner, Task, run_tasks, TaskFailures
from verification_host import inspect_host, missing_requirements, STAGES


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--results', type=Path, required=True)
    out = parser.parse_args().results.resolve()
    out.mkdir(parents=True, exist_ok=False)
    with CommandRunner(out/'logs', ROOT, jobs=12) as runner:
        facts = inspect_host(runner, STAGES)
        (out/'host.json').write_text(json.dumps(facts, indent=2)+'\n')
        if facts['missing']:
            raise RuntimeError('\n'.join(facts['missing']))

        def preflight_controls():
            for name in ('ast', 'mbstring', 'pcntl_waitid'):
                broken = copy.deepcopy(facts)
                broken['php'][name] = False
                assert any(name in message for message in missing_requirements(broken, STAGES))
            broken = copy.deepcopy(facts)
            broken['php']['version_id'] = 80200
            assert any('PHP 8.4' in message for message in missing_requirements(broken, ['baseline']))
            broken = copy.deepcopy(facts)
            broken['connector'] = None
            assert any('Connector/C' in message for message in missing_requirements(broken, ['mysql']))
            for name in facts['tools']:
                broken = copy.deepcopy(facts)
                broken['tools'][name] = None
                assert any(name in message for message in missing_requirements(broken, STAGES))
            # A frontend-only host need not install the database or the v0.1 AST extension.
            assert missing_requirements({'php': {'mbstring': True}}, ['frontend']) == []
            runner.run('missing-php-cli', ['python3', APP/'tools/verification_host.py',
                       '--stages', 'frontend', '--php', '/nonexistent/scpp-test-php',
                       '--results', out/'missing-php'], expected=1)
            assert 'Selected PHP executable not found' in (out/'logs/missing-php-cli.stderr').read_text()
            return {'dependency_rejections': True, 'stage_isolation': True}

        def prelowered():
            folder = out/'prelowered'
            (folder/'source').mkdir(parents=True)
            (folder/'source/main.phs').write_text('function sum(int $a,int $b):int {return $a+$b;} echo sum(19,23),"\\n";\n')
            runner.run('publish', ['php', APP/'tests/verification_host_publish.php', folder])
            manifest = json.loads((folder/'published/.scpp-output.manifest').read_text())
            assert manifest['application_templates'] == 'forbidden'
            outputs = []
            for temperature in ('cold', 'warm'):
                result = json.loads(runner.run('build-'+temperature,
                    ['php', APP/'tools/build_cpp.php', folder/'published', folder/'cache'], timeout=300))
                assert runner.run('execute-'+temperature, [result['executable']]) == b'42\n'
                if temperature == 'cold':
                    assert result['compiled'] > 0 and result['linked'] == 1
                else:
                    assert result['compiled'] == result['linked'] == 0
                outputs.append(result)
            return outputs

        def mysql():
            build = out/'mysql-build'
            runner.run('mysql-configure', ['cmake', '-S', APP/'tests/fixtures/mysql_verification',
                       '-B', build, '-DSCPP_ROOT='+str(ROOT), '-DCMAKE_CXX_COMPILER=/usr/bin/clang++',
                       '-DCMAKE_CXX_FLAGS=-O0 -g0'], timeout=120)
            runner.run('mysql-build', ['cmake', '--build', build, '--parallel', '10', '--target',
                       'test_mysqli_connect', 'test_mysqli_fetch_exhaustion', 'mysql_fixture'], timeout=600)
            tests = build/'runtime/tests_runtime_native'
            runner.run('mysql-original-connect', [tests/'test_mysqli_connect'])
            runner.run('mysql-original-exhaustion', [tests/'test_mysqli_fetch_exhaustion'])
            for name, fail in [('database', False), ('cleanup-after-failure', True)]:
                command = ['python3', APP/'tests/mysql_isolated.py', '--results', out/name,
                           '--executable', build/'mysql_fixture']
                if fail:
                    command.append('--fail-after-ready')
                runner.run(name, command, expected=1 if fail else 0, timeout=180)
                lifecycle = json.loads((out/name/'lifecycle.json').read_text())
                assert lifecycle['ready'] and lifecycle['stopped'] and lifecycle['removed'], lifecycle
                assert lifecycle['passed'] == (not fail), lifecycle
                if fail:
                    assert 'Deliberate harness failure after readiness' in (out/'logs'/(name+'.stderr')).read_text()
            return {'original_tests': 2, 'real_provider': True, 'failure_cleanup': True}

        try:
            outcomes = run_tasks([Task('preflight-controls', preflight_controls),
                                  Task('default-prelowering', prelowered), Task('mysql-integration', mysql)])
        except TaskFailures as error:
            outcomes = error.outcomes
            (out/'summary.json').write_text(json.dumps({'passed': False, 'tasks': [asdict(x) for x in outcomes]}, indent=2)+'\n')
            raise
        (out/'summary.json').write_text(json.dumps({'passed': True, 'tasks': [asdict(x) for x in outcomes]}, indent=2)+'\n')

if __name__ == '__main__':
    main()
