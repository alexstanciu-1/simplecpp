#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class CompilerProjectRunReport;
struct ResidentSourceUnitFrontendPayloadTableRow;
struct SourceUnitTableRow;
ResidentSourceUnitFrontendPayloadTableRow __latency_fn_resident_source_unit_frontend_payload_tables_record_built_handle_metrics_from_counts(shared_p<CompilerProjectRunReport>& report, SourceUnitTableRow sourceUnit, int_t<std::uint32_t> payloadTableId, int_t<std::uint32_t> ownerRunId, int_t<std::uint32_t> tokenCount, int_t<std::uint32_t> tokenSegmentCount, int_t<std::uint32_t> tokenReservedSegmentBytes, int_t<std::uint32_t> tokenSegmentSlackBytes, int_t<std::uint32_t> frontendNodeCount, int_t<std::uint32_t> frontendNodeSegmentCount, int_t<std::uint32_t> frontendNodeReservedSegmentBytes, int_t<std::uint32_t> frontendNodeSegmentSlackBytes, int_t<std::uint32_t> parserErrorCount);
}
