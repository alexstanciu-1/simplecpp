#!/usr/bin/env python3
"""Diagnostic wrapper; forwards real Clang work but perturbs startup/CPU scheduling.

Per-invocation CPU/intervals describe this instrumented run, not production
utilization. Use an unwrapped pool measurement for normal dispatch throughput.
"""

import json
import os
from pathlib import Path
import resource
import fcntl
import subprocess
import sys
import time


def main():
    arguments = sys.argv[1:]
    output = arguments[arguments.index('-o') + 1] if '-o' in arguments else None
    if '--version' in arguments:
        operation = 'version'
    elif '-c' in arguments:
        operation = 'compile_module' if output and Path(output).name.startswith('scpp-object-') else 'return_probe'
    elif '-S' in arguments:
        operation = 'target_or_entry_probe'
    else:
        operation = 'link'
    started_ns = time.perf_counter_ns()
    started = started_ns / 1e9
    result = subprocess.run([os.environ['SCPP_BENCH_CLANG'], *arguments])
    ended_ns = time.perf_counter_ns()
    seconds = (ended_ns - started_ns) / 1e9
    # This wrapper starts exactly one child invocation. Linux reports child CPU
    # usage (including waited descendants) and the largest process RSS high-water
    # mark, not the sum of simultaneous parent/child memory. No sampling is needed.
    usage = resource.getrusage(resource.RUSAGE_CHILDREN)
    with open(os.environ['SCPP_BENCH_TOOL_LOG'], 'a') as stream:
        fcntl.flock(stream, fcntl.LOCK_EX)
        stream.write(json.dumps({'operation': operation, 'seconds': seconds,
                                 'started_ns': started_ns, 'ended_ns': ended_ns,
                                 'cpu_user_seconds': usage.ru_utime, 'cpu_system_seconds': usage.ru_stime,
                                 'peak_rss_bytes': usage.ru_maxrss * 1024,
                                 'exit_code': result.returncode}) + '\n')
    raise SystemExit(result.returncode)


if __name__ == '__main__':
    main()
