"""On-demand native lookup and retained-node measurement. Requires the datetime runtime."""
import json
from pathlib import Path
import shutil
import statistics
import subprocess
import sys
import tempfile

from translated_preparation_recovery import ROOT, fresh_build


def verify(work, compiler, build):
    app, out = work/'app', work/'out'
    app.mkdir()
    fixture = ROOT/'compiler/examples/signature_interning'
    shutil.copyfile(fixture/'index.phs', app/'index.phs')
    shutil.copyfile(fixture/'churn.phs', app/'main.phs')
    (app/'module.json').write_text(json.dumps({
        'dependencies': {'runtime': {'modules': ['datetime']}}, 'general_api': True}))
    result = fresh_build(app, out)
    assert 'error' not in result, result
    manifest = json.loads((out/'manifest.json').read_text())
    subprocess.run([compiler, '-std=c++23', '-stdlib=libc++', '-O2', '-I', ROOT/'runtime/generated/public',
                    *(out/unit for unit in manifest['translation_units']),
                    *(build/Path(lib).name for lib in manifest['link_inputs']), '-o', work/'program'], check=True)
    samples = {8: [], 64: [], 256: []}
    for _ in range(5):
        output = subprocess.check_output([work/'program'], text=True)
        rows = [list(map(int, line.split())) for line in output.splitlines()]
        assert [row[0] for row in rows] == list(samples), rows
        for width, elapsed, released, cycled, narrowed in rows:
            assert released == cycled == narrowed == width + 2, (width, released, cycled, narrowed)
            samples[width].append(elapsed)
    for width, timings in samples.items():
        print(f'{width} live signatures: {statistics.median(timings)} ms per 131072 hits; '
              f'{width + 2} nodes after release, 32 replacements, and narrowing; samples {timings}')


if __name__ == '__main__':
    with tempfile.TemporaryDirectory(prefix='scpp-signature-churn-') as tmp:
        verify(Path(tmp), sys.argv[1], Path(sys.argv[2]))
