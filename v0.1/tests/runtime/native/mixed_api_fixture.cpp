#include "scpp/mixed_t.hpp"
#include "scpp/memory.hpp"
#include "scpp/index_write_path.hpp"
#include "scpp/keyed_path.hpp"
#include "scpp/value_iteration.hpp"
#include <cstdlib>

void begin_mixed_allocations();
unsigned end_mixed_allocations();
unsigned native_mixed_allocations(int phase)
{
    using namespace scpp;
    const string_t text("a long owned string to exercise the native mixed allocation path");
    begin_mixed_allocations();
    if (phase == 0) {
        mixed_t a, b(null), c(bool_t(true)), d(int_t<>(42)), e(float_t(1.25));
        a = d; b = e; c = null;
    } else if (phase == 1) {
        mixed_t a(text);
        mixed_t b(a);
        string_t extracted = b.get_string();
        a = null;
    } else {
        mixed_t a(unique<hash_t<mixed_t>>());
        index_store(a,mixed_t(int_t<>(0)),mixed_t(int_t<>(10)));
        index_store(a,mixed_t(int_t<>(1)),mixed_t(int_t<>(20)));
        mixed_t copy(a);
        auto found = null_index_read(copy,mixed_t(int_t<>(0)));
        if (phase == 3) {
            auto cursor = value_iteration(a);
            (void)(*cursor.begin()).value_copy();
            a = null;
            auto iterator=cursor.begin(); ++iterator;
            if ((*cursor.begin()).value_copy().get_int().native_value()!=20) std::abort();
        }
    }
    return end_mixed_allocations();
}
