#include "scpp/dynamic_t.hpp"
#include "scpp/cast.hpp"
#include "scpp/index_write_path.hpp"
#include "scpp/value_iteration.hpp"
#include <cstdlib>

void begin_dynamic_allocations();
unsigned end_dynamic_allocations();
unsigned native_dynamic_allocations(int phase)
{
    using namespace scpp;
    using value=dynamic_t<>;
    const string_t text("a long owned string for dynamic conversion and allocation parity");
    begin_dynamic_allocations();
    if (phase==0) {
        value a, b(null), c(bool_t(true)), d(int_t<>(42)), e(scpp::float_t(1.25));
        a=d; b=e; c=value(null);
        auto scalar=cast<mixed_t>(d);
        auto roundtrip=cast<value>(scalar);
    } else if (phase==1) {
        value a(text), b(a);
        string_t extracted=b.get_string();
        a=value();
    } else if (phase==4) {
        mixed_t a(text);
        auto converted=cast<value>(a);
        auto roundtrip=cast<mixed_t>(converted);
    } else {
        auto a=value::make_container();
        index_store(a,mixed_t(int_t<>(0)),value(int_t<>(10)));
        index_store(a,mixed_t(int_t<>(1)),value(int_t<>(20)));
        auto alias=a;
        auto found=null_index_read(alias,mixed_t(int_t<>(0)));
        if (phase==3) {
            auto cursor=value_iteration(a);
            (void)(*cursor.begin()).value_copy();
            a=value();
            auto iterator=cursor.begin(); ++iterator;
            if ((*cursor.begin()).value_copy().get_int().native_value()!=20) std::abort();
        }
    }
    return end_dynamic_allocations();
}
