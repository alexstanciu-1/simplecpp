// The callback and the count output can refer to the same caller storage.
#include <scpp/regex/callback_mixed.hpp>
#include <cassert>

int main() {
    using namespace scpp;
    int64 count = 99;
    struct State { int64& count; } state{count};
    struct Stop {};
    auto mutate = [](const void* context, mixed) -> string {
        auto& slot = static_cast<const State*>(context)->count;
        if (slot == 77) throw Stop{};
        assert(slot == 99);
        slot = 77;
        return string{"!"};
    };
    try {
        (void)regex::replace_callback(string{"/a/"}, &state, mutate, string{"aaa"}, -1, count);
        assert(false);
    } catch (const Stop&) {
        assert(count == 2);
    }
    count = 99;
    assert(regex::replace_callback(string{"/a/"}, &state, mutate, string{"a"}, -1, count).has_value());
    assert(count == 1);
}
