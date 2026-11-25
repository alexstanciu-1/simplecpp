#include "scpp/detail/concrete_keys.hpp"
#include "scpp/table_iteration.hpp"
#include <cstdlib>
#include <limits>

void begin_hash_allocations();
unsigned end_hash_allocations();
unsigned native_hash_allocations()
{
    begin_hash_allocations();
    {
        scpp::hash_t<scpp::string,scpp::int64> values;
        for (int i=0; i<12; ++i) (void)values.append(scpp::string("small"));
        auto found=values.find(scpp::int64(4));
        auto copied=values;
        auto cursor=values.value_range();
        (void)(*cursor.begin()).value_copy();
        values.clear();
        auto iterator=cursor.begin(); ++iterator;
        (void)(*cursor.begin()).value_copy();
    }
    return end_hash_allocations();
}

template<class Key>
static void append_limits()
{
    using rep=decltype(Key().native_value());
    scpp::hash_t<scpp::string,Key> values;
    if (values.append(scpp::string("zero")).native_value()!=0) std::abort();
    values.set(Key(std::numeric_limits<rep>::max()),scpp::string("maximum"));
    bool rejected=false;
    try { (void)values.append(scpp::string("overflow")); } catch (const std::overflow_error &) { rejected=true; }
    if (!rejected || values.size()!=2) std::abort();
}
void native_integer_append_proof()
{
    append_limits<scpp::int8>(); append_limits<scpp::uint8>();
    append_limits<scpp::int16>(); append_limits<scpp::uint16>();
    append_limits<scpp::int32>(); append_limits<scpp::uint32>();
    append_limits<scpp::int64>(); append_limits<scpp::uint64>();
    append_limits<scpp::int_t<>>();
}
