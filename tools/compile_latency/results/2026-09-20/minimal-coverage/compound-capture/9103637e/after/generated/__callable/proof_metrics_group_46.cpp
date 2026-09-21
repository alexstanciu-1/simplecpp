#include <scpp/lang/php.hpp>
#include "__types/CompilerProjectRunReport.hpp"
#include "__types/ProofMetricRow.hpp"
#include "__types/proof_metrics.hpp"
#include "__callable/__latency_fn_proof_metrics_report_aggregate_owner_id.hpp"
#include "__callable/__latency_fn_proof_metrics_report_value.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_proof_metrics_echo_report_metrics.hpp"
#include "__callable/__latency_fn_proof_metrics_report_aggregate_owner_id.hpp"
namespace scpp { extern const int __latency_lines_proof_metrics[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_proof_metrics_report_value(shared_p<CompilerProjectRunReport> report, const string_t& metricKey) {
	SCPP_CALL_DEPTH_GUARD("proof_metrics::report_value", "/tmp/scpp-edit-latency-20260919/app/compile/support/proof_metrics.phs", __latency_lines_proof_metrics[821]);
	auto __latency_local_0 = report->proof_metrics;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>((php::identical(row->metric_key, metricKey) && php::identical(cast<int_t<>>(row->owner_run_id), cast<int_t<>>(__latency_fn_proof_metrics_report_aggregate_owner_id()))))) {
			return row->value;
		}
	}
	return __latency_fn_structure_row_ids_none_id();
}

}

namespace scpp { extern const int __latency_lines_proof_metrics[]; }
namespace scpp {
void __latency_fn_proof_metrics_echo_report_metrics(shared_p<CompilerProjectRunReport> report, const string_t& prefix) {
	SCPP_CALL_DEPTH_GUARD("proof_metrics::echo_report_metrics", "/tmp/scpp-edit-latency-20260919/app/compile/support/proof_metrics.phs", __latency_lines_proof_metrics[822]);
	auto __latency_local_0 = report->proof_metrics;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->owner_run_id), cast<int_t<>>(__latency_fn_proof_metrics_report_aggregate_owner_id())))) {
			php::echo_one(prefix);
			php::echo_one(row->metric_key);
			php::echo_one(string_t("="));
			php::echo_one(cast<int_t<>>(row->value));
			php::echo_one(string_t("\n"));
		}
	}
}

}
