#include <scpp/lang/php.hpp>
#include "__types/ProjectManifest.hpp"
#include "__types/ProjectManifestSourceRow.hpp"
#include "__types/SourceUnitTable.hpp"
#include "__types/SourceUnitTableRow.hpp"
#include "__callable/__latency_fn_pipeline_config_helpers_env_or_empty.hpp"
#include "__callable/__latency_fn_source_units_source_read_worker_count_for_manifest.hpp"
#include "__callable/__latency_fn_pipeline_config_helpers_env_or_empty.hpp"
#include "__callable/__latency_fn_source_units_source_read_worker_pool_size.hpp"
#include "__callable/__latency_fn_pipeline_config_helpers_env_or_empty.hpp"
#include "__callable/__latency_fn_source_units_source_read_worker_publication_enabled.hpp"
#include "__callable/__latency_fn_pipeline_config_helpers_env_or_empty.hpp"
#include "__callable/__latency_fn_source_units_source_read_worker_try_lock_publication_enabled.hpp"
#include "__callable/__latency_fn_pipeline_config_helpers_env_or_empty.hpp"
#include "__callable/__latency_fn_source_units_source_read_hash_audit_enabled.hpp"
#include "__callable/__latency_fn_pipeline_config_helpers_env_or_empty.hpp"
#include "__callable/__latency_fn_source_units_source_read_light_metrics_enabled.hpp"
#include "__callable/__latency_fn_pipeline_config_helpers_env_or_empty.hpp"
#include "__callable/__latency_fn_source_units_source_read_worker_publish_batch_cap.hpp"
#include "__callable/__latency_fn_source_units_line_count.hpp"
#include "__callable/__latency_fn_source_units_content_debug_key.hpp"
#include "__callable/__latency_fn_source_identity_source_unit_key_from_table.hpp"
#include "__callable/__latency_fn_source_units_incremental_owner_key.hpp"
#include "__callable/__latency_fn_source_units_dirty_signal_source_text_or_manifest_id.hpp"
#include "__callable/__latency_fn_source_units_line_count.hpp"
#include "__callable/__latency_fn_source_units_partition_source_unit_id.hpp"
#include "__callable/__latency_fn_source_units_reuse_signal_source_unit_rows_id.hpp"
#include "__callable/__latency_fn_source_units_table_row.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
namespace scpp { extern const int __latency_lines_source_units[]; }
namespace scpp {
int_t<> __latency_fn_source_units_source_read_worker_count_for_manifest(shared_p<ProjectManifest> manifest) {
	SCPP_CALL_DEPTH_GUARD("source_units::source_read_worker_count_for_manifest", "/tmp/scpp-edit-latency-20260919/app/compile/model/source_units.phs", __latency_lines_source_units[14]);
	if (static_cast<bool>((cast<int_t<>>(manifest->source_count) <= static_cast<int_t<> >(1)))) {
		return static_cast<int_t<> >(1);
	}
	string_t workerCountText = required_cast<string_t>(__latency_fn_pipeline_config_helpers_env_or_empty(string_t("SCPP_V2_SOURCE_READ_WORKER_COUNT")));
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

namespace scpp { extern const int __latency_lines_source_units[]; }
namespace scpp {
int_t<> __latency_fn_source_units_source_read_worker_pool_size() {
	SCPP_CALL_DEPTH_GUARD("source_units::source_read_worker_pool_size", "/tmp/scpp-edit-latency-20260919/app/compile/model/source_units.phs", __latency_lines_source_units[15]);
	string_t poolSizeText = required_cast<string_t>(__latency_fn_pipeline_config_helpers_env_or_empty(string_t("SCPP_V2_SOURCE_READ_WORKER_POOL_SIZE")));
	if (static_cast<bool>(php::condition_truthy(php::not_identical(poolSizeText, string_t(""))))) {
		int_t<> poolSize = required_cast<int_t<>>(cast<int_t<>>(poolSizeText));
		if (static_cast<bool>((poolSize > static_cast<int_t<> >(0)))) {
			return poolSize;
		}
	}
	string_t productionPoolSizeText = required_cast<string_t>(__latency_fn_pipeline_config_helpers_env_or_empty(string_t("SCPP_V2_PRODUCTION_MT_WORKER_POOL_SIZE")));
	if (static_cast<bool>(php::condition_truthy(php::not_identical(productionPoolSizeText, string_t(""))))) {
		int_t<> productionPoolSize = required_cast<int_t<>>(cast<int_t<>>(productionPoolSizeText));
		if (static_cast<bool>((productionPoolSize > static_cast<int_t<> >(0)))) {
			return productionPoolSize;
		}
	}
	return static_cast<int_t<> >(0);
}

}

namespace scpp { extern const int __latency_lines_source_units[]; }
namespace scpp {
bool_t __latency_fn_source_units_source_read_worker_publication_enabled() {
	SCPP_CALL_DEPTH_GUARD("source_units::source_read_worker_publication_enabled", "/tmp/scpp-edit-latency-20260919/app/compile/model/source_units.phs", __latency_lines_source_units[16]);
	return bool_t(php::identical(__latency_fn_pipeline_config_helpers_env_or_empty(string_t("SCPP_V2_SOURCE_READ_WORKER_PUBLICATION")), string_t("1")));
}

}

namespace scpp { extern const int __latency_lines_source_units[]; }
namespace scpp {
bool_t __latency_fn_source_units_source_read_worker_try_lock_publication_enabled() {
	SCPP_CALL_DEPTH_GUARD("source_units::source_read_worker_try_lock_publication_enabled", "/tmp/scpp-edit-latency-20260919/app/compile/model/source_units.phs", __latency_lines_source_units[17]);
	return bool_t(php::identical(__latency_fn_pipeline_config_helpers_env_or_empty(string_t("SCPP_V2_SOURCE_READ_WORKER_TRY_LOCK_PUBLICATION")), string_t("1")));
}

}

namespace scpp { extern const int __latency_lines_source_units[]; }
namespace scpp {
bool_t __latency_fn_source_units_source_read_hash_audit_enabled() {
	SCPP_CALL_DEPTH_GUARD("source_units::source_read_hash_audit_enabled", "/tmp/scpp-edit-latency-20260919/app/compile/model/source_units.phs", __latency_lines_source_units[18]);
	return bool_t(php::not_identical(__latency_fn_pipeline_config_helpers_env_or_empty(string_t("SCPP_V2_SOURCE_READ_HASH_AUDIT")), string_t("0")));
}

}

namespace scpp { extern const int __latency_lines_source_units[]; }
namespace scpp {
bool_t __latency_fn_source_units_source_read_light_metrics_enabled() {
	SCPP_CALL_DEPTH_GUARD("source_units::source_read_light_metrics_enabled", "/tmp/scpp-edit-latency-20260919/app/compile/model/source_units.phs", __latency_lines_source_units[19]);
	return bool_t(php::identical(__latency_fn_pipeline_config_helpers_env_or_empty(string_t("SCPP_V2_SOURCE_READ_LIGHT_METRICS")), string_t("1")));
}

}

namespace scpp { extern const int __latency_lines_source_units[]; }
namespace scpp {
int_t<> __latency_fn_source_units_source_read_worker_publish_batch_cap() {
	SCPP_CALL_DEPTH_GUARD("source_units::source_read_worker_publish_batch_cap", "/tmp/scpp-edit-latency-20260919/app/compile/model/source_units.phs", __latency_lines_source_units[20]);
	string_t capText = required_cast<string_t>(__latency_fn_pipeline_config_helpers_env_or_empty(string_t("SCPP_V2_SOURCE_READ_WORKER_PUBLISH_BATCH_CAP")));
	if (static_cast<bool>(php::condition_truthy(php::not_identical(capText, string_t(""))))) {
		int_t<> cap = required_cast<int_t<>>(cast<int_t<>>(capText));
		if (static_cast<bool>((cap > static_cast<int_t<> >(0)))) {
			return cap;
		}
	}
	return static_cast<int_t<> >(0);
}

}

namespace scpp { extern const int __latency_lines_source_units[]; }
namespace scpp {
int_t<> __latency_fn_source_units_line_count(const string_t& source) {
	SCPP_CALL_DEPTH_GUARD("source_units::line_count", "/tmp/scpp-edit-latency-20260919/app/compile/model/source_units.phs", __latency_lines_source_units[21]);
	int_t<> length = required_cast<int_t<>>(str::byte_length(source));
	if (static_cast<bool>(php::identical(length, static_cast<int_t<> >(0)))) {
		return static_cast<int_t<> >(0);
	}
	int_t<> lines = required_cast<int_t<>>(static_cast<int_t<> >(1));
	int_t<> offset = required_cast<int_t<>>(static_cast<int_t<> >(0));
	while (static_cast<bool>((offset < length))) {
		if (static_cast<bool>(php::identical(php::string_byte_at(source, offset), static_cast<int_t<> >(10)))) {
			lines = (lines + static_cast<int_t<> >(1));
		}
		offset = (offset + static_cast<int_t<> >(1));
	}
	return lines;
}

}

namespace scpp { extern const int __latency_lines_source_units[]; }
namespace scpp {
string_t __latency_fn_source_units_content_debug_key(SourceUnitTableRow row) {
	SCPP_CALL_DEPTH_GUARD("source_units::content_debug_key", "/tmp/scpp-edit-latency-20260919/app/compile/model/source_units.phs", __latency_lines_source_units[22]);
	return (string_t("source_length:") + cast<string_t>(row->source_length) + string_t(":lines:") + cast<string_t>(row->line_count));
}

}

namespace scpp { extern const int __latency_lines_source_units[]; }
namespace scpp {
string_t __latency_fn_source_units_incremental_owner_key(shared_p<SourceUnitTable> table, SourceUnitTableRow row) {
	SCPP_CALL_DEPTH_GUARD("source_units::incremental_owner_key", "/tmp/scpp-edit-latency-20260919/app/compile/model/source_units.phs", __latency_lines_source_units[23]);
	return (string_t("source_unit:") + cast<string_t>(__latency_fn_source_identity_source_unit_key_from_table(table, row)));
}

}

namespace scpp { extern const int __latency_lines_source_units[]; }
namespace scpp {
SourceUnitTableRow __latency_fn_source_units_table_row(ProjectManifestSourceRow source, const string_t& sourceText) {
	SCPP_CALL_DEPTH_GUARD("source_units::table_row", "/tmp/scpp-edit-latency-20260919/app/compile/model/source_units.phs", __latency_lines_source_units[24]);
	SourceUnitTableRow row = SourceUnitTableRow{};
	row->source_unit_id = source->source_id;
	row->language_id = source->language_id;
	row->status_id = source->status_id;
	row->dirty_signal_id = __latency_fn_source_units_dirty_signal_source_text_or_manifest_id();
	row->reuse_signal_id = __latency_fn_source_units_reuse_signal_source_unit_rows_id();
	row->partition_id = __latency_fn_source_units_partition_source_unit_id();
	row->source_unit_key_id = source->source_unit_key_id;
	row->relative_path_id = source->relative_path_id;
	row->path_id = source->relative_path_id;
	row->source_length = __latency_fn_structure_row_ids_uint32_from_int(str::byte_length(sourceText));
	row->line_count = __latency_fn_structure_row_ids_uint32_from_int(__latency_fn_source_units_line_count(sourceText));
	return row;
}

}
