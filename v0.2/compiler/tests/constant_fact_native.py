"""Compile the by-value evaluator specimen, check scalar identity and measure its ABI."""
import json
import os
from pathlib import Path
import statistics
import subprocess
import tempfile

from app_namespace_native import ROOT, invoke, service, write_build


SOURCE = Path(__file__).with_name('constant_fact') / 'main.php'
FACT = ROOT / 'compiler/examples/constant_evaluation/constant_fact.php.scpp'
PREPARE = r'''
require $argv[1];
use scpp\compiler\{Parser, Preparation, Tokenizer, entry_target, flavor, source_file};
$text = file_get_contents($argv[2]);
$target = new entry_target(31);
$samples = [];
for ($i = 0; $i < 22; ++$i) {
    $source = new source_file('entry', $text, flavor::php);
    $file = (new Parser((new Tokenizer($source))->tokenize()))->parse();
    $start = hrtime(true);
    (new Preparation())->prepare($file, $target);
    if ($i >= 1) $samples[] = (hrtime(true) - $start) / 1e6;
}
echo json_encode($samples);
'''

NATIVE = r'''
#include <algorithm>
#include <array>
#include <bit>
#include <chrono>
#include <cstdint>
#include <cstdio>
#include <limits>
#include <type_traits>

int main() {
    using namespace App;
    using Fact = scpp_records::constant_fact;
    static_assert(std::is_trivially_copyable_v<Fact>);
    if (scpp_entry_body() != 8) return 1;
    auto unknown = evaluateFact_(unavailableFact_(), true);
    auto none = evaluateFact_(nullFact_(), true);
    auto f = evaluateFact_(boolFact_(false), true);
    auto zero = evaluateFact_(intFact_(0), true);
    auto max = evaluateFact_(intFact_(std::numeric_limits<std::int64_t>::max()), true);
    auto negative = evaluateFact_(floatFact_(-0.0), true);
    if (unknown.kind != 0 || none.kind != 1 || f.kind != 2 || f.booleanValue) return 2;
    if (zero.kind != 3 || zero.integerValue != 0 || max.kind != 3 ||
        max.integerValue != std::numeric_limits<std::int64_t>::max()) return 3;
    if (negative.kind != 4 || std::bit_cast<std::uint64_t>(negative.floatValue) != (UINT64_C(1) << 63)) return 4;
    auto declined = evaluateFact_(none, false);
    auto copy = max;
    copy.integerValue = 1;
    if (declined.kind != 0 || none.kind != 1 || copy.integerValue == max.integerValue) return 5;
    std::printf("sizeof=%zu alignof=%zu\n", sizeof(Fact), alignof(Fact));
    // Indirect calls keep the translated evaluator ABI visible to the optimizer.
    decltype(&evaluateFact_) volatile evaluate = &evaluateFact_;
    std::array<Fact, 6> inputs{unknown, none, f, zero, max, negative};
    constexpr int calls = 240000;
    std::array<double, 11> samples{};
    for (int run = -1; run < static_cast<int>(samples.size()); ++run) {
        unsigned checksum = 0;
        auto start = std::chrono::steady_clock::now();
        for (int i = 0; i < calls; ++i) {
            checksum += evaluate(inputs[i % inputs.size()], (i % 12) < 6).kind;
        }
        auto elapsed = std::chrono::duration<double, std::nano>(std::chrono::steady_clock::now() - start);
        if (checksum != (calls / 12) * (0 + 1 + 2 + 3 + 3 + 4)) return 6;
        if (run >= 0) samples[run] = elapsed.count() / calls;
    }
    std::sort(samples.begin(), samples.end());
    std::printf("native identity/decline median %.2f ns/call (%d calls/sample)\n", samples[5], calls);
}
'''


def prove(work):
    app = work / 'app'
    app.mkdir()
    specimen = app / 'main.php'
    specimen.write_bytes(FACT.read_bytes() + b'\n' + SOURCE.read_bytes())
    (app / 'module.json').write_text('{"entry":"main.php"}')
    out = work / 'out'
    with service(app, out) as proc:
        result = invoke(proc)
        assert 'error' not in result, result
        assert invoke(proc)['preparation']['prepared'] == 0
        proc.stdin.close()
    units = [out / u for u in json.loads((out / 'manifest.json').read_text())['translation_units']]
    body, = [u for u in units if 'int scpp_entry_body()\n{' in u.read_text()]
    probe = work / 'probe.cpp'
    probe.write_text(f'#include "{body}"\n' + NATIVE)
    cmd = write_build(work, [probe, *(u for u in units if u != body and u.name != 'entry.cpp')])
    subprocess.run(cmd, cwd=work, check=True)
    subprocess.run([work / 'program'], check=True)
    output = subprocess.check_output(['php', '-r', PREPARE, ROOT / 'compiler/boot.php', specimen],
                                     env={**os.environ, 'XDEBUG_MODE': 'off'})
    samples = json.loads(output)
    print(f'{specimen.stat().st_size} bytes, {len(samples)} warm-process fresh preparations: '
          f'median {statistics.median(samples):.3f} ms, range {min(samples):.3f}–{max(samples):.3f} ms')


if __name__ == '__main__':
    with tempfile.TemporaryDirectory() as tmp:
        prove(Path(tmp))
