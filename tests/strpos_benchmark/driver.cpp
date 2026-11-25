// Link this driver as a native object so every iteration crosses the measured application boundary.
#include <scpp/string.hpp>
#include <scpp/carriers.hpp>
#include <algorithm>
#include <chrono>
#include <cstdio>
#include <cstdlib>
#include <string>
#include <vector>

namespace App {
using namespace scpp;
result_or_false_int64 ptwo_(string&, string&);
result_or_false_int64 pthree_(string&, string&, int64);
int64 jtwo_(string&, string&);
int64 jthree_(string&, string&, int64);
int64 ctwo_(string&, string&);
int64 cthree_(string&, string&, int64);
}

struct sample {
    scpp::string subject;
    scpp::string needle;
    scpp::int64 expected;
};

static scpp::int64 position(scpp::result_or_false_int64 result) {
    return result.has_value() ? result.value() : -1;
}

template<class Search>
static void measure(const char* name, std::vector<sample>& inputs, Search search) {
    constexpr int passes = 500;
    scpp::int64 expected = 0;
    for (auto& input : inputs) {
        if (search(input) != input.expected) std::abort();
        expected += input.expected;
    }
    scpp::int64 sum = 0;
    auto start = std::chrono::steady_clock::now();
    for (int i = 0; i < passes; ++i) {
        for (auto& input : inputs) sum += search(input);
    }
    auto elapsed = std::chrono::steady_clock::now() - start;
    if (sum != expected * passes) std::abort();
    std::printf("%s %.3f\n", name, std::chrono::duration<double, std::nano>(elapsed).count()
                                      / (passes * inputs.size()));
}

int main() {
    std::vector<sample> inputs;
    for (int i = 0; i < 1024; ++i) {
        const std::size_t length = (i & 4) ? 128 : 16;
        std::string text(length, 'a');
        const int group = i % 4;
        const int index = group == 0 ? -1 : group == 1 ? 1 : group == 2 ? length / 2 : length - 3;
        if (index >= 0) text.replace(index, 3, "a\0b", 3);
        inputs.push_back({scpp::string{text.data(), static_cast<scpp::int64>(length)},
                          scpp::string{"a\0b", 3}, index});
    }
    // Fixed shuffle, identical for every flavor, binary, and run.
    unsigned state = 42;
    for (std::size_t i = inputs.size(); i > 1; --i) {
        state = state * 1664525u + 1013904223u;
        std::swap(inputs[i - 1], inputs[state % i]);
    }
    measure("php2", inputs, [](sample& x) { return position(App::ptwo_(x.subject, x.needle)); });
    measure("php3", inputs, [](sample& x) { return position(App::pthree_(x.subject, x.needle, 1)); });
    measure("js2", inputs, [](sample& x) { return App::jtwo_(x.subject, x.needle); });
    measure("js3", inputs, [](sample& x) { return App::jthree_(x.subject, x.needle, 1); });
    measure("cpp2", inputs, [](sample& x) { return App::ctwo_(x.subject, x.needle); });
    measure("cpp3", inputs, [](sample& x) { return App::cthree_(x.subject, x.needle, 1); });
}
