"""Behavioral proofs for continuous refill, isolation and concurrent failure evidence."""
import json
from pathlib import Path
import sys
import tempfile
import threading
import time
import unittest

sys.path.insert(0, str(Path(__file__).resolve().parents[1] / 'tools'))
from test_runner import CommandRunner, DEFAULT_JOBS, Task, TaskFailures, run_tasks


class RunnerTests(unittest.TestCase):
    def test_default_bound_and_refill_without_batch_barrier(self):
        barrier = threading.Barrier(12)
        release = threading.Event()
        lock = threading.Lock()
        active, peak = 0, 0

        def work(index):
            nonlocal active, peak
            with lock:
                active += 1
                peak = max(peak, active)
            try:
                if index < 12:
                    barrier.wait(timeout=5)
                    if index < 11:
                        self.assertTrue(release.wait(5), 'Queued task did not refill the free slot')
                else:
                    release.set()
                return index
            finally:
                with lock:
                    active -= 1
        self.assertEqual(DEFAULT_JOBS, 12)
        results = run_tasks([Task(str(i), lambda i=i: work(i)) for i in range(20)], report=False)
        self.assertEqual(peak, 12)
        self.assertEqual([r.value for r in results], list(range(20)))

    def test_all_failures_retained_and_other_tasks_finish(self):
        finished = []
        def fail():
            raise RuntimeError('intentional')
        with self.assertRaises(TaskFailures) as caught:
            run_tasks([Task('first', fail), Task('good', lambda: finished.append(True)),
                       Task('last', fail)], jobs=1, report=False)
        self.assertEqual(finished, [True])
        self.assertEqual([r.error is not None for r in caught.exception.outcomes], [True, False, True])
        with self.assertRaises(ValueError):
            run_tasks([], jobs=0)
        with self.assertRaises(ValueError):
            run_tasks([Task('same', fail), Task('same', fail)])

    def test_process_isolation_logs_and_expected_nonzero(self):
        with tempfile.TemporaryDirectory() as temporary:
            root = Path(temporary)
            runner = CommandRunner(root / 'logs', root, [root / 'commands.json'])
            script = 'import os,tempfile; print(os.getcwd()); print(tempfile.gettempdir()); raise SystemExit(7)'
            results = run_tasks([Task(str(i), lambda i=i:
                                runner.run(str(i), [sys.executable, '-c', script], expected=7))
                                for i in range(24)], report=False)
            paths = [r.value.decode().splitlines() for r in results]
            self.assertTrue(all(row[0] == str(root) for row in paths))
            self.assertEqual(len({row[1] for row in paths}), 24)
            self.assertTrue(all(not Path(row[1]).exists() for row in paths))
            records = json.loads((root / 'commands.json').read_text())
            self.assertEqual(len(records), 24)
            self.assertEqual(records, json.loads((root / 'logs/commands.json').read_text()))
            self.assertTrue(all(row['code'] == 7 for row in records))

    def test_timeout_kills_children_and_records_launch_failure(self):
        with tempfile.TemporaryDirectory() as temporary:
            root = Path(temporary)
            runner = CommandRunner(root / 'logs', root)
            child = 'import time; from pathlib import Path; time.sleep(0.8); Path("escaped").touch()'
            parent = f'import subprocess,sys,time; subprocess.Popen([sys.executable,"-c",{child!r}]); print("started",flush=True); time.sleep(10)'
            with self.assertRaisesRegex(RuntimeError, 'timeout'):
                runner.run('timeout', [sys.executable, '-c', parent], timeout=0.3)
            time.sleep(0.9)
            self.assertFalse((root / 'escaped').exists())
            self.assertIn(b'started', (root / 'logs/timeout.stdout').read_bytes())
            with self.assertRaises(RuntimeError):
                runner.run('missing', [root / 'does-not-exist'])
            self.assertEqual(len(runner.commands), 2)
            self.assertTrue(all('error' in row for row in runner.commands))


if __name__ == '__main__':
    unittest.main()
