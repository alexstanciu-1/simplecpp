#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class CompilerProjectRunReport;
struct ResidentChangeEventRow;
void __latency_fn_resident_change_events_append_event(shared_p<CompilerProjectRunReport>& report, ResidentChangeEventRow row);
}
