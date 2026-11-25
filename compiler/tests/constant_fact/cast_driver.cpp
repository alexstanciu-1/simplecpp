// Included after the generated unit. Each input row describes one dirty cast site.
#include <algorithm>
#include <array>
#include <chrono>
#include <cstdio>

using namespace App;
using Fact = scpp_records::constant_fact;
using Slot = scpp_records::integer_slot;

#ifdef COUNT_EVALUATIONS
static unsigned calls;
extern "C" __attribute__((no_instrument_function))
void __cyg_profile_func_enter(void *callee, void *) {
    if (callee == reinterpret_cast<void *>(&evaluateIntegerFact_)) ++calls;
}
extern "C" __attribute__((no_instrument_function))
void __cyg_profile_func_exit(void *, void *) {}
#endif

struct Site { int id, cast, init; Fact input; };

int main() {
    std::array<Slot, 2> locals{};
    std::array<Fact, 2> casts{};
    int n;
    while (std::scanf("%d", &n) == 1) {
        std::array<Site, 2> sites{};
        if (n < 0 || n > 2) return 1;
        for (int i = 0; i < n; ++i) {
            auto &s = sites[i];
            int known, value;
            if (std::scanf("%d%d%d%d%d", &s.id, &known, &value, &s.cast, &s.init) != 5 || s.id < 0 || s.id > 1) return 2;
            s.input = known ? boolFact_(value != 0) : unavailableFact_();
        }
        // Keep the translated function boundary visible to the benchmark harness.
        decltype(&prepareIntegerCastLocal_) volatile prepare = &prepareIntegerCastLocal_;
        auto run = [&] {
            for (int i = 0; i < n; ++i) {
                const auto &s = sites[i];
                locals[s.id] = prepare(s.input, s.cast, s.init);
            }
        };
#ifdef COUNT_EVALUATIONS
        calls = 0;
        run();
        std::printf("%u ", calls);
#else
        std::array<double, 21> samples{};
        for (int sample = -1; sample < static_cast<int>(samples.size()); ++sample) {
            constexpr int repeats = 50000;
            auto start = std::chrono::steady_clock::now();
            for (int repeat = 0; repeat < repeats; ++repeat) run();
            auto ns = std::chrono::duration<double, std::nano>(std::chrono::steady_clock::now() - start);
            if (sample >= 0) samples[sample] = ns.count() / repeats;
        }
        std::sort(samples.begin(), samples.end());
        std::printf("%.2f ", samples[10]);
#endif
        // Inspect the intermediate cast outside the measured preparation.
        for (int i = 0; i < n; ++i) {
            const auto &s = sites[i];
            casts[s.id] = evaluateIntegerFact_(s.cast, s.input);
        }
        for (int i = 0; i < 2; ++i)
            std::printf("%d %lld %d %lld ", casts[i].kind == 3, static_cast<long long>(casts[i].integerValue),
                        int(locals[i].known), static_cast<long long>(locals[i].value));
        std::puts("");
    }
}
