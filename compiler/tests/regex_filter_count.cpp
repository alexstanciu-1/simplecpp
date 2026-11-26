// Observe the mutable output after the scalar runtime exception unwinds.
#include <scpp/regex/preg_filter_string.hpp>
#include <scpp/value_error.hpp>
#include <cassert>

int main() {
    using namespace scpp;
    auto check = [](const string& pattern, const string& subject, int64 expected) {
        int64 count = 99;
        try {
            (void)regex::preg_filter_string(pattern, string{"!"}, subject, -1, count);
            assert(false);
        } catch (const ValueError&) {
            assert(count == expected);
        }
    };
    check(string{"/[/"}, string{"a"}, 0);
    check(string{"/(*LIMIT_MATCH=20)a|(?:b+)+c/"}, string{"abbbbbbbbbbbbbbbbd"}, 1);
}
