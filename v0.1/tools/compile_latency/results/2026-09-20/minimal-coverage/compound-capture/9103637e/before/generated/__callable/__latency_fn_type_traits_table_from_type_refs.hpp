#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct TypeRefTable;
struct TypeTraitTable;
TypeTraitTable __latency_fn_type_traits_table_from_type_refs(TypeRefTable typeRefs);
}
