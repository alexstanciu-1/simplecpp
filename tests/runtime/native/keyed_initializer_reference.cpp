#include "scpp/data_initializer_typed.hpp"
#include "scpp/detail/concrete_keys.hpp"
#include "scpp/string.hpp"
extern bool counting;
extern unsigned allocations;
extern "C" unsigned native_keyed_allocations()
{
    using namespace scpp;
    initializer_buffer<initializer_entry<string, string>> input(1);
    input.append({string("A sufficiently long key to measure owned factory copies"),
                  string("A sufficiently long value to measure owned factory copies")});
    allocations = 0; counting = true;
    auto result = data_initializer<hash_t<string, string>>::from_key_value(input.view());
    counting = false;
    return allocations;
}
