#include <scpp/lang/php.hpp>
#include "__types/CompilerProjectRunReport.hpp"
#include "__types/CompilerProjectRunRow.hpp"
#include "__types/ProjectManifest.hpp"
#include "__types/SourceReadTable.hpp"
#include "__types/SourceUnitTable.hpp"
#include "__types/SourceUnitTableRow.hpp"
#include "__callable/__latency_fn_proof_metrics_append_unique_report_counter.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_table_source_text_adopted.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_table_source_text_payload_copy_bytes.hpp"
#include "__callable/__latency_fn_source_units_record_source_unit_table_text_adoption_metrics.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint32_from_int.hpp"
#include "__callable/__latency_fn_proof_metrics_append_unique_report_counter.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_read_metrics_elapsed_us.hpp"
#include "__callable/__latency_fn_source_units_record_source_read_metrics_elapsed.hpp"
#include "__callable/__latency_fn_proof_metrics_append_unique_report_counter.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_unit_table_materialization_elapsed_us.hpp"
#include "__callable/__latency_fn_source_units_record_source_unit_table_materialization_elapsed.hpp"
#include "__callable/__latency_fn_source_units_source_read_table_from_manifest.hpp"
#include "__callable/__latency_fn_source_units_table_from_manifest.hpp"
#include "__callable/__latency_fn_source_units_table_from_source_read_table_adopting_texts.hpp"
#include "__callable/__latency_fn_source_units_row_by_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_dense_index.hpp"
#include "__callable/__latency_fn_structure_row_ids_has_dense_id.hpp"
#include "__callable/__latency_fn_source_units_source_text_by_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_dense_index.hpp"
#include "__callable/__latency_fn_structure_row_ids_has_dense_id.hpp"
namespace scpp { extern const int __latency_lines_source_units[]; }
namespace scpp {
void __latency_fn_source_units_record_source_unit_table_text_adoption_metrics(shared_p<CompilerProjectRunReport>& report) {
	SCPP_CALL_DEPTH_GUARD("source_units::record_source_unit_table_text_adoption_metrics", "/tmp/scpp-edit-latency-20260919/app/compile/model/source_units.phs", __latency_lines_source_units[58]);
	__latency_fn_proof_metrics_append_unique_report_counter(report, __latency_fn_proof_metrics_key_source_unit_table_source_text_payload_copy_bytes(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(0)));
	__latency_fn_proof_metrics_append_unique_report_counter(report, __latency_fn_proof_metrics_key_source_unit_table_source_text_adopted(), __latency_fn_structure_row_ids_uint32_from_int(static_cast<int_t<> >(1)));
}

}

namespace scpp { extern const int __latency_lines_source_units[]; }
namespace scpp {
void __latency_fn_source_units_record_source_read_metrics_elapsed(shared_p<CompilerProjectRunReport>& report, int_t<std::uint32_t> elapsedUs) {
	SCPP_CALL_DEPTH_GUARD("source_units::record_source_read_metrics_elapsed", "/tmp/scpp-edit-latency-20260919/app/compile/model/source_units.phs", __latency_lines_source_units[59]);
	__latency_fn_proof_metrics_append_unique_report_counter(report, __latency_fn_proof_metrics_key_source_read_metrics_elapsed_us(), elapsedUs);
}

}

namespace scpp { extern const int __latency_lines_source_units[]; }
namespace scpp {
void __latency_fn_source_units_record_source_unit_table_materialization_elapsed(shared_p<CompilerProjectRunReport>& report, int_t<std::uint32_t> elapsedUs) {
	SCPP_CALL_DEPTH_GUARD("source_units::record_source_unit_table_materialization_elapsed", "/tmp/scpp-edit-latency-20260919/app/compile/model/source_units.phs", __latency_lines_source_units[60]);
	__latency_fn_proof_metrics_append_unique_report_counter(report, __latency_fn_proof_metrics_key_source_unit_table_materialization_elapsed_us(), elapsedUs);
}

}

namespace scpp { extern const int __latency_lines_source_units[]; }
namespace scpp {
shared_p<SourceUnitTable> __latency_fn_source_units_table_from_manifest(const shared_p<ProjectManifest>& manifest) {
	SCPP_CALL_DEPTH_GUARD("source_units::table_from_manifest", "/tmp/scpp-edit-latency-20260919/app/compile/model/source_units.phs", __latency_lines_source_units[61]);
	shared_p<SourceReadTable> sourceReads = __latency_fn_source_units_source_read_table_from_manifest(manifest);
	return __latency_fn_source_units_table_from_source_read_table_adopting_texts(manifest, sourceReads);
}

}

namespace scpp { extern const int __latency_lines_source_units[]; }
namespace scpp {
SourceUnitTableRow __latency_fn_source_units_row_by_id(shared_p<SourceUnitTable> table, int_t<std::uint32_t> sourceUnitId) {
	SCPP_CALL_DEPTH_GUARD("source_units::row_by_id", "/tmp/scpp-edit-latency-20260919/app/compile/model/source_units.phs", __latency_lines_source_units[62]);
	if (static_cast<bool>(php::condition_truthy(__latency_fn_structure_row_ids_has_dense_id(sourceUnitId, table->source_unit_count)))) {
		SourceUnitTableRow row = table->rows[__latency_fn_structure_row_ids_dense_index(sourceUnitId)];
		if (static_cast<bool>(php::identical(row->source_unit_id, sourceUnitId))) {
			return row;
		}
	}
	auto __latency_local_0 = table->rows;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(row->source_unit_id, sourceUnitId))) {
			return row;
		}
	}
	SourceUnitTableRow empty = SourceUnitTableRow{};
	return empty;
}

}

namespace scpp { extern const int __latency_lines_source_units[]; }
namespace scpp {
string_t __latency_fn_source_units_source_text_by_id(shared_p<SourceUnitTable> table, int_t<std::uint32_t> sourceUnitId) {
	SCPP_CALL_DEPTH_GUARD("source_units::source_text_by_id", "/tmp/scpp-edit-latency-20260919/app/compile/model/source_units.phs", __latency_lines_source_units[63]);
	if (static_cast<bool>(php::condition_truthy(__latency_fn_structure_row_ids_has_dense_id(sourceUnitId, php::count(table->source_texts))))) {
		return table->source_texts[__latency_fn_structure_row_ids_dense_index(sourceUnitId)];
	}
	return string_t("");
}

}
