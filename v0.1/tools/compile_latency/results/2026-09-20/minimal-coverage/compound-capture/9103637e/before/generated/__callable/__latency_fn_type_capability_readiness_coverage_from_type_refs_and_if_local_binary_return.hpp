#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class CapabilityCoverageArtifact;
struct TypeRefTable;
shared_p<CapabilityCoverageArtifact> __latency_fn_type_capability_readiness_coverage_from_type_refs_and_if_local_binary_return(TypeRefTable typeRefs, int_t<std::uint32_t> leftLocalSourceRowId, int_t<std::uint32_t> rightLocalSourceRowId, int_t<std::uint32_t> conditionBinarySourceRowId, int_t<std::uint16_t> conditionBinaryFeatureId, int_t<std::uint32_t> ifSourceRowId, int_t<std::uint32_t> thenLiteralSourceRowId, int_t<std::uint32_t> fallbackLiteralSourceRowId, int_t<std::uint32_t> conditionProviderTypeRefId, int_t<std::uint32_t> thenTypeRefId, int_t<std::uint32_t> fallbackTypeRefId, int_t<std::uint32_t> conditionTypeRefId);
}
