"""Reusable bounded task pool and isolated, logged subprocess execution.

A task owns its sequential dependencies (for example compile then execute).
Independent tasks refill free worker slots immediately; phases remain caller-owned.
"""
from concurrent.futures import ThreadPoolExecutor, as_completed
from dataclasses import dataclass
import json
import os
from pathlib import Path
import signal
import subprocess
import tempfile
import threading
import time
from typing import Callable, Any

DEFAULT_JOBS = 12


def positive_jobs(value):
    number = int(value)
    if number < 1:
        raise ValueError('jobs must be at least 1')
    return number


@dataclass(frozen=True)
class Task:
    name: str
    action: Callable[[], Any]


@dataclass
class Outcome:
    name: str
    value: Any = None
    error: str | None = None
    seconds: float = 0


class TaskFailures(RuntimeError):
    def __init__(self, outcomes):
        self.outcomes = outcomes
        failures = [f'{row.name}: {row.error}' for row in outcomes if row.error is not None]
        super().__init__('\n'.join(failures))


def run_tasks(tasks, jobs=DEFAULT_JOBS, *, report=True):
    """Drain all tasks, report completion promptly, return outcomes in input order.

    Collect failures rather than abandoning evidence from other independent jobs.
    Callers must not share mutable fixture files or nest pools inside these tasks.
    """
    positive_jobs(jobs)
    tasks = list(tasks)
    if len({task.name for task in tasks}) != len(tasks):
        raise ValueError('Task names must be unique within a phase')

    def execute(task):
        started = time.monotonic()
        try:
            return Outcome(task.name, value=task.action(), seconds=time.monotonic() - started)
        except Exception as error:
            return Outcome(task.name, error=f'{type(error).__name__}: {error}',
                           seconds=time.monotonic() - started)

    outcomes = [None] * len(tasks)
    with ThreadPoolExecutor(max_workers=jobs) as pool:
        futures = {pool.submit(execute, task): index for index, task in enumerate(tasks)}
        for completed, future in enumerate(as_completed(futures), 1):
            outcome = future.result()
            outcomes[futures[future]] = outcome
            if report:
                print(f'[{completed}/{len(tasks)}] {outcome.name}: '
                      f'{"PASS" if outcome.error is None else "FAIL"} ({outcome.seconds:.2f}s)', flush=True)
    if any(row.error is not None for row in outcomes):
        raise TaskFailures(outcomes)
    return outcomes


class CommandRunner:
    """Capture per-command evidence and atomically publish a concurrent journal.

    Each subprocess has its own temporary directory and process group. A timeout
    kills the group (including compiler children), then saves stdout/stderr.
    """
    def __init__(self, logs, cwd, journals=()):
        self.logs = Path(logs)
        self.logs.mkdir(parents=True, exist_ok=True)
        self.cwd = Path(cwd)
        self.journals = [self.logs / 'commands.json', *map(Path, journals)]
        self.commands = []
        self._names = set()
        self._lock = threading.Lock()

    def run(self, name, command, cwd=None, expected=0, timeout=240):
        if Path(name).name != name or name in ('', '.', '..'):
            raise ValueError('Command name must be a single nonempty path component')
        with self._lock:
            if name in self._names:
                raise ValueError(f'Duplicate command name: {name}')
            self._names.add(name)
        command = [str(part) for part in command]
        cwd = Path(cwd) if cwd is not None else self.cwd
        started = time.monotonic()
        stdout, stderr, code, error = b'', b'', None, None
        try:
            with tempfile.TemporaryDirectory(prefix='test-command-') as temporary:
                environment = dict(os.environ, TMPDIR=temporary, TMP=temporary, TEMP=temporary)
                process = subprocess.Popen(command, cwd=cwd, env=environment,
                                           stdout=subprocess.PIPE, stderr=subprocess.PIPE,
                                           start_new_session=True)
                try:
                    stdout, stderr = process.communicate(timeout=timeout)
                    code = process.returncode
                except subprocess.TimeoutExpired:
                    os.killpg(process.pid, signal.SIGKILL)
                    stdout, stderr = process.communicate()
                    code, error = process.returncode, f'timeout after {timeout}s'
        except Exception as failure:
            error = f'{type(failure).__name__}: {failure}'
        (self.logs / (name + '.stdout')).write_bytes(stdout)
        (self.logs / (name + '.stderr')).write_bytes(stderr)
        record = dict(name=name, command=command, cwd=str(cwd),
                      seconds=round(time.monotonic() - started, 3), code=code, expected=expected)
        if error is not None:
            record['error'] = error
        with self._lock:
            self.commands.append(record)
            data = json.dumps(self.commands, indent=2) + '\n'
            for journal in self.journals:
                temporary_path = journal.with_suffix('.json.tmp')
                temporary_path.write_text(data)
                temporary_path.replace(journal)
        if error is not None or code != expected:
            raise RuntimeError(f'{name}: {error or f"expected exit {expected}, got {code}"}; see {self.logs}')
        return stdout
