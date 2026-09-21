#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct ResidentReverseDependencyLookupRow;
struct ResidentSymbolDefinitionChangeRow;
bool_t __latency_fn_resident_reverse_dependencies_lookup_belongs_to_change(ResidentReverseDependencyLookupRow lookup, ResidentSymbolDefinitionChangeRow change);
}
