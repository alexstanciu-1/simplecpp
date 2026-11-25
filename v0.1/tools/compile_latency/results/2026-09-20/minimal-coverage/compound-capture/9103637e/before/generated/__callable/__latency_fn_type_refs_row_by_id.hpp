#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct TypeRefRow;
struct TypeRefTable;
TypeRefRow __latency_fn_type_refs_row_by_id(TypeRefTable table, int_t<std::uint32_t> typeRefId);
}
