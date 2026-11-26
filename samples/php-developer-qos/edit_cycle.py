"""Try edit/build/run and failure recovery with the same generated and native paths."""
import argparse
import json
import os
from pathlib import Path
import subprocess
import tempfile
import time

from check_apps import build, check_cli, root


def atomic_save():
    with tempfile.TemporaryDirectory(prefix='php-atomic-save-') as directory:
        work = Path(directory)
        app = work/'app'
        app.mkdir()
        (app/'module.json').write_text('{}')
        source = app/'main.php'
        good = 'echo 404; return 0;'
        broken = 'echo @@@; return 0;'
        source.write_text(good)
        exe = build(app, work)
        original = source.stat()
        new_file = app/'.editor-save'
        new_file.write_text(broken)
        # A new inode and a new nanosecond value in the same observable second.
        os.utime(new_file, ns=(original.st_atime_ns, original.st_mtime_ns ^ 1))
        os.replace(new_file, source)
        assert original.st_ino != source.stat().st_ino and len(good) == len(broken)
        try:
            build(app, work)
        except RuntimeError as error:
            print('Atomic invalid save rejected:', str(error).strip().replace(str(app)+'/', ''))
        else:
            print('Atomic invalid save built; output:', subprocess.check_output([exe]).decode())
        force = subprocess.run(['php', root/'compiler/main.php', 'project', app, root/'build/clang/native-layout/facts.json',
                                work/'generated', '--force'], capture_output=True)
        if force.returncode == 0:
            raise AssertionError('forced project accepted the intentionally invalid input')
        print('Forced invalid source:', force.stderr.decode().strip().replace(str(app)+'/', ''))


def ordinary_saves():
    with tempfile.TemporaryDirectory(prefix='php-real-saves-') as directory:
        work = Path(directory)
        app = work/'app'
        app.mkdir()
        (app/'module.json').write_text('{}')
        source = app/'main.php'
        source.write_text('echo 99; return 0;')
        exe = build(app, work)
        for trial in range(5):
            # Start near the beginning of a wall-clock second. Do not set an mtime.
            time.sleep(1.01 - time.time() % 1)
            first, second = 200 + trial, 404 + trial
            source.write_text(f'echo {first}; return 0;')
            a = source.stat().st_mtime_ns
            build(app, work)
            check_cli(exe, [], 0, str(first))
            source.write_text(f'echo {second}; return 0;')
            b = source.stat().st_mtime_ns
            build(app, work)
            observed = subprocess.check_output([exe]).decode()
            print(f'ordinary save {trial+1}: gap={(b-a)//1_000_000} ms, '
                  f'same second={a//10**9 == b//10**9}, wanted={second}, got={observed}')


def main():
    with tempfile.TemporaryDirectory(prefix='php-edits-') as directory:
        work = Path(directory)
        app = work/'project with spaces'
        app.mkdir()
        (app/'module.json').write_text(json.dumps({'name': 'edit-demo', 'sources': ['main.php', 'status.php']}))
        main = app/'main.php'
        main.write_text('echo status(); return 0;')
        helper = app/'status.php'
        helper.write_text('function status(): int {return 200;}')
        exe = build(app, work)
        check_cli(exe, [], 0, '200')

        # Reproduce two equal-length editor saves in the same observable mtime second.
        before = helper.stat()
        helper.write_text('function status(): int {return 404;}')
        second, nanos = divmod(before.st_mtime_ns, 1_000_000_000)
        later_ns = second * 1_000_000_000 + (nanos + 1) % 1_000_000_000
        os.utime(helper, ns=(before.st_atime_ns, later_ns))
        build(app, work)
        observed = subprocess.check_output([exe]).decode()
        print('Same-size / different nanosecond in same second:', observed)
        # --force belongs to the source project command. Next build relinks the bundle.
        subprocess.run(['php', root/'compiler/main.php', 'project', app, root/'build/clang/native-layout/facts.json',
                        work/'generated', '--force'], check=True, stdout=subprocess.DEVNULL)
        build(app, work)
        check_cli(exe, [], 0, '404')

        helper.write_text('function status(): int {return missing_helper();}')
        try:
            build(app, work)
        except RuntimeError as error:
            if 'Unknown function: missing_helper' not in str(error) or 'status.php:' not in str(error):
                raise
            print('Invalid edit:', str(error).replace(str(app)+'/', '').strip())
        else:
            raise AssertionError('invalid source unexpectedly built')
        check_cli(exe, [], 0, '404')

        helper.write_text('function status(): int {return 201;} // fixed')
        build(app, work)
        check_cli(exe, [], 0, '201')
        print('Force rebuild, failed edit, and repair: passed')


if __name__ == '__main__':
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--ordinary-saves', action='store_true', help='also try five unmodified editor-style mtimes')
    args = parser.parse_args()
    main()
    atomic_save()
    if args.ordinary_saves:
        ordinary_saves()
