// Optional donor comparison; run both builds with the same release compiler flags.
#include <chrono>
#include <cstdio>
#include <cstdint>
#ifdef SCPP_PACKED_DONOR
#include "scpp/hash_t.hpp"
using table = scpp::hash_detail::packed_table<int>;
auto key(std::int64_t n) { return scpp::int_t<>{n}; }
int lookup(const table& t, std::int64_t n) { return t.at(key(n)); }
auto text_key(const char* s) { return scpp::string_t{s}; }
int text_lookup(const table& t, const scpp::string_t& s) { return t.at(s); }
#else
#include "packed_table.hpp"
using table = scpp::detail::packed_table<int>;
auto key(std::int64_t n) { return n; }
int lookup(const table& t, std::int64_t n) { return *t.find(n); }
auto text_key(const char* s) { return std::string_view{s}; }
int text_lookup(const table& t, std::string_view s) { return *t.find(s); }
#endif
int main() {
    using clock = std::chrono::steady_clock;
    auto start = clock::now();
    table dense;
    for (int i = 0; i < 10000; ++i) (void)dense.append(i);
    auto dense_done = clock::now();
    table sparse;
    for (int i = 0; i < 10000; ++i) sparse.set(key(i * 2), i);
    auto insert_done = clock::now();
    std::int64_t checksum = 0;
    for (int repeat = 0; repeat < 20; ++repeat)
        for (int i = 0; i < 10000; ++i) checksum += lookup(sparse, i * 2);
    auto lookup_done = clock::now();
    table churn;
    for (int i = 0; i < 10000; ++i) {
        churn.set(key(i * 2), i);
        if (i >= 32) (void)churn.remove(key((i - 32) * 2));
    }
    auto churn_done = clock::now();
    table strings;
    auto text = text_key("a-long-already-interned-string-for-repeated-lookup");
    strings.set(text, 5);
    for (int i = 0; i < 200000; ++i) checksum += text_lookup(strings, text);
    auto string_done = clock::now();
    auto us = [](auto a, auto b) { return std::chrono::duration_cast<std::chrono::microseconds>(b-a).count(); };
    std::printf("dense_us=%lld sparse_insert_us=%lld lookup_us=%lld churn_us=%lld string_lookup_us=%lld bytes_dense=%zu bytes_sparse=%zu checksum=%lld\n",
        static_cast<long long>(us(start,dense_done)), static_cast<long long>(us(dense_done,insert_done)),
        static_cast<long long>(us(insert_done,lookup_done)), static_cast<long long>(us(lookup_done,churn_done)),
        static_cast<long long>(us(churn_done,string_done)), dense.estimated_storage_bytes(), sparse.estimated_storage_bytes(),
        static_cast<long long>(checksum));
}
