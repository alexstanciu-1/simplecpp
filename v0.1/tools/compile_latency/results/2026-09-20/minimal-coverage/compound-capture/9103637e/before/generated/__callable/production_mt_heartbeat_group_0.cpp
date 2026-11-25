#include <scpp/lang/php.hpp>
#include "__types/production_mt_heartbeat.hpp"
#include "__callable/__latency_fn_production_mt_heartbeat_stage_frontend_worker_handoff.hpp"
#include "__callable/__latency_fn_production_mt_heartbeat_stage_source_read_worker_probe.hpp"
#include "__callable/__latency_fn_production_mt_heartbeat_stage_symbol_fact_worker_recompute.hpp"
#include "__callable/__latency_fn_production_mt_heartbeat_stage_reference_contract_snapshot_build.hpp"
#include "__callable/__latency_fn_production_mt_heartbeat_stage_reference_contract_worker_task.hpp"
#include "__callable/__latency_fn_production_mt_heartbeat_stage_capability_readiness_snapshot_build.hpp"
#include "__callable/__latency_fn_production_mt_heartbeat_stage_capability_readiness_worker_task.hpp"
#include "__callable/__latency_fn_production_mt_heartbeat_stage_storage_lifetime_snapshot_build.hpp"
#include "__callable/__latency_fn_production_mt_heartbeat_stage_storage_lifetime_worker_task.hpp"
#include "__callable/__latency_fn_production_mt_heartbeat_stage_backend_lowering_snapshot_build.hpp"
#include "__callable/__latency_fn_production_mt_heartbeat_stage_backend_lowering_worker_task.hpp"
#include "__callable/__latency_fn_production_mt_heartbeat_stage_emission_llvm_snapshot_build.hpp"
#include "__callable/__latency_fn_production_mt_heartbeat_stage_emission_llvm_worker_task.hpp"
#include "__callable/__latency_fn_production_mt_heartbeat_stage_object_output_snapshot_build.hpp"
#include "__callable/__latency_fn_production_mt_heartbeat_stage_object_output_worker_task.hpp"
#include "__callable/__latency_fn_production_mt_heartbeat_stage_proof_assertion_emission.hpp"
#include "__callable/__latency_fn_pipeline_config_helpers_env_or_empty.hpp"
#include "__callable/__latency_fn_production_mt_heartbeat_proof_active.hpp"
namespace scpp { extern const int __latency_lines_production_mt_heartbeat[]; }
namespace scpp {
bool_t production_mt_heartbeat::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == production_mt_heartbeat::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

}

namespace scpp { extern const int __latency_lines_production_mt_heartbeat[]; }
namespace scpp {
string_t __latency_fn_production_mt_heartbeat_stage_frontend_worker_handoff() {
	SCPP_CALL_DEPTH_GUARD("production_mt_heartbeat::stage_frontend_worker_handoff", "/tmp/scpp-edit-latency-20260919/app/compile/support/production_mt_heartbeat.phs", __latency_lines_production_mt_heartbeat[0]);
	return string_t("frontend_worker_handoff");
}

}

namespace scpp { extern const int __latency_lines_production_mt_heartbeat[]; }
namespace scpp {
string_t __latency_fn_production_mt_heartbeat_stage_source_read_worker_probe() {
	SCPP_CALL_DEPTH_GUARD("production_mt_heartbeat::stage_source_read_worker_probe", "/tmp/scpp-edit-latency-20260919/app/compile/support/production_mt_heartbeat.phs", __latency_lines_production_mt_heartbeat[1]);
	return string_t("source_read_worker_probe");
}

}

namespace scpp { extern const int __latency_lines_production_mt_heartbeat[]; }
namespace scpp {
string_t __latency_fn_production_mt_heartbeat_stage_symbol_fact_worker_recompute() {
	SCPP_CALL_DEPTH_GUARD("production_mt_heartbeat::stage_symbol_fact_worker_recompute", "/tmp/scpp-edit-latency-20260919/app/compile/support/production_mt_heartbeat.phs", __latency_lines_production_mt_heartbeat[2]);
	return string_t("symbol_fact_worker_recompute");
}

}

namespace scpp { extern const int __latency_lines_production_mt_heartbeat[]; }
namespace scpp {
string_t __latency_fn_production_mt_heartbeat_stage_reference_contract_snapshot_build() {
	SCPP_CALL_DEPTH_GUARD("production_mt_heartbeat::stage_reference_contract_snapshot_build", "/tmp/scpp-edit-latency-20260919/app/compile/support/production_mt_heartbeat.phs", __latency_lines_production_mt_heartbeat[3]);
	return string_t("reference_contract_snapshot_build");
}

}

namespace scpp { extern const int __latency_lines_production_mt_heartbeat[]; }
namespace scpp {
string_t __latency_fn_production_mt_heartbeat_stage_reference_contract_worker_task() {
	SCPP_CALL_DEPTH_GUARD("production_mt_heartbeat::stage_reference_contract_worker_task", "/tmp/scpp-edit-latency-20260919/app/compile/support/production_mt_heartbeat.phs", __latency_lines_production_mt_heartbeat[4]);
	return string_t("reference_contract_worker_task");
}

}

namespace scpp { extern const int __latency_lines_production_mt_heartbeat[]; }
namespace scpp {
string_t __latency_fn_production_mt_heartbeat_stage_capability_readiness_snapshot_build() {
	SCPP_CALL_DEPTH_GUARD("production_mt_heartbeat::stage_capability_readiness_snapshot_build", "/tmp/scpp-edit-latency-20260919/app/compile/support/production_mt_heartbeat.phs", __latency_lines_production_mt_heartbeat[5]);
	return string_t("capability_readiness_snapshot_build");
}

}

namespace scpp { extern const int __latency_lines_production_mt_heartbeat[]; }
namespace scpp {
string_t __latency_fn_production_mt_heartbeat_stage_capability_readiness_worker_task() {
	SCPP_CALL_DEPTH_GUARD("production_mt_heartbeat::stage_capability_readiness_worker_task", "/tmp/scpp-edit-latency-20260919/app/compile/support/production_mt_heartbeat.phs", __latency_lines_production_mt_heartbeat[6]);
	return string_t("capability_readiness_worker_task");
}

}

namespace scpp { extern const int __latency_lines_production_mt_heartbeat[]; }
namespace scpp {
string_t __latency_fn_production_mt_heartbeat_stage_storage_lifetime_snapshot_build() {
	SCPP_CALL_DEPTH_GUARD("production_mt_heartbeat::stage_storage_lifetime_snapshot_build", "/tmp/scpp-edit-latency-20260919/app/compile/support/production_mt_heartbeat.phs", __latency_lines_production_mt_heartbeat[7]);
	return string_t("storage_lifetime_snapshot_build");
}

}

namespace scpp { extern const int __latency_lines_production_mt_heartbeat[]; }
namespace scpp {
string_t __latency_fn_production_mt_heartbeat_stage_storage_lifetime_worker_task() {
	SCPP_CALL_DEPTH_GUARD("production_mt_heartbeat::stage_storage_lifetime_worker_task", "/tmp/scpp-edit-latency-20260919/app/compile/support/production_mt_heartbeat.phs", __latency_lines_production_mt_heartbeat[8]);
	return string_t("storage_lifetime_worker_task");
}

}

namespace scpp { extern const int __latency_lines_production_mt_heartbeat[]; }
namespace scpp {
string_t __latency_fn_production_mt_heartbeat_stage_backend_lowering_snapshot_build() {
	SCPP_CALL_DEPTH_GUARD("production_mt_heartbeat::stage_backend_lowering_snapshot_build", "/tmp/scpp-edit-latency-20260919/app/compile/support/production_mt_heartbeat.phs", __latency_lines_production_mt_heartbeat[9]);
	return string_t("backend_lowering_snapshot_build");
}

}

namespace scpp { extern const int __latency_lines_production_mt_heartbeat[]; }
namespace scpp {
string_t __latency_fn_production_mt_heartbeat_stage_backend_lowering_worker_task() {
	SCPP_CALL_DEPTH_GUARD("production_mt_heartbeat::stage_backend_lowering_worker_task", "/tmp/scpp-edit-latency-20260919/app/compile/support/production_mt_heartbeat.phs", __latency_lines_production_mt_heartbeat[10]);
	return string_t("backend_lowering_worker_task");
}

}

namespace scpp { extern const int __latency_lines_production_mt_heartbeat[]; }
namespace scpp {
string_t __latency_fn_production_mt_heartbeat_stage_emission_llvm_snapshot_build() {
	SCPP_CALL_DEPTH_GUARD("production_mt_heartbeat::stage_emission_llvm_snapshot_build", "/tmp/scpp-edit-latency-20260919/app/compile/support/production_mt_heartbeat.phs", __latency_lines_production_mt_heartbeat[11]);
	return string_t("emission_llvm_snapshot_build");
}

}

namespace scpp { extern const int __latency_lines_production_mt_heartbeat[]; }
namespace scpp {
string_t __latency_fn_production_mt_heartbeat_stage_emission_llvm_worker_task() {
	SCPP_CALL_DEPTH_GUARD("production_mt_heartbeat::stage_emission_llvm_worker_task", "/tmp/scpp-edit-latency-20260919/app/compile/support/production_mt_heartbeat.phs", __latency_lines_production_mt_heartbeat[12]);
	return string_t("emission_llvm_worker_task");
}

}

namespace scpp { extern const int __latency_lines_production_mt_heartbeat[]; }
namespace scpp {
string_t __latency_fn_production_mt_heartbeat_stage_object_output_snapshot_build() {
	SCPP_CALL_DEPTH_GUARD("production_mt_heartbeat::stage_object_output_snapshot_build", "/tmp/scpp-edit-latency-20260919/app/compile/support/production_mt_heartbeat.phs", __latency_lines_production_mt_heartbeat[13]);
	return string_t("object_output_snapshot_build");
}

}

namespace scpp { extern const int __latency_lines_production_mt_heartbeat[]; }
namespace scpp {
string_t __latency_fn_production_mt_heartbeat_stage_object_output_worker_task() {
	SCPP_CALL_DEPTH_GUARD("production_mt_heartbeat::stage_object_output_worker_task", "/tmp/scpp-edit-latency-20260919/app/compile/support/production_mt_heartbeat.phs", __latency_lines_production_mt_heartbeat[14]);
	return string_t("object_output_worker_task");
}

}

namespace scpp { extern const int __latency_lines_production_mt_heartbeat[]; }
namespace scpp {
string_t __latency_fn_production_mt_heartbeat_stage_proof_assertion_emission() {
	SCPP_CALL_DEPTH_GUARD("production_mt_heartbeat::stage_proof_assertion_emission", "/tmp/scpp-edit-latency-20260919/app/compile/support/production_mt_heartbeat.phs", __latency_lines_production_mt_heartbeat[15]);
	return string_t("proof_assertion_emission");
}

}

namespace scpp { extern const int __latency_lines_production_mt_heartbeat[]; }
namespace scpp {
bool_t __latency_fn_production_mt_heartbeat_proof_active() {
	SCPP_CALL_DEPTH_GUARD("production_mt_heartbeat::proof_active", "/tmp/scpp-edit-latency-20260919/app/compile/support/production_mt_heartbeat.phs", __latency_lines_production_mt_heartbeat[16]);
	return bool_t(php::identical(__latency_fn_pipeline_config_helpers_env_or_empty(string_t("SCPP_V2_PRODUCTION_MT_PROOF")), string_t("1")));
}

}
