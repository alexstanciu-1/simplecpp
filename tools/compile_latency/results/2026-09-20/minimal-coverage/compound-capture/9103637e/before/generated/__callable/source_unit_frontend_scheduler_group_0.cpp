#include <scpp/lang/php.hpp>
#include "__types/SourceUnitTable.hpp"
#include "__types/source_unit_frontend_scheduler.hpp"
#include "__callable/__latency_fn_source_unit_frontend_scheduler_uint32_from_int.hpp"
#include "__callable/__latency_fn_source_unit_frontend_scheduler_add_u32.hpp"
#include "__callable/__latency_fn_source_unit_frontend_scheduler_uint32_from_int.hpp"
#include "__callable/__latency_fn_pipeline_config_helpers_env_or_empty.hpp"
#include "__callable/__latency_fn_source_unit_frontend_scheduler_worker_count_for_sources.hpp"
#include "__callable/__latency_fn_pipeline_config_helpers_env_or_empty.hpp"
#include "__callable/__latency_fn_source_unit_frontend_scheduler_production_mt_enabled.hpp"
#include "__callable/__latency_fn_pipeline_config_helpers_env_or_empty.hpp"
#include "__callable/__latency_fn_source_unit_frontend_scheduler_production_mt_worker_pool_size.hpp"
#include "__callable/__latency_fn_source_unit_frontend_scheduler_configure_production_mt_worker_pool_if_requested.hpp"
#include "__callable/__latency_fn_source_unit_frontend_scheduler_production_mt_enabled.hpp"
#include "__callable/__latency_fn_source_unit_frontend_scheduler_production_mt_worker_pool_size.hpp"
#include "__callable/__latency_fn_pipeline_config_helpers_env_or_empty.hpp"
#include "__callable/__latency_fn_source_unit_frontend_scheduler_production_mt_min_payload_rows.hpp"
#include "__callable/__latency_fn_source_unit_frontend_scheduler_decision_status_not_required_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_source_unit_frontend_scheduler_decision_status_ready_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_source_unit_frontend_scheduler_decision_status_blocked_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_source_unit_frontend_scheduler_decision_status_required_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_source_unit_frontend_scheduler_trial_execution_scope_not_selected_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_kind_id.hpp"
namespace scpp { extern const int __latency_lines_source_unit_frontend_scheduler[]; }
namespace scpp {
bool_t source_unit_frontend_scheduler::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == source_unit_frontend_scheduler::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

}

namespace scpp { extern const int __latency_lines_source_unit_frontend_scheduler[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_source_unit_frontend_scheduler_uint32_from_int(int_t<> value) {
	SCPP_CALL_DEPTH_GUARD("source_unit_frontend_scheduler::uint32_from_int", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/source_unit_frontend_scheduler.phs", __latency_lines_source_unit_frontend_scheduler[0]);
	return cast<int_t<std::uint32_t>>(value);
}

}

namespace scpp { extern const int __latency_lines_source_unit_frontend_scheduler[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_source_unit_frontend_scheduler_add_u32(int_t<std::uint32_t> left, int_t<std::uint32_t> right) {
	SCPP_CALL_DEPTH_GUARD("source_unit_frontend_scheduler::add_u32", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/source_unit_frontend_scheduler.phs", __latency_lines_source_unit_frontend_scheduler[1]);
	return __latency_fn_source_unit_frontend_scheduler_uint32_from_int((cast<int_t<>>(left) + cast<int_t<>>(right)));
}

}

namespace scpp { extern const int __latency_lines_source_unit_frontend_scheduler[]; }
namespace scpp {
int_t<> __latency_fn_source_unit_frontend_scheduler_worker_count_for_sources(shared_p<SourceUnitTable> sourceUnits) {
	SCPP_CALL_DEPTH_GUARD("source_unit_frontend_scheduler::worker_count_for_sources", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/source_unit_frontend_scheduler.phs", __latency_lines_source_unit_frontend_scheduler[2]);
	if (static_cast<bool>((cast<int_t<>>(sourceUnits->source_unit_count) <= static_cast<int_t<> >(1)))) {
		return static_cast<int_t<> >(1);
	}
	string_t workerCountText = required_cast<string_t>(__latency_fn_pipeline_config_helpers_env_or_empty(string_t("SCPP_V2_SOURCE_UNIT_FRONTEND_WORKER_COUNT")));
	if (static_cast<bool>(php::condition_truthy(php::not_identical(workerCountText, string_t(""))))) {
		int_t<> workerCount = required_cast<int_t<>>(cast<int_t<>>(workerCountText));
		if (static_cast<bool>((workerCount > static_cast<int_t<> >(0)))) {
			return workerCount;
		}
	}
	string_t productionWorkerCountText = required_cast<string_t>(__latency_fn_pipeline_config_helpers_env_or_empty(string_t("SCPP_V2_PRODUCTION_MT_WORKERS")));
	if (static_cast<bool>(php::condition_truthy(php::not_identical(productionWorkerCountText, string_t(""))))) {
		int_t<> productionWorkerCount = required_cast<int_t<>>(cast<int_t<>>(productionWorkerCountText));
		if (static_cast<bool>((productionWorkerCount > static_cast<int_t<> >(0)))) {
			return productionWorkerCount;
		}
	}
	return static_cast<int_t<> >(2);
}

}

namespace scpp { extern const int __latency_lines_source_unit_frontend_scheduler[]; }
namespace scpp {
bool_t __latency_fn_source_unit_frontend_scheduler_production_mt_enabled() {
	SCPP_CALL_DEPTH_GUARD("source_unit_frontend_scheduler::production_mt_enabled", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/source_unit_frontend_scheduler.phs", __latency_lines_source_unit_frontend_scheduler[3]);
	return bool_t(php::identical(__latency_fn_pipeline_config_helpers_env_or_empty(string_t("SCPP_V2_PRODUCTION_MT")), string_t("1")));
}

}

namespace scpp { extern const int __latency_lines_source_unit_frontend_scheduler[]; }
namespace scpp {
int_t<> __latency_fn_source_unit_frontend_scheduler_production_mt_worker_pool_size() {
	SCPP_CALL_DEPTH_GUARD("source_unit_frontend_scheduler::production_mt_worker_pool_size", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/source_unit_frontend_scheduler.phs", __latency_lines_source_unit_frontend_scheduler[4]);
	string_t text = required_cast<string_t>(__latency_fn_pipeline_config_helpers_env_or_empty(string_t("SCPP_V2_PRODUCTION_MT_WORKER_POOL_SIZE")));
	if (static_cast<bool>(php::identical(text, string_t("")))) {
		return static_cast<int_t<> >(0);
	}
	int_t<> value = required_cast<int_t<>>(cast<int_t<>>(text));
	if (static_cast<bool>((value <= static_cast<int_t<> >(0)))) {
		return static_cast<int_t<> >(0);
	}
	return value;
}

}

namespace scpp { extern const int __latency_lines_source_unit_frontend_scheduler[]; }
namespace scpp {
void __latency_fn_source_unit_frontend_scheduler_configure_production_mt_worker_pool_if_requested() {
	SCPP_CALL_DEPTH_GUARD("source_unit_frontend_scheduler::configure_production_mt_worker_pool_if_requested", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/source_unit_frontend_scheduler.phs", __latency_lines_source_unit_frontend_scheduler[5]);
	if (static_cast<bool>((!__latency_fn_source_unit_frontend_scheduler_production_mt_enabled()))) {
		return;
	}
	int_t<> poolSize = required_cast<int_t<>>(__latency_fn_source_unit_frontend_scheduler_production_mt_worker_pool_size());
	if (static_cast<bool>((poolSize > static_cast<int_t<> >(0)))) {
		tasks::configure_default_worker_pool(poolSize);
	}
}

}

namespace scpp { extern const int __latency_lines_source_unit_frontend_scheduler[]; }
namespace scpp {
int_t<> __latency_fn_source_unit_frontend_scheduler_production_mt_min_payload_rows() {
	SCPP_CALL_DEPTH_GUARD("source_unit_frontend_scheduler::production_mt_min_payload_rows", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/source_unit_frontend_scheduler.phs", __latency_lines_source_unit_frontend_scheduler[6]);
	string_t text = required_cast<string_t>(__latency_fn_pipeline_config_helpers_env_or_empty(string_t("SCPP_V2_PRODUCTION_MT_MIN_PAYLOAD_ROWS")));
	if (static_cast<bool>(php::identical(text, string_t("")))) {
		return static_cast<int_t<> >(64);
	}
	int_t<> value = required_cast<int_t<>>(cast<int_t<>>(text));
	if (static_cast<bool>((value <= static_cast<int_t<> >(0)))) {
		return static_cast<int_t<> >(64);
	}
	return value;
}

}

namespace scpp { extern const int __latency_lines_source_unit_frontend_scheduler[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_source_unit_frontend_scheduler_decision_status_not_required_id() {
	SCPP_CALL_DEPTH_GUARD("source_unit_frontend_scheduler::decision_status_not_required_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/source_unit_frontend_scheduler.phs", __latency_lines_source_unit_frontend_scheduler[7]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_source_unit_frontend_scheduler[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_source_unit_frontend_scheduler_decision_status_ready_id() {
	SCPP_CALL_DEPTH_GUARD("source_unit_frontend_scheduler::decision_status_ready_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/source_unit_frontend_scheduler.phs", __latency_lines_source_unit_frontend_scheduler[8]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(2));
}

}

namespace scpp { extern const int __latency_lines_source_unit_frontend_scheduler[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_source_unit_frontend_scheduler_decision_status_blocked_id() {
	SCPP_CALL_DEPTH_GUARD("source_unit_frontend_scheduler::decision_status_blocked_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/source_unit_frontend_scheduler.phs", __latency_lines_source_unit_frontend_scheduler[9]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(3));
}

}

namespace scpp { extern const int __latency_lines_source_unit_frontend_scheduler[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_source_unit_frontend_scheduler_decision_status_required_id() {
	SCPP_CALL_DEPTH_GUARD("source_unit_frontend_scheduler::decision_status_required_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/source_unit_frontend_scheduler.phs", __latency_lines_source_unit_frontend_scheduler[10]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(4));
}

}

namespace scpp { extern const int __latency_lines_source_unit_frontend_scheduler[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_source_unit_frontend_scheduler_trial_execution_scope_not_selected_id() {
	SCPP_CALL_DEPTH_GUARD("source_unit_frontend_scheduler::trial_execution_scope_not_selected_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/source_unit_frontend_scheduler.phs", __latency_lines_source_unit_frontend_scheduler[11]);
	return __latency_fn_structure_row_ids_none_kind_id();
}

}
