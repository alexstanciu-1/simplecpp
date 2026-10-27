"""Measure local sample startup stages and allocation-instrumented scan/read operations."""
import json
from pathlib import Path
import statistics
import subprocess
import tempfile

PROJECT = Path(__file__).resolve().parents[1]
ROOT = PROJECT.parents[1]
SAMPLES = {'accounting-small': 'accounting-small', 'accounting-large': 'accounting-large',
           'my-try': 'my-try/phpp'}
TRIALS = 7


def capture(command):
    return subprocess.check_output([str(x) for x in command], cwd=ROOT, text=True)


def summarize(rows):
    return {key: {'median': statistics.median([row[key] for row in rows]),
                  'min': min(row[key] for row in rows), 'max': max(row[key] for row in rows)}
            for key in rows[0] if isinstance(rows[0][key], (int, float))}


def main():
    result = {'trials': TRIALS, 'jobs': 8, 'method':
              'One warmup per command; fresh processes, warm filesystem cache. '
              'Startup is uninstrumented; profiler allocations/times are separate runs. '
              'Ordinary new/new[] counters exclude direct malloc/aligned allocations. '
              'RSS is whole startup process, not per-stage live memory.', 'samples': {}}
    binaries = PROJECT / 'build/compiler'
    for name, relative in SAMPLES.items():
        source = PROJECT / 'samples' / relative
        phs = sorted(source.rglob('*.phs'))
        item = {'path': str(source.relative_to(ROOT)), 'files': len(phs),
                'bytes': sum(p.stat().st_size for p in phs)}
        command = [binaries / 'my-try-cpp', '--catalogs', ROOT / 'runtime/metadata/modules',
                   '--source', source, '--jobs', '8', '--timings']
        capture(command)
        startup = []
        with tempfile.TemporaryDirectory(prefix='sample-metrics-') as temporary:
            rss_file = Path(temporary) / 'rss'
            for _ in range(TRIALS):
                row = json.loads(capture(['/usr/bin/time', '-f', '%M', '-o', rss_file, *command]))
                row['peak_rss_kib'] = int(rss_file.read_text())
                assert row['source_files_read'] == item['files']
                assert row['source_bytes_read'] == item['bytes']
                startup.append(row)
        item['startup'] = {'summary': summarize(startup), 'trials': startup}
        for phase, binary in [('discovery', 'source-discovery-profile'), ('reading', 'source-reading-profile')]:
            command = [binaries / binary, source]
            capture(command)
            samples = {'initial': [], 'unchanged': []}
            for _ in range(TRIALS):
                for line in capture(command).splitlines():
                    label, *fields = line.split()
                    samples[label].append({key: int(value) for key, value in
                                           (field.split('=') for field in fields)})
            item[phase] = {label: {'summary': summarize(rows), 'trials': rows}
                           for label, rows in samples.items()}
        result['samples'][name] = item
    output = PROJECT / 'samples/metrics.json'
    output.write_text(json.dumps(result, indent=2) + '\n')
    for name, item in result['samples'].items():
        print(name, json.dumps({key: row['median'] for key, row in item['startup']['summary'].items()}))
        for phase in ('discovery', 'reading'):
            print(phase, json.dumps({label: {key: row['median'] for key, row in values['summary'].items()}
                                     for label, values in item[phase].items()}))


if __name__ == '__main__':
    main()
