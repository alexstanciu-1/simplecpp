#include "types/n42.hpp"
#include "types/n43.hpp"
record_B::record_B(record_B::scpp_construction_key)
{
}

record_C::record_C() : record_B{record_B::scpp_constructor_key()}
{
}

