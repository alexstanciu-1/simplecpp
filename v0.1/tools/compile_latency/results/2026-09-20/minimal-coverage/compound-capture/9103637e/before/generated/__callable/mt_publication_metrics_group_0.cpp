#include <scpp/lang/php.hpp>
#include "__types/CompilerProjectRunReport.hpp"
#include "__types/mt_publication_metrics.hpp"
#include "__callable/__latency_fn_mt_publication_metrics_key.hpp"
#include "__callable/__latency_fn_mt_publication_metrics_int_counter.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_mt_publication_metrics_bool_counter.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_mt_publication_metrics_add_int.hpp"
#include "__callable/__latency_fn_mt_publication_metrics_int_counter.hpp"
#include "__callable/__latency_fn_proof_metrics_add_report_counter.hpp"
#include "__callable/__latency_fn_mt_publication_metrics_add_bool.hpp"
#include "__callable/__latency_fn_mt_publication_metrics_bool_counter.hpp"
#include "__callable/__latency_fn_proof_metrics_add_report_counter.hpp"
#include "__callable/__latency_fn_mt_publication_metrics_add_int.hpp"
#include "__callable/__latency_fn_mt_publication_metrics_key.hpp"
#include "__callable/__latency_fn_mt_publication_metrics_record_coordinator_selection.hpp"
#include "__callable/__latency_fn_mt_publication_metrics_add_int.hpp"
#include "__callable/__latency_fn_mt_publication_metrics_key.hpp"
#include "__callable/__latency_fn_mt_publication_metrics_record_coordinator_selection.hpp"
#include "__callable/__latency_fn_mt_publication_metrics_record_selected_publication_owner.hpp"
#include "__callable/__latency_fn_mt_publication_metrics_add_bool.hpp"
#include "__callable/__latency_fn_mt_publication_metrics_add_int.hpp"
#include "__callable/__latency_fn_mt_publication_metrics_key.hpp"
#include "__callable/__latency_fn_mt_publication_metrics_record_worker_gate.hpp"
namespace scpp { extern const int __latency_lines_mt_publication_metrics[]; }
namespace scpp {
bool_t mt_publication_metrics::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == mt_publication_metrics::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

}

namespace scpp { extern const int __latency_lines_mt_publication_metrics[]; }
namespace scpp {
string_t __latency_fn_mt_publication_metrics_key(const string_t& stageKey, const string_t& metricField) {
	SCPP_CALL_DEPTH_GUARD("mt_publication_metrics::key", "/tmp/scpp-edit-latency-20260919/app/compile/support/mt_publication_metrics.phs", __latency_lines_mt_publication_metrics[0]);
	return (cast<string_t>(stageKey) + string_t("_") + cast<string_t>(metricField));
}

}

namespace scpp { extern const int __latency_lines_mt_publication_metrics[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_mt_publication_metrics_int_counter(int_t<> value) {
	SCPP_CALL_DEPTH_GUARD("mt_publication_metrics::int_counter", "/tmp/scpp-edit-latency-20260919/app/compile/support/mt_publication_metrics.phs", __latency_lines_mt_publication_metrics[1]);
	return __latency_fn_structure_row_ids_uint32_from_int(value);
}

}

namespace scpp { extern const int __latency_lines_mt_publication_metrics[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_mt_publication_metrics_bool_counter(bool_t value) {
	SCPP_CALL_DEPTH_GUARD("mt_publication_metrics::bool_counter", "/tmp/scpp-edit-latency-20260919/app/compile/support/mt_publication_metrics.phs", __latency_lines_mt_publication_metrics[2]);
	if (static_cast<bool>(php::condition_truthy(value))) {
		return __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1));
	}
	return __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(0));
}

}

namespace scpp { extern const int __latency_lines_mt_publication_metrics[]; }
namespace scpp {
void __latency_fn_mt_publication_metrics_add_int(shared_p<CompilerProjectRunReport>& report, const string_t& metricKey, int_t<> value) {
	SCPP_CALL_DEPTH_GUARD("mt_publication_metrics::add_int", "/tmp/scpp-edit-latency-20260919/app/compile/support/mt_publication_metrics.phs", __latency_lines_mt_publication_metrics[3]);
	__latency_fn_proof_metrics_add_report_counter(report, metricKey, __latency_fn_mt_publication_metrics_int_counter(value));
}

}

namespace scpp { extern const int __latency_lines_mt_publication_metrics[]; }
namespace scpp {
void __latency_fn_mt_publication_metrics_add_bool(shared_p<CompilerProjectRunReport>& report, const string_t& metricKey, bool_t value) {
	SCPP_CALL_DEPTH_GUARD("mt_publication_metrics::add_bool", "/tmp/scpp-edit-latency-20260919/app/compile/support/mt_publication_metrics.phs", __latency_lines_mt_publication_metrics[4]);
	__latency_fn_proof_metrics_add_report_counter(report, metricKey, __latency_fn_mt_publication_metrics_bool_counter(bool_t(value)));
}

}

namespace scpp { extern const int __latency_lines_mt_publication_metrics[]; }
namespace scpp {
void __latency_fn_mt_publication_metrics_record_coordinator_selection(shared_p<CompilerProjectRunReport>& report, const string_t& stageKey) {
	SCPP_CALL_DEPTH_GUARD("mt_publication_metrics::record_coordinator_selection", "/tmp/scpp-edit-latency-20260919/app/compile/support/mt_publication_metrics.phs", __latency_lines_mt_publication_metrics[5]);
	__latency_fn_mt_publication_metrics_add_int(report, __latency_fn_mt_publication_metrics_key(stageKey, string_t("coordinator_selected")), static_cast<int_t<> >(1));
	__latency_fn_mt_publication_metrics_add_int(report, __latency_fn_mt_publication_metrics_key(stageKey, string_t("worker_selected")), static_cast<int_t<> >(0));
}

}

namespace scpp { extern const int __latency_lines_mt_publication_metrics[]; }
namespace scpp {
void __latency_fn_mt_publication_metrics_record_selected_publication_owner(shared_p<CompilerProjectRunReport>& report, const string_t& stageKey, bool_t workerSelected) {
	SCPP_CALL_DEPTH_GUARD("mt_publication_metrics::record_selected_publication_owner", "/tmp/scpp-edit-latency-20260919/app/compile/support/mt_publication_metrics.phs", __latency_lines_mt_publication_metrics[6]);
	if (static_cast<bool>(php::condition_truthy(workerSelected))) {
		__latency_fn_mt_publication_metrics_add_int(report, __latency_fn_mt_publication_metrics_key(stageKey, string_t("coordinator_selected")), static_cast<int_t<> >(0));
		__latency_fn_mt_publication_metrics_add_int(report, __latency_fn_mt_publication_metrics_key(stageKey, string_t("worker_selected")), static_cast<int_t<> >(1));
	}
	else {
		__latency_fn_mt_publication_metrics_record_coordinator_selection(report, stageKey);
	}
}

}

namespace scpp { extern const int __latency_lines_mt_publication_metrics[]; }
namespace scpp {
void __latency_fn_mt_publication_metrics_record_worker_gate(shared_p<CompilerProjectRunReport>& report, const string_t& stageKey, const string_t& blockedField, const string_t& upstreamWorkerReadyField, const string_t& upstreamCoordinatorBlockedField, const string_t& workerCandidateReadyField, const string_t& o3MeasurementRequiredField, const string_t& speedupClaimBlockedField, const string_t& payloadCopyBytesField, bool_t hasWorkerCandidate, bool_t upstreamWorkerReady, bool_t workerCandidateReady) {
	SCPP_CALL_DEPTH_GUARD("mt_publication_metrics::record_worker_gate", "/tmp/scpp-edit-latency-20260919/app/compile/support/mt_publication_metrics.phs", __latency_lines_mt_publication_metrics[7]);
	bool_t workerBlocked = required_cast<bool_t>((hasWorkerCandidate && (!upstreamWorkerReady)));
	bool_t upstreamCoordinatorBlocked = required_cast<bool_t>((hasWorkerCandidate && (!upstreamWorkerReady)));
	__latency_fn_mt_publication_metrics_add_bool(report, __latency_fn_mt_publication_metrics_key(stageKey, blockedField), bool_t(workerBlocked));
	__latency_fn_mt_publication_metrics_add_bool(report, __latency_fn_mt_publication_metrics_key(stageKey, upstreamWorkerReadyField), (hasWorkerCandidate && upstreamWorkerReady));
	__latency_fn_mt_publication_metrics_add_bool(report, __latency_fn_mt_publication_metrics_key(stageKey, upstreamCoordinatorBlockedField), bool_t(upstreamCoordinatorBlocked));
	__latency_fn_mt_publication_metrics_add_bool(report, __latency_fn_mt_publication_metrics_key(stageKey, workerCandidateReadyField), bool_t(workerCandidateReady));
	__latency_fn_mt_publication_metrics_add_bool(report, __latency_fn_mt_publication_metrics_key(stageKey, o3MeasurementRequiredField), bool_t(workerCandidateReady));
	__latency_fn_mt_publication_metrics_add_bool(report, __latency_fn_mt_publication_metrics_key(stageKey, speedupClaimBlockedField), bool_t(workerCandidateReady));
	__latency_fn_mt_publication_metrics_add_int(report, __latency_fn_mt_publication_metrics_key(stageKey, payloadCopyBytesField), static_cast<int_t<> >(0));
}

}
