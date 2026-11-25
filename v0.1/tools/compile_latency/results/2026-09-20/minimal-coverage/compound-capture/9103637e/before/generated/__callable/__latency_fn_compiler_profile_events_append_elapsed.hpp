#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class CompilerProjectRunReport;
int_t<std::uint32_t> __latency_fn_compiler_profile_events_append_elapsed(shared_p<CompilerProjectRunReport>& report, int_t<std::uint32_t> ownerRunId, int_t<std::uint16_t> stageId, int_t<std::uint32_t> rowCount, int_t<std::uint64_t> startedUs, int_t<std::uint16_t> statusId);
}
