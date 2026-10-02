"""FPM transport contracts; opt in to socket-based proofs with MY_TRY_TEST_FPM=1."""
import json
import os
from pathlib import Path
import sys
import tempfile
import time
import unittest
from unittest.mock import patch

sys.path.insert(0, str(Path(__file__).resolve().parents[1] / 'tools'))
from test_runner import CommandRunner, Task, run_tasks


class SelectionTests(unittest.TestCase):
    def test_unavailable_falls_back_once_and_explicit_cli_skips_discovery(self):
        with tempfile.TemporaryDirectory() as folder:
            with patch('php_executor.FpmPool', side_effect=RuntimeError('not installed')) as pool:
                with CommandRunner(Path(folder) / 'logs', Path(folder)) as runner:
                    for name in ('first', 'second'):
                        self.assertEqual(runner.run(name, ['php', '-r', 'echo "ok";']), b'ok')
                    self.assertEqual(pool.call_count, 1)
                    self.assertEqual(runner.commands[0]['executor'], 'cli')
                    self.assertIn('not installed', runner.commands[0]['executor_reason'])
                    runner.run('exit', ['php', '-r', 'exit(7);'], expected=7, cli_reason='exit contract')
                    self.assertEqual(pool.call_count, 1)


@unittest.skipUnless(os.environ.get('MY_TRY_TEST_FPM') == '1', 'Set MY_TRY_TEST_FPM=1 for private FPM integration')
class FpmTests(unittest.TestCase):
    def test_parallel_isolation_arguments_bytes_and_cache_refresh(self):
        with tempfile.TemporaryDirectory() as folder:
            root = Path(folder)
            script = root / 'script.php'
            script.write_text('<?php echo json_encode([$argv, $argc, getcwd(), sys_get_temp_dir(), PHP_SAPI, isset($GLOBALS["dirty"])]); $GLOBALS["dirty"]=true; fwrite(STDERR,"err\\0");')
            with CommandRunner(root / 'logs', root, jobs=2, php_executor='fpm') as runner:
                def invoke(index):
                    cwd = root / str(index)
                    cwd.mkdir()
                    return runner.run(str(index), ['php', script, 'a b', 'λ'], cwd=cwd)
                rows = run_tasks([Task(str(i), lambda i=i: invoke(i)) for i in range(12)], jobs=2, report=False)
                values = [json.loads(row.value) for row in rows]
                self.assertEqual(len({v[3] for v in values}), 12)
                for i, value in enumerate(values):
                    self.assertEqual(value[:3], [[str(script), 'a b', 'λ'], 3, str(root / str(i))])
                    self.assertEqual(value[4:], ['fpm-fcgi', False])
                    self.assertFalse(Path(value[3]).exists())
                    self.assertEqual((root / 'logs' / f'{i}.stderr').read_bytes(), b'err\0')
                script.write_text('<?php echo "changed";')
                self.assertEqual(runner.run('changed', ['php', script]), b'changed')
                self.assertEqual(runner.run('bytes', ['php', '-r', 'echo "a\\0"; fwrite(STDOUT,"b"); echo "c";']), b'a\0bc')
                self.assertEqual(runner.run('stdin', ['php', '-r', 'echo stream_get_contents(STDIN);'], input=b'a\0b'), b'a\0b')
                self.assertTrue(all(row['executor'] == 'fpm' for row in runner.commands))

    def test_failure_never_retries_and_timeout_kills_child_then_recovers(self):
        with tempfile.TemporaryDirectory() as folder:
            root = Path(folder)
            with CommandRunner(root / 'logs', root, jobs=1, php_executor='fpm') as runner:
                code = 'file_put_contents("count", "x", FILE_APPEND); throw new Exception("intentional");'
                with self.assertRaises(RuntimeError):
                    runner.run('failure', ['php', '-r', code])
                self.assertEqual((root / 'count').read_text(), 'x')
                self.assertIn(b'intentional', (root / 'logs/failure.stderr').read_bytes())
                with self.assertRaisesRegex(RuntimeError, 'before returning'):
                    runner.run('exit', ['php', '-r', 'exit(0);'])
                # Keep a shell child alive long enough to catch a client-only cancellation bug.
                code = 'proc_open(["/bin/sh", "-c", "sleep 0.8; touch escaped"], [], $pipes); echo "started"; sleep(10);'
                with self.assertRaisesRegex(RuntimeError, 'timeout'):
                    runner.run('timeout', ['php', '-r', code], timeout=.3)
                time.sleep(1)
                self.assertFalse((root / 'escaped').exists())
                self.assertEqual((root / 'logs/timeout.stdout').read_bytes(), b'started')
                self.assertEqual(runner.run('recovery', ['php', '-r', 'echo "recovered";']), b'recovered')


if __name__ == '__main__':
    unittest.main()
