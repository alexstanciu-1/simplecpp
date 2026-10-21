"""Stage-specific verification requirements; no package installation or service changes."""
import argparse
import json
import shutil
from pathlib import Path
from test_runner import CommandRunner

STAGES = ('baseline', 'frontend', 'native', 'mysql')
PHP_PROBE = '''echo json_encode(['version'=>PHP_VERSION,'version_id'=>PHP_VERSION_ID,
'ast'=>extension_loaded('ast'),'mbstring'=>extension_loaded('mbstring'),
'pcntl_waitid'=>function_exists('pcntl_waitid')], JSON_THROW_ON_ERROR);'''


def missing_requirements(facts, stages):
    missing = []
    php = facts.get('php', {})
    if 'baseline' in stages:
        if php.get('version_id', 0) < 80400:
            missing.append('baseline: PHP 8.4+ required')
        if not php.get('ast'):
            missing.append('baseline: install/enable ext-ast for the selected PHP')
    if 'frontend' in stages and not php.get('mbstring'):
        missing.append('frontend: install/enable mbstring for the selected PHP')
    if 'native' in stages:
        if not php.get('pcntl_waitid'):
            missing.append('native: select PHP with pcntl_waitid (PHP 8.4+)')
        for tool in ('clang++',):
            if not facts.get('tools', {}).get(tool):
                missing.append('native: required executable missing: ' + tool)
    if 'mysql' in stages:
        for tool in ('cmake', 'pkg-config', 'mariadbd', 'mariadb-install-db', 'mariadb'):
            if not facts.get('tools', {}).get(tool):
                missing.append('mysql: required executable missing: ' + tool)
        if not facts.get('connector'):
            missing.append('mysql: Connector/C pkg-config package required (libmariadb, mariadb or mysqlclient)')
    return missing


def inspect_host(runner, stages, php='php'):
    facts = {'stages': list(stages), 'tools': {}, 'connector': None}
    if set(stages) & {'baseline', 'frontend', 'native'}:
        if not shutil.which(php):
            raise RuntimeError('Selected PHP executable not found: ' + php)
        facts['php'] = json.loads(runner.run('host-php', [php, '-r', PHP_PROBE],
                                           cli_reason='Inspect the selected host CLI capabilities'))
    names = set()
    if 'native' in stages:
        names.add('clang++')
    if 'mysql' in stages:
        names.update(('cmake', 'pkg-config', 'mariadbd', 'mariadb-install-db', 'mariadb'))
    for name in sorted(names):
        facts['tools'][name] = shutil.which(name)
    if 'mysql' in stages and facts['tools'].get('pkg-config'):
        for package in ('libmariadb', 'mariadb', 'mysqlclient'):
            try:
                version = runner.run('connector-' + package, ['pkg-config', '--modversion', package])
                facts['connector'] = {'package': package, 'version': version.decode().strip()}
                break
            except RuntimeError:
                continue
    for name in ('clang++', 'cmake', 'mariadbd'):
        if facts['tools'].get(name):
            facts[name + '_version'] = runner.run('host-' + name.replace('+', 'p'),
                                                  [facts['tools'][name], '--version']).decode()
    facts['missing'] = missing_requirements(facts, stages)
    return facts


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--stages', nargs='+', choices=STAGES, default=list(STAGES))
    parser.add_argument('--php', default='php')
    parser.add_argument('--results', type=Path, required=True)
    args = parser.parse_args()
    args.results.mkdir(parents=True, exist_ok=False)
    with CommandRunner(args.results/'logs', Path.cwd()) as runner:
        facts = inspect_host(runner, args.stages, args.php)
    (args.results/'host.json').write_text(json.dumps(facts, indent=2)+'\n')
    if facts['missing']:
        raise SystemExit('\n'.join(facts['missing']))
    print('Verification host requirements satisfied: ' + ', '.join(args.stages))

if __name__ == '__main__':
    main()
