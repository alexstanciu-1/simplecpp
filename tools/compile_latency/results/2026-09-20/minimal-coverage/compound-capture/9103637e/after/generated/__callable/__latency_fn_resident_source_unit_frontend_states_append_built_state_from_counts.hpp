#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class CompilerProjectRunReport;
struct ResidentSourceUnitFrontendStateRow;
struct SourceUnitTableRow;
ResidentSourceUnitFrontendStateRow __latency_fn_resident_source_unit_frontend_states_append_built_state_from_counts(shared_p<CompilerProjectRunReport>& report, int_t<std::uint32_t> ownerRunId, SourceUnitTableRow sourceUnit, int_t<std::uint32_t> sidecarId, int_t<std::uint16_t> stateKindId, int_t<std::uint32_t> tokenCount, int_t<std::uint32_t> tokenSegmentCount, int_t<std::uint32_t> tokenSegmentReservedBytes, int_t<std::uint32_t> tokenSegmentSlackBytes, int_t<std::uint32_t> frontendNodeCount, int_t<std::uint32_t> frontendNodeSegmentCount, int_t<std::uint32_t> frontendNodeSegmentReservedBytes, int_t<std::uint32_t> frontendNodeSegmentSlackBytes, int_t<std::uint32_t> declarationCount, int_t<std::uint32_t> statementCount, int_t<std::uint32_t> expressionCount, int_t<std::uint32_t> parserErrorCount);
}
