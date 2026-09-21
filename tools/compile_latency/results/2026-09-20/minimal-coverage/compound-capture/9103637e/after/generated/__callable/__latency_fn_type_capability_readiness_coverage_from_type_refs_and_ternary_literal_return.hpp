#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class CapabilityCoverageArtifact;
struct TypeRefTable;
shared_p<CapabilityCoverageArtifact> __latency_fn_type_capability_readiness_coverage_from_type_refs_and_ternary_literal_return(TypeRefTable typeRefs, int_t<std::uint32_t> ternarySourceRowId, int_t<std::uint32_t> conditionSourceRowId, int_t<std::uint32_t> thenSourceRowId, int_t<std::uint32_t> elseSourceRowId, int_t<std::uint32_t> conditionTypeRefId, int_t<std::uint32_t> thenTypeRefId, int_t<std::uint32_t> elseTypeRefId, int_t<std::uint32_t> resultTypeRefId);
}
