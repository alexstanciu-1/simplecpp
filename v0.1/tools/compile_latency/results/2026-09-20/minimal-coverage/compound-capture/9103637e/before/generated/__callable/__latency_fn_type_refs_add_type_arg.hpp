#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct TypeRefTable;
void __latency_fn_type_refs_add_type_arg(TypeRefTable& table, int_t<std::uint32_t> parentTypeRefId, int_t<std::uint16_t> argIndex, int_t<std::uint32_t> typeArgRefId);
}
