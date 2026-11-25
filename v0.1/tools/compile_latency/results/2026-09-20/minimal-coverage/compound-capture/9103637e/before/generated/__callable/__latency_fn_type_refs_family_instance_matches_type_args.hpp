#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct TypeRefTable;
bool_t __latency_fn_type_refs_family_instance_matches_type_args(TypeRefTable table, int_t<std::uint32_t> typeRefId, int_t<std::uint32_t> familyId, const vector_t<int_t<std::uint32_t>>& typeArgRefIds);
}
