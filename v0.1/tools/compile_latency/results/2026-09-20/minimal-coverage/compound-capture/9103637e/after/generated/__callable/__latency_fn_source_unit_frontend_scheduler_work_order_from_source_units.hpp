#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class DeterministicWorkOrderArtifact;
class SourceUnitTable;
shared_p<DeterministicWorkOrderArtifact> __latency_fn_source_unit_frontend_scheduler_work_order_from_source_units(shared_p<SourceUnitTable> sourceUnits, int_t<std::uint32_t> ownerRunId, bool_t simulatedCompletion);
}
