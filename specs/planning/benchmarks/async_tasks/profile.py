"""Check compiler-profile activation and translation-unit isolation without changing the workspace profile."""
import json
from pathlib import Path
import shutil
import subprocess
import tempfile

ROOT = Path(__file__).resolve().parents[4]
FACTS = ROOT / 'build/clang/native-layout/facts.json'


def run(profile, enabled):
    compiler = profile / 'compiler'
    shutil.copytree(ROOT / 'compiler', compiler, ignore=shutil.ignore_patterns('tests', 'generated', '__pycache__'))
    (profile / 'tools').symlink_to(ROOT / 'tools', target_is_directory=True)
    config = json.loads((ROOT / 'build/clang/runtime-publication.json').read_text())
    config['async_frames'] = enabled
    request = profile / 'publication.json'
    request.write_text(json.dumps(config))
    for kind in ['runtime', 'compiler']:
        subprocess.run(['php', ROOT / 'tools/api_generator/generate.php', 'emit-' + kind,
                        ROOT / 'runtime/metadata/runtime.json', request, FACTS, FACTS.with_name('context.json'),
                        profile / kind / 'generated'], check=True, stdout=subprocess.DEVNULL)
    public = profile / 'runtime/generated/public'
    for name in ['async_timer.hpp', 'async/frame.hpp']:
        assert (public / 'scpp' / name).exists() == enabled
    for flavor in ['php', 'cpp', 'js']:
        app = profile / flavor
        app.mkdir()
        suffix = 'phs' if flavor == 'php' else flavor
        sources = [f'{name}.{suffix}' for name in ['main', 'worker', 'plain']]
        signature = 'int readNumber()' if flavor == 'cpp' else 'function readNumber():int'
        (app / sources[2]).write_text(signature.replace('readNumber', 'plain') + '{return 7;}')
        cmd = ['php', compiler / 'main.php', 'program', app, sources[0], FACTS, app / 'out']
        for source in sources:
            cmd.extend([flavor, source])
        for async_source in [False, True]:
            (app / sources[0]).write_text('echo plain();' + ('await ' if async_source else '') + 'readNumber();return 8;')
            (app / sources[1]).write_text(('async ' if async_source else '') + signature + '{return 1;}')
            result = subprocess.run(cmd, capture_output=True, text=True)
            if async_source and not enabled:
                assert result.returncode == 1 and 'Unpublished async result' in result.stderr, result
                continue
            assert result.returncode == 0, result.stderr
            seen = []
            for unit in (app / 'out').rglob('*.cpp'):
                deps = subprocess.check_output(['clang++', '-std=c++23', '-stdlib=libc++', '-M', '-I', public, unit], text=True)
                producer = 'co_return' in unit.read_text()
                seen.append(producer)
                assert ('/coroutine' in deps) == producer, unit
                assert ('scpp/async/frame.hpp' in deps) == producer, unit
                assert 'scpp/async_timer.hpp' not in deps, unit
            assert len(seen) == 4 and sum(seen) == int(async_source), seen


if __name__ == '__main__':
    with tempfile.TemporaryDirectory(prefix='scpp-async-profile-') as tmp:
        for enabled in [False, True]:
            run(Path(tmp) / str(enabled), enabled)
    print('Enabled and disabled profiles passed in all three flavors')
