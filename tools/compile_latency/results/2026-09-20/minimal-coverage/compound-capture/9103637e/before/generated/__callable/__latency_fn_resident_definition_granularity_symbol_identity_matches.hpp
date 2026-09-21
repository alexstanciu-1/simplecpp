#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct ResidentSymbolDefinitionSnapshotRow;
bool_t __latency_fn_resident_definition_granularity_symbol_identity_matches(ResidentSymbolDefinitionSnapshotRow snapshot, int_t<std::uint32_t> ownerRunId, ResidentSymbolDefinitionSnapshotRow target);
}
