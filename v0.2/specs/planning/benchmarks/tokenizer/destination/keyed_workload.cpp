#include "binding.hpp"
#include <chrono>
#include <cstdio>
#include <string>
#include <vector>

int main(int argc, char** argv) {
    if (argc != 2) return 2;
    const bool replace = std::string(argv[1]) == "replace";
    constexpr int count = 8192;
    std::vector<Key> keys;
    for (int i = 0; i < count; ++i) {
        int n = (i * 3989) % count;
#ifdef STRING_KEY
        auto bytes = std::to_string(n);
        keys.emplace_back(bytes.data(), bytes.size());
#else
        keys.push_back(n);
#endif
    }
    long long nanos = 0;
    for (int r = 0; r < 12; ++r) {
        Table table;
        if (replace)
            for (auto& key : keys) table.set(key, {-1, -1});
        auto start = std::chrono::steady_clock::now();
        for (int i = 0; i < count; ++i) {
#ifdef DIRECT
            table.set_fields(keys[i], i, r - i);
#else
            App::scpp_records::Token item{};
            item.x = i;
            item.y = r - i;
            table.set(keys[i], item);
#endif
        }
        nanos += std::chrono::duration_cast<std::chrono::nanoseconds>(std::chrono::steady_clock::now()-start).count();
        if (table.size() != count) return 3;
        for (int i = 0; i < count; ++i) {
            auto got = table.at(keys[i]);
            if (got.x != i || got.y != r - i) return 4;
        }
    }
    std::printf("%.6f\n", static_cast<double>(nanos) / (12 * count));
}
