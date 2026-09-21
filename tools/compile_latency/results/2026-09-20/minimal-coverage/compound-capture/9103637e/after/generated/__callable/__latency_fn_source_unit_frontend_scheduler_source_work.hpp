#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct DeterministicWorkOrderRow;
struct SourceUnitTableRow;
DeterministicWorkOrderRow __latency_fn_source_unit_frontend_scheduler_source_work(SourceUnitTableRow sourceUnit, int_t<std::uint32_t> ownerRunId, int_t<std::uint16_t> executionModelId, int_t<> outputOrder, int_t<> completionOrder);
}
