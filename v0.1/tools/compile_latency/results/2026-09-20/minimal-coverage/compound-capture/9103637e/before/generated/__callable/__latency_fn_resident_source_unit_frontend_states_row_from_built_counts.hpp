#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct ResidentSourceUnitFrontendStateRow;
struct SourceUnitTableRow;
ResidentSourceUnitFrontendStateRow __latency_fn_resident_source_unit_frontend_states_row_from_built_counts(SourceUnitTableRow sourceUnit, int_t<std::uint32_t> sidecarId, int_t<std::uint16_t> stateKindId, int_t<std::uint32_t> tokenCount, int_t<std::uint32_t> tokenSegmentCount, int_t<std::uint32_t> tokenSegmentReservedBytes, int_t<std::uint32_t> tokenSegmentSlackBytes, int_t<std::uint32_t> frontendNodeCount, int_t<std::uint32_t> frontendNodeSegmentCount, int_t<std::uint32_t> frontendNodeSegmentReservedBytes, int_t<std::uint32_t> frontendNodeSegmentSlackBytes, int_t<std::uint32_t> declarationCount, int_t<std::uint32_t> statementCount, int_t<std::uint32_t> expressionCount, int_t<std::uint32_t> parserErrorCount);
}
