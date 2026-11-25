// Native behavior investigation only: no source-language interior-reference API is added.
#include <scpp/vector_t.hpp>
#include <scpp_provider/sequence.hpp>
#include <cassert>
#include <cstdint>
#include <iostream>
#include <string>
#include <type_traits>
#include <utility>

struct observations {
    int live = 0;
    int copies = 0;
    int moves = 0;
};

// Heap-backed contents and a destructive move expose copies performed too late during growth.
struct tracked_value {
    observations* counts;
    int value;
    std::string payload;

    tracked_value(observations& counts, int value)
        : counts(&counts), value(value), payload(256, static_cast<char>('a' + value)) {
        ++counts.live;
    }
    tracked_value(const tracked_value& other)
        : counts(other.counts), value(other.value), payload(other.payload) {
        assert(value >= 0 && payload.size() == 256);
        ++counts->live;
        ++counts->copies;
    }
    tracked_value(tracked_value&& other) noexcept
        : counts(other.counts), value(std::exchange(other.value, -1)), payload(std::move(other.payload)) {
        ++counts->live;
        ++counts->moves;
    }
    tracked_value& operator=(const tracked_value&) = default;
    ~tracked_value() { --counts->live; }
};

int value_of(std::int32_t value) { return value; }
int value_of(const tracked_value& value) {
    assert(value.payload == std::string(256, static_cast<char>('a' + value.value)));
    return value.value;
}

template<class T>
T make_value(observations& counts, int value) {
    if constexpr (std::is_same_v<T, tracked_value>) {
        return tracked_value(counts, value);
    } else {
        return value;
    }
}

// Independent source, first-slot alias and last-slot alias, each with/without reallocation.
template<class T>
void exercise(bool growth, int source_kind) {
    observations counts;
    {
        scpp::vector_t<T> items;
        const T original = make_value<T>(counts, 7);
        items.reserve(4);
        const auto old_capacity = items.capacity();
        const auto initial_size = growth ? old_capacity : 2;
        for (std::size_t i = 0; i < initial_size; ++i) {
            const T element = make_value<T>(counts, static_cast<int>(i % 10));
            items.append(element);
        }
        const T& source = source_kind == 0 ? original : items.at(source_kind == 1 ? 0 : initial_size - 1);
        const int expected = value_of(source);
        const int copies_before = counts.copies;
        const int moves_before = counts.moves;

        // Exercise the existing preparation helper, with no added defensive temporary.
        scpp_provider::sequence_append(items, source);
        assert(items.size() == initial_size + 1);
        assert((items.capacity() > old_capacity) == growth);
        assert(value_of(items.at(initial_size)) == expected);
        for (std::size_t i = 0; i < initial_size; ++i) {
            assert(value_of(items.at(i)) == static_cast<int>(i % 10));
        }
        assert(value_of(original) == 7);
        if (!growth) {
            assert(value_of(source) == expected);
        }
        // A source alias into old storage is never accessed after a growing append.
        if constexpr (std::is_same_v<T, tracked_value>) {
            assert(counts.live == static_cast<int>(items.size()) + 1);
            assert(counts.copies > copies_before);
            std::cout << "growth=" << growth << " source=" << source_kind
                      << " copies=" << counts.copies - copies_before
                      << " moves=" << counts.moves - moves_before << '\n';
        }
    }
    assert(counts.live == 0);
}

int main() {
    for (bool growth : {false, true}) {
        for (int source : {0, 1, 2}) {
            exercise<std::int32_t>(growth, source);
            exercise<tracked_value>(growth, source);
        }
    }
    std::cout << "12 cases passed; all tracked lifetimes balanced\n";
}
