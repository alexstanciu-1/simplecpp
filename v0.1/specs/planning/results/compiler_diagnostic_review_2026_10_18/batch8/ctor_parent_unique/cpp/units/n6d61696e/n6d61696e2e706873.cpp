#include "types/n42.hpp"
#include "scpp/int_t.hpp"
#include "scpp/unique_p.hpp"
#include "types/n43.hpp"
#include "utility"
record_B::record_B(scpp::unique_p<scpp::int_t<>> local_p)
{
}

record_C::record_C(scpp::unique_p<scpp::int_t<>> local_p) : record_B{scpp::unique_p<scpp::int_t<>>(scpp::unique_p<scpp::int_t<>>(std::move(local_p)))}
{
}

