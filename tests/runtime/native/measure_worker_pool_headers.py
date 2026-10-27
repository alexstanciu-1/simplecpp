"""Measure focused versus generic task header compilation; no performance claim from line counts."""
import argparse
import json
import re
from pathlib import Path
import statistics
import subprocess
import time


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--compiler', default='clang++')
    parser.add_argument('--output', type=Path, required=True)
    parser.add_argument('--trials', type=int, default=3)
    args = parser.parse_args()
    if args.trials < 1:
        parser.error('--trials must be positive')
    include = Path(__file__).resolve().parents[3] / 'runtime/include'
    args.output.mkdir(parents=True, exist_ok=True)
    cases = {}
    for enabled in (0, 1):
        for label, header in (('focused', 'modules/tasks/worker_pool.hpp'),
                              ('generic', 'modules/tasks/tasks.hpp')):
            name = f'{label}_{enabled}'
            source = args.output / f'{name}.cpp'
            call = ('scpp::tasks::worker_pool::configure(scpp::int64(4))' if label == 'focused'
                    else 'scpp::tasks::configure_default_worker_pool(scpp::int_t<>(4))')
            source.write_text(f'#include "{header}"\n'
                              f'void configure() {{ {call}; }}\n')
            command = [args.compiler, '-std=c++23', '-O2', '-DNDEBUG',
                       f'-DSCPP_HAS_TASKS={enabled}', '-I'+str(include), str(source)]
            dependencies = subprocess.check_output([*command, '-M'], text=True)
            if label == 'focused':
                for forbidden in ('modules/tasks/tasks.hpp', 'scpp/int_t.hpp', 'scpp/detail.hpp', 'scpp/mixed_t.hpp',
                                  'scpp/dynamic_t.hpp', 'scpp/hash_t.hpp', 'scpp/vector_t.hpp'):
                    assert forbidden not in dependencies, (name, forbidden)
            preprocessed = subprocess.check_output([*command, '-E', '-P'])
            if label == 'focused' and enabled:
                assert not re.search(rb'\b(template|typename)\b', preprocessed), name
            cases[name] = dict(command=command, runs_seconds=[],
                               preprocessed_bytes=len(preprocessed))
    # Alternate focused/generic cases; each compile uses its own object output.
    for _ in range(args.trials):
        for name, case in cases.items():
            started = time.perf_counter()
            subprocess.run([*case['command'], '-c', '-o', str(args.output / f'{name}.o')], check=True)
            case['runs_seconds'].append(round(time.perf_counter() - started, 4))
    for case in cases.values():
        case.pop('command')
        case['median_seconds'] = statistics.median(case['runs_seconds'])
    result = dict(compiler=subprocess.check_output([args.compiler, '--version'], text=True).splitlines()[0],
                  cases=cases)
    (args.output / 'results.json').write_text(json.dumps(result, indent=2)+'\n')
    print(json.dumps(result, indent=2))


if __name__ == '__main__':
    main()
