#include <scpp/lang/php.hpp>
#include "__types/CompilerProjectRunReport.hpp"
#include "__callable/__latency_fn_pipeline_config_helpers_env_or_empty.hpp"
#include "__callable/__latency_fn_production_mt_heartbeat_heartbeat_path.hpp"
#include "__callable/__latency_fn_production_mt_heartbeat_heartbeat_path.hpp"
#include "__callable/__latency_fn_production_mt_heartbeat_live_enabled.hpp"
#include "__callable/__latency_fn_production_mt_heartbeat_proof_active.hpp"
#include "__callable/__latency_fn_production_mt_heartbeat_key.hpp"
#include "__callable/__latency_fn_production_mt_heartbeat_uint32_from_int.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_production_mt_heartbeat_append_live_line.hpp"
#include "__callable/__latency_fn_production_mt_heartbeat_heartbeat_path.hpp"
#include "__callable/__latency_fn_production_mt_heartbeat_live_enabled.hpp"
#include "__callable/__latency_fn_source_files_content_or_empty.hpp"
#include "__callable/__latency_fn_production_mt_heartbeat_append_live_event.hpp"
#include "__callable/__latency_fn_production_mt_heartbeat_append_live_line.hpp"
#include "__callable/__latency_fn_compiler_profile_events_now_us.hpp"
#include "__callable/__latency_fn_production_mt_heartbeat_append_live_event.hpp"
#include "__callable/__latency_fn_production_mt_heartbeat_key.hpp"
#include "__callable/__latency_fn_production_mt_heartbeat_proof_active.hpp"
#include "__callable/__latency_fn_production_mt_heartbeat_start.hpp"
#include "__callable/__latency_fn_production_mt_heartbeat_uint32_from_int.hpp"
#include "__callable/__latency_fn_proof_metrics_add_report_counter.hpp"
#include "__callable/__latency_fn_compiler_profile_events_elapsed_us_since.hpp"
#include "__callable/__latency_fn_production_mt_heartbeat_append_live_event.hpp"
#include "__callable/__latency_fn_production_mt_heartbeat_finish.hpp"
#include "__callable/__latency_fn_production_mt_heartbeat_key.hpp"
#include "__callable/__latency_fn_production_mt_heartbeat_proof_active.hpp"
#include "__callable/__latency_fn_production_mt_heartbeat_uint32_from_int.hpp"
#include "__callable/__latency_fn_proof_metrics_add_report_counter.hpp"
#include "__callable/__latency_fn_production_mt_heartbeat_echo_stage.hpp"
#include "__callable/__latency_fn_production_mt_heartbeat_key.hpp"
#include "__callable/__latency_fn_proof_metrics_report_value.hpp"
#include "__callable/__latency_fn_production_mt_heartbeat_echo_report.hpp"
#include "__callable/__latency_fn_production_mt_heartbeat_echo_stage.hpp"
#include "__callable/__latency_fn_production_mt_heartbeat_stage_backend_lowering_snapshot_build.hpp"
#include "__callable/__latency_fn_production_mt_heartbeat_stage_backend_lowering_worker_task.hpp"
#include "__callable/__latency_fn_production_mt_heartbeat_stage_capability_readiness_snapshot_build.hpp"
#include "__callable/__latency_fn_production_mt_heartbeat_stage_capability_readiness_worker_task.hpp"
#include "__callable/__latency_fn_production_mt_heartbeat_stage_emission_llvm_snapshot_build.hpp"
#include "__callable/__latency_fn_production_mt_heartbeat_stage_emission_llvm_worker_task.hpp"
#include "__callable/__latency_fn_production_mt_heartbeat_stage_frontend_worker_handoff.hpp"
#include "__callable/__latency_fn_production_mt_heartbeat_stage_object_output_snapshot_build.hpp"
#include "__callable/__latency_fn_production_mt_heartbeat_stage_object_output_worker_task.hpp"
#include "__callable/__latency_fn_production_mt_heartbeat_stage_proof_assertion_emission.hpp"
#include "__callable/__latency_fn_production_mt_heartbeat_stage_reference_contract_snapshot_build.hpp"
#include "__callable/__latency_fn_production_mt_heartbeat_stage_reference_contract_worker_task.hpp"
#include "__callable/__latency_fn_production_mt_heartbeat_stage_source_read_worker_probe.hpp"
#include "__callable/__latency_fn_production_mt_heartbeat_stage_storage_lifetime_snapshot_build.hpp"
#include "__callable/__latency_fn_production_mt_heartbeat_stage_storage_lifetime_worker_task.hpp"
#include "__callable/__latency_fn_production_mt_heartbeat_stage_symbol_fact_worker_recompute.hpp"
namespace scpp { extern const int __latency_lines_production_mt_heartbeat[]; }
namespace scpp {
string_t __latency_fn_production_mt_heartbeat_heartbeat_path() {
	SCPP_CALL_DEPTH_GUARD("production_mt_heartbeat::heartbeat_path", "/tmp/scpp-edit-latency-20260919/app/compile/support/production_mt_heartbeat.phs", __latency_lines_production_mt_heartbeat[17]);
	return __latency_fn_pipeline_config_helpers_env_or_empty(string_t("SCPP_V2_PRODUCTION_MT_HEARTBEAT_OUT"));
}

}

namespace scpp { extern const int __latency_lines_production_mt_heartbeat[]; }
namespace scpp {
bool_t __latency_fn_production_mt_heartbeat_live_enabled() {
	SCPP_CALL_DEPTH_GUARD("production_mt_heartbeat::live_enabled", "/tmp/scpp-edit-latency-20260919/app/compile/support/production_mt_heartbeat.phs", __latency_lines_production_mt_heartbeat[18]);
	return (__latency_fn_production_mt_heartbeat_proof_active() && php::not_identical(__latency_fn_production_mt_heartbeat_heartbeat_path(), string_t("")));
}

}

namespace scpp { extern const int __latency_lines_production_mt_heartbeat[]; }
namespace scpp {
string_t __latency_fn_production_mt_heartbeat_key(const string_t& stage, const string_t& suffix) {
	SCPP_CALL_DEPTH_GUARD("production_mt_heartbeat::key", "/tmp/scpp-edit-latency-20260919/app/compile/support/production_mt_heartbeat.phs", __latency_lines_production_mt_heartbeat[19]);
	return (string_t("production_mt_heartbeat_") + cast<string_t>(stage) + string_t("_") + cast<string_t>(suffix));
}

}

namespace scpp { extern const int __latency_lines_production_mt_heartbeat[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_production_mt_heartbeat_uint32_from_int(int_t<> value) {
	SCPP_CALL_DEPTH_GUARD("production_mt_heartbeat::uint32_from_int", "/tmp/scpp-edit-latency-20260919/app/compile/support/production_mt_heartbeat.phs", __latency_lines_production_mt_heartbeat[20]);
	return __latency_fn_structure_row_ids_uint32_from_int(value);
}

}

namespace scpp { extern const int __latency_lines_production_mt_heartbeat[]; }
namespace scpp {
void __latency_fn_production_mt_heartbeat_append_live_line(const string_t& line) {
	SCPP_CALL_DEPTH_GUARD("production_mt_heartbeat::append_live_line", "/tmp/scpp-edit-latency-20260919/app/compile/support/production_mt_heartbeat.phs", __latency_lines_production_mt_heartbeat[21]);
	if (static_cast<bool>((!__latency_fn_production_mt_heartbeat_live_enabled()))) {
		return;
	}
	string_t path = required_cast<string_t>(__latency_fn_production_mt_heartbeat_heartbeat_path());
	string_t previous = required_cast<string_t>(string_t(""));
	if (static_cast<bool>(php::condition_truthy(fs::exists(path)))) {
		previous = __latency_fn_source_files_content_or_empty(path);
	}
	int_t<> written = required_cast<int_t<>>(static_cast<int_t<> >(0));
	error_t err;
	if (static_cast<bool>((!php::take(written, err, fs::put(path, (cast<string_t>(previous) + cast<string_t>(line))))))) {
		return;
	}
}

}

namespace scpp { extern const int __latency_lines_production_mt_heartbeat[]; }
namespace scpp {
void __latency_fn_production_mt_heartbeat_append_live_event(const string_t& stage, const string_t& event, int_t<std::uint32_t> elapsedUs, int_t<std::uint32_t> rows) {
	SCPP_CALL_DEPTH_GUARD("production_mt_heartbeat::append_live_event", "/tmp/scpp-edit-latency-20260919/app/compile/support/production_mt_heartbeat.phs", __latency_lines_production_mt_heartbeat[22]);
	__latency_fn_production_mt_heartbeat_append_live_line((string_t("production_mt_heartbeat=stage:") + cast<string_t>(stage) + string_t(":event:") + cast<string_t>(event) + string_t(":elapsed_us=") + cast<string_t>(cast<int_t<>>(elapsedUs)) + string_t(":rows=") + cast<string_t>(cast<int_t<>>(rows)) + string_t("\n")));
}

}

namespace scpp { extern const int __latency_lines_production_mt_heartbeat[]; }
namespace scpp {
int_t<std::uint64_t> __latency_fn_production_mt_heartbeat_start(shared_p<CompilerProjectRunReport>& report, const string_t& stage, int_t<std::uint32_t> rows) {
	SCPP_CALL_DEPTH_GUARD("production_mt_heartbeat::start", "/tmp/scpp-edit-latency-20260919/app/compile/support/production_mt_heartbeat.phs", __latency_lines_production_mt_heartbeat[23]);
	int_t<std::uint64_t> started = required_cast<int_t<std::uint64_t>>(__latency_fn_compiler_profile_events_now_us());
	if (static_cast<bool>((!__latency_fn_production_mt_heartbeat_proof_active()))) {
		return cast<int_t<std::uint64_t>>(started);
	}
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_production_mt_heartbeat_key(stage, string_t("starts")), __latency_fn_production_mt_heartbeat_uint32_from_int(static_cast<int_t<> >(1)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_production_mt_heartbeat_key(stage, string_t("rows")), rows);
	__latency_fn_production_mt_heartbeat_append_live_event(stage, string_t("start"), __latency_fn_production_mt_heartbeat_uint32_from_int(static_cast<int_t<> >(0)), cast<int_t<std::uint32_t>>(rows));
	return cast<int_t<std::uint64_t>>(started);
}

}

namespace scpp { extern const int __latency_lines_production_mt_heartbeat[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_production_mt_heartbeat_finish(shared_p<CompilerProjectRunReport>& report, const string_t& stage, int_t<std::uint64_t> started, int_t<std::uint32_t> rows) {
	SCPP_CALL_DEPTH_GUARD("production_mt_heartbeat::finish", "/tmp/scpp-edit-latency-20260919/app/compile/support/production_mt_heartbeat.phs", __latency_lines_production_mt_heartbeat[24]);
	int_t<std::uint32_t> elapsedUs = required_cast<int_t<std::uint32_t>>(__latency_fn_compiler_profile_events_elapsed_us_since(started));
	if (static_cast<bool>((!__latency_fn_production_mt_heartbeat_proof_active()))) {
		return cast<int_t<std::uint32_t>>(elapsedUs);
	}
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_production_mt_heartbeat_key(stage, string_t("completions")), __latency_fn_production_mt_heartbeat_uint32_from_int(static_cast<int_t<> >(1)));
	__latency_fn_proof_metrics_add_report_counter(report, __latency_fn_production_mt_heartbeat_key(stage, string_t("elapsed_us")), elapsedUs);
	__latency_fn_production_mt_heartbeat_append_live_event(stage, string_t("end"), cast<int_t<std::uint32_t>>(elapsedUs), cast<int_t<std::uint32_t>>(rows));
	return cast<int_t<std::uint32_t>>(elapsedUs);
}

}

namespace scpp { extern const int __latency_lines_production_mt_heartbeat[]; }
namespace scpp {
void __latency_fn_production_mt_heartbeat_echo_stage(shared_p<CompilerProjectRunReport> report, const string_t& stage) {
	SCPP_CALL_DEPTH_GUARD("production_mt_heartbeat::echo_stage", "/tmp/scpp-edit-latency-20260919/app/compile/support/production_mt_heartbeat.phs", __latency_lines_production_mt_heartbeat[25]);
	php::echo_one(string_t("production_mt_proof_heartbeat_"));
	php::echo_one(stage);
	php::echo_one(string_t("_starts="));
	php::echo_one(cast<int_t<>>(__latency_fn_proof_metrics_report_value(report, __latency_fn_production_mt_heartbeat_key(stage, string_t("starts")))));
	php::echo_one(string_t("\n"));
	php::echo_one(string_t("production_mt_proof_heartbeat_"));
	php::echo_one(stage);
	php::echo_one(string_t("_completions="));
	php::echo_one(cast<int_t<>>(__latency_fn_proof_metrics_report_value(report, __latency_fn_production_mt_heartbeat_key(stage, string_t("completions")))));
	php::echo_one(string_t("\n"));
	php::echo_one(string_t("production_mt_proof_heartbeat_"));
	php::echo_one(stage);
	php::echo_one(string_t("_elapsed_us="));
	php::echo_one(cast<int_t<>>(__latency_fn_proof_metrics_report_value(report, __latency_fn_production_mt_heartbeat_key(stage, string_t("elapsed_us")))));
	php::echo_one(string_t("\n"));
	php::echo_one(string_t("production_mt_proof_heartbeat_"));
	php::echo_one(stage);
	php::echo_one(string_t("_rows="));
	php::echo_one(cast<int_t<>>(__latency_fn_proof_metrics_report_value(report, __latency_fn_production_mt_heartbeat_key(stage, string_t("rows")))));
	php::echo_one(string_t("\n"));
}

}

namespace scpp { extern const int __latency_lines_production_mt_heartbeat[]; }
namespace scpp {
void __latency_fn_production_mt_heartbeat_echo_report(shared_p<CompilerProjectRunReport> report) {
	SCPP_CALL_DEPTH_GUARD("production_mt_heartbeat::echo_report", "/tmp/scpp-edit-latency-20260919/app/compile/support/production_mt_heartbeat.phs", __latency_lines_production_mt_heartbeat[26]);
	__latency_fn_production_mt_heartbeat_echo_stage(report, __latency_fn_production_mt_heartbeat_stage_source_read_worker_probe());
	__latency_fn_production_mt_heartbeat_echo_stage(report, __latency_fn_production_mt_heartbeat_stage_frontend_worker_handoff());
	__latency_fn_production_mt_heartbeat_echo_stage(report, __latency_fn_production_mt_heartbeat_stage_symbol_fact_worker_recompute());
	__latency_fn_production_mt_heartbeat_echo_stage(report, __latency_fn_production_mt_heartbeat_stage_reference_contract_snapshot_build());
	__latency_fn_production_mt_heartbeat_echo_stage(report, __latency_fn_production_mt_heartbeat_stage_reference_contract_worker_task());
	__latency_fn_production_mt_heartbeat_echo_stage(report, __latency_fn_production_mt_heartbeat_stage_capability_readiness_snapshot_build());
	__latency_fn_production_mt_heartbeat_echo_stage(report, __latency_fn_production_mt_heartbeat_stage_capability_readiness_worker_task());
	__latency_fn_production_mt_heartbeat_echo_stage(report, __latency_fn_production_mt_heartbeat_stage_storage_lifetime_snapshot_build());
	__latency_fn_production_mt_heartbeat_echo_stage(report, __latency_fn_production_mt_heartbeat_stage_storage_lifetime_worker_task());
	__latency_fn_production_mt_heartbeat_echo_stage(report, __latency_fn_production_mt_heartbeat_stage_backend_lowering_snapshot_build());
	__latency_fn_production_mt_heartbeat_echo_stage(report, __latency_fn_production_mt_heartbeat_stage_backend_lowering_worker_task());
	__latency_fn_production_mt_heartbeat_echo_stage(report, __latency_fn_production_mt_heartbeat_stage_emission_llvm_snapshot_build());
	__latency_fn_production_mt_heartbeat_echo_stage(report, __latency_fn_production_mt_heartbeat_stage_emission_llvm_worker_task());
	__latency_fn_production_mt_heartbeat_echo_stage(report, __latency_fn_production_mt_heartbeat_stage_object_output_snapshot_build());
	__latency_fn_production_mt_heartbeat_echo_stage(report, __latency_fn_production_mt_heartbeat_stage_object_output_worker_task());
	__latency_fn_production_mt_heartbeat_echo_stage(report, __latency_fn_production_mt_heartbeat_stage_proof_assertion_emission());
}

}
