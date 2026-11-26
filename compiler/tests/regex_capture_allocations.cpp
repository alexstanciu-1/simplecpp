// Compare ordinary C++ allocations for identical short-text capture workloads.
// PCRE2 uses its own allocator. The counter deliberately excludes those allocations.
#include <scpp/regex/preg_match_all_pattern_order_numbered.hpp>
#include <scpp/regex/preg_match_all_pattern_order_numbered_offset.hpp>
#include <scpp/regex/preg_match_all_pattern_order_numbered_nullable.hpp>
#include <scpp/regex/preg_match_all_pattern_order_numbered_nullable_offset.hpp>
#include <scpp/regex/preg_match_numbered_nullable_offset.hpp>
#include <scpp/value_error.hpp>
#include <cassert>
#include <cstdio>
#include <cstdlib>
#include <new>

static std::size_t allocations;
void* operator new(std::size_t n) {
    if (void* p = std::malloc(n ? n : 1)) { ++allocations; return p; }
    throw std::bad_alloc{};
}
void operator delete(void* p) noexcept { std::free(p); }
void operator delete(void* p, std::size_t) noexcept { std::free(p); }

template<class Run>
std::size_t measure(Run run) {
    const auto before = allocations;
    run();
    return allocations - before;
}

int main() {
    using namespace scpp;
    vector_regex_nullable_offset_capture cells;
    cells.reserve(64L);
    const auto stored = measure([&] {
        for (int64 i = 0; i < 64; ++i) cells.append(regex_nullable_offset_capture{string{"a"}, i});
    });
    const auto offsets = measure([&] {
        for (int64 i = 0; i < 64; ++i) assert(cells.at(i).byte_offset() == i);
    });
    const auto projected = measure([&] {
        for (int64 i = 0; i < 64; ++i) assert(cells.at(i).text().has_value());
    });
    assert(stored == 0 && offsets == 0);
    const string subject{"aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa"}, pattern{"/(.)/"};
    vector_vector_string plain;
    vector_vector_regex_offset_capture positions;
    vector_vector_nullable_string nullable;
    vector_vector_regex_nullable_offset_capture combined;
    const auto plain_calls = measure([&] { assert(regex::preg_match_all_pattern_order_numbered(pattern, subject, plain).value() == 64); });
    const auto offset_calls = measure([&] { assert(regex::preg_match_all_pattern_order_numbered_offset(pattern, subject, positions).value() == 64); });
    const auto null_calls = measure([&] { assert(regex::preg_match_all_pattern_order_numbered_nullable(pattern, subject, nullable).value() == 64); });
    const auto both_calls = measure([&] { assert(regex::preg_match_all_pattern_order_numbered_nullable_offset(pattern, subject, combined).value() == 64); });
    std::printf("bytes/cell offset=%zu combined=%zu; allocations for 64 stored/offset reads/text reads=%zu/%zu/%zu; "
                "64 hits x 2 columns plain/offset/nullable/both=%zu/%zu/%zu/%zu\n",
                sizeof(regex_offset_capture), sizeof(regex_nullable_offset_capture), stored, offsets, projected,
                plain_calls, offset_calls, null_calls, both_calls);
    try {
        (void)regex::preg_match_numbered_nullable_offset(string{"/[/"}, subject, cells, 256);
        assert(false);
    } catch (const ValueError&) {
        assert(cells.size() == 64 && cells.at(63L).byte_offset() == 63);
    }
}
