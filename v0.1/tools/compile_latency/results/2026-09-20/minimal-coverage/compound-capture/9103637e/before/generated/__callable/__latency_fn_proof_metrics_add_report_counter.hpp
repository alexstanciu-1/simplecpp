#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
class CompilerProjectRunReport;
void __latency_fn_proof_metrics_add_report_counter(shared_p<CompilerProjectRunReport>& report, const string_t& metricKey, int_t<std::uint32_t> delta);
}
