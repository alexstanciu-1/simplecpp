#include "checked_view.hpp"
#include "split_view.hpp"
#include <cassert>
#include <string>
#include <type_traits>
using tokenizer_bench::checked_byte_view;
static_assert(!std::is_move_constructible_v<checked_byte_view>);
static_assert(!std::is_copy_constructible_v<checked_byte_view>);

checked_byte_view from_local(const std::string& expected) {
    scpp::string local(expected.data(), expected.size());
    return checked_byte_view(std::move(local));
}

void verify(const auto& view, const std::string& expected) {
    assert(view.size() == static_cast<scpp::int64>(expected.size()));
    for (scpp::int64 i = 0; i < view.size(); ++i)
        assert(view.byte_at(i) == static_cast<unsigned char>(expected[i]));
    for (auto i : {scpp::int64{-1}, view.size()}) {
        try { view.byte_at(i); assert(false); }
        catch (const std::out_of_range& e) {
            assert(std::string(e.what()) == "sequence_index_out_of_range");
        }
    }
}

using tokenizer_bench::split_view;
static_assert(!std::is_copy_constructible_v<split_view>);
static_assert(!std::is_move_constructible_v<split_view>);
static_assert(!std::is_constructible_v<split_view, scpp::string&&>);

int main() {
    for (std::string expected : {std::string{}, std::string("a\0\xff", 3),
                                 std::string(22, 'i'), std::string(23, 'h'), std::string(256, 'z')}) {
        scpp::string input(expected.data(), expected.size());
        const scpp::string owner(std::move(input));
        const split_view descriptor(owner);
        input = scpp::string("reassigned");
        verify(descriptor, expected);
        verify(from_local(expected), expected);
        scpp::string source(expected.data(), expected.size());
        checked_byte_view view(std::move(source));
        source = scpp::string("different bytes");
        verify(view, expected);
    }
}
