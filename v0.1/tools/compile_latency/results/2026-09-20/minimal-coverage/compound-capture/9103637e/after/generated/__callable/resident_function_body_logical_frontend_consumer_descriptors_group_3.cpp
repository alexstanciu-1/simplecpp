#include <scpp/lang/php.hpp>
#include "__types/CompilerProjectRunReport.hpp"
#include "__types/PerformanceMemoryEstimateRow.hpp"
#include "__types/ResidentFunctionBodyLogicalFrontendBodySpanDescriptorRow.hpp"
#include "__types/ResidentFunctionBodyLogicalFrontendConsumerDescriptorRow.hpp"
#include "__types/ResidentFunctionBodyLogicalFrontendViewRow.hpp"
#include "__types/performance_memory_estimates.hpp"
#include "__types/resident_function_body_logical_frontend_consumer_descriptors.hpp"
#include "__types/resident_function_body_logical_frontend_views.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_consumer_descriptors_descriptor_by_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_dense_index.hpp"
#include "__callable/__latency_fn_structure_row_ids_has_dense_id.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_consumer_descriptors_max_source_unit_id_from_ready_descriptors.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_consumer_descriptors_status_ready_id.hpp"
#include "__callable/__latency_fn_resident_definition_granularity_reserve_none_id_slots.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_consumer_descriptors_build_descriptor_lookup_ids.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_consumer_descriptors_status_ready_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_dense_index.hpp"
#include "__callable/__latency_fn_structure_row_ids_has_dense_id.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_consumer_descriptors_append_lookup_slot_metrics.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_consumer_descriptors_uint32_from_int.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_consumer_descriptors_descriptor_by_id.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_consumer_descriptors_descriptor_from_lookup.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_consumer_descriptors_status_ready_id.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_consumer_descriptors_uint32_from_int.hpp"
#include "__callable/__latency_fn_structure_row_ids_dense_index.hpp"
#include "__callable/__latency_fn_structure_row_ids_has_dense_id.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_append_row.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_bucket_resident_rows_id.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_policy_compact_hot_rows_id.hpp"
#include "__callable/__latency_fn_performance_memory_estimates_row.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_consumer_descriptors_append_memory_estimate.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_consumer_descriptors_uint32_from_int.hpp"
#include "__callable/__latency_fn_structure_row_ids_next_dense_id.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_consumer_descriptors_append_descriptor_for_view.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_consumer_descriptors_append_from_logical_views_if_needed.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_consumer_descriptors_append_lookup_slot_metrics.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_consumer_descriptors_append_memory_estimate.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_consumer_descriptors_build_descriptor_lookup_ids.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_consumer_descriptors_descriptor_from_lookup.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_consumer_descriptors_has_ready_logical_views.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_consumer_descriptors_max_source_unit_id_from_ready_descriptors.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_consumer_descriptors_status_ready_id.hpp"
#include "__callable/__latency_fn_resident_function_body_logical_frontend_views_status_ready_id.hpp"
namespace scpp { extern const int __latency_lines_resident_function_body_logical_frontend_consumer_descriptors[]; }
namespace scpp {
ResidentFunctionBodyLogicalFrontendConsumerDescriptorRow __latency_fn_resident_function_body_logical_frontend_consumer_descriptors_descriptor_by_id(shared_p<CompilerProjectRunReport> report, int_t<std::uint32_t> descriptorId) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_logical_frontend_consumer_descriptors::descriptor_by_id", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_logical_frontend_consumer_descriptors.phs", __latency_lines_resident_function_body_logical_frontend_consumer_descriptors[30]);
	if (static_cast<bool>(php::condition_truthy(__latency_fn_structure_row_ids_has_dense_id(descriptorId, php::count(report->resident_function_body_logical_frontend_consumer_descriptors))))) {
		ResidentFunctionBodyLogicalFrontendConsumerDescriptorRow row = report->resident_function_body_logical_frontend_consumer_descriptors[__latency_fn_structure_row_ids_dense_index(descriptorId)];
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->descriptor_id), cast<int_t<>>(descriptorId)))) {
			return row;
		}
	}
	auto __latency_local_0 = report->resident_function_body_logical_frontend_consumer_descriptors;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->descriptor_id), cast<int_t<>>(descriptorId)))) {
			return row;
		}
	}
	ResidentFunctionBodyLogicalFrontendConsumerDescriptorRow empty = ResidentFunctionBodyLogicalFrontendConsumerDescriptorRow{};
	return empty;
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_logical_frontend_consumer_descriptors[]; }
namespace scpp {
int_t<> __latency_fn_resident_function_body_logical_frontend_consumer_descriptors_max_source_unit_id_from_ready_descriptors(shared_p<CompilerProjectRunReport> report, int_t<std::uint32_t> ownerRunId) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_logical_frontend_consumer_descriptors::max_source_unit_id_from_ready_descriptors", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_logical_frontend_consumer_descriptors.phs", __latency_lines_resident_function_body_logical_frontend_consumer_descriptors[31]);
	int_t<> maxId = required_cast<int_t<>>(static_cast<int_t<> >(0));
	auto __latency_local_0 = report->resident_function_body_logical_frontend_consumer_descriptors;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>(((php::identical(cast<int_t<>>(row->owner_run_id), cast<int_t<>>(ownerRunId)) && php::identical(cast<int_t<>>(row->status_id), cast<int_t<>>(__latency_fn_resident_function_body_logical_frontend_consumer_descriptors_status_ready_id()))) && (cast<int_t<>>(row->source_unit_id) > maxId)))) {
			maxId = cast<int_t<>>(row->source_unit_id);
		}
	}
	return maxId;
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_logical_frontend_consumer_descriptors[]; }
namespace scpp {
void __latency_fn_resident_function_body_logical_frontend_consumer_descriptors_build_descriptor_lookup_ids(shared_p<CompilerProjectRunReport> report, int_t<std::uint32_t> ownerRunId, int_t<> slotCount, vector_t<int_t<std::uint32_t>>& descriptorIds) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_logical_frontend_consumer_descriptors::build_descriptor_lookup_ids", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_logical_frontend_consumer_descriptors.phs", __latency_lines_resident_function_body_logical_frontend_consumer_descriptors[32]);
	__latency_fn_resident_definition_granularity_reserve_none_id_slots(descriptorIds, slotCount);
	auto __latency_local_0 = report->resident_function_body_logical_frontend_consumer_descriptors;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>(((php::identical(cast<int_t<>>(row->owner_run_id), cast<int_t<>>(ownerRunId)) && php::identical(cast<int_t<>>(row->status_id), cast<int_t<>>(__latency_fn_resident_function_body_logical_frontend_consumer_descriptors_status_ready_id()))) && __latency_fn_structure_row_ids_has_dense_id(row->source_unit_id, slotCount)))) {
			descriptorIds.at(__latency_fn_structure_row_ids_dense_index(row->source_unit_id)) = row->descriptor_id;
		}
	}
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_logical_frontend_consumer_descriptors[]; }
namespace scpp {
void __latency_fn_resident_function_body_logical_frontend_consumer_descriptors_append_lookup_slot_metrics(shared_p<CompilerProjectRunReport>& report, int_t<> slotCount) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_logical_frontend_consumer_descriptors::append_lookup_slot_metrics", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_logical_frontend_consumer_descriptors.phs", __latency_lines_resident_function_body_logical_frontend_consumer_descriptors[33]);
	report->resident_function_body_logical_frontend_consumer_descriptor_lookup_slot_count = __latency_fn_resident_function_body_logical_frontend_consumer_descriptors_uint32_from_int((cast<int_t<>>(report->resident_function_body_logical_frontend_consumer_descriptor_lookup_slot_count) + slotCount));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_logical_frontend_consumer_descriptors[]; }
namespace scpp {
ResidentFunctionBodyLogicalFrontendConsumerDescriptorRow __latency_fn_resident_function_body_logical_frontend_consumer_descriptors_descriptor_from_lookup(shared_p<CompilerProjectRunReport>& metricsReport, shared_p<CompilerProjectRunReport> descriptorReport, vector_t<int_t<std::uint32_t>>& descriptorIds, int_t<std::uint32_t> ownerRunId, int_t<std::uint32_t> sourceUnitId) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_logical_frontend_consumer_descriptors::descriptor_from_lookup", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_logical_frontend_consumer_descriptors.phs", __latency_lines_resident_function_body_logical_frontend_consumer_descriptors[34]);
	metricsReport->resident_function_body_logical_frontend_consumer_descriptor_lookup_probe_count = __latency_fn_resident_function_body_logical_frontend_consumer_descriptors_uint32_from_int((cast<int_t<>>(metricsReport->resident_function_body_logical_frontend_consumer_descriptor_lookup_probe_count) + static_cast<int_t<> >(1)));
	if (static_cast<bool>(php::condition_truthy(__latency_fn_structure_row_ids_has_dense_id(sourceUnitId, php::count(descriptorIds))))) {
		int_t<std::uint32_t> descriptorId = required_cast<int_t<std::uint32_t>>(cast<int_t<std::uint32_t>>(descriptorIds.at(__latency_fn_structure_row_ids_dense_index(sourceUnitId))));
		if (static_cast<bool>((cast<int_t<>>(descriptorId) > static_cast<int_t<> >(0)))) {
			ResidentFunctionBodyLogicalFrontendConsumerDescriptorRow row = __latency_fn_resident_function_body_logical_frontend_consumer_descriptors_descriptor_by_id(descriptorReport, cast<int_t<std::uint32_t>>(descriptorId));
			if (static_cast<bool>(((php::identical(cast<int_t<>>(row->owner_run_id), cast<int_t<>>(ownerRunId)) && php::identical(cast<int_t<>>(row->source_unit_id), cast<int_t<>>(sourceUnitId))) && php::identical(cast<int_t<>>(row->status_id), cast<int_t<>>(__latency_fn_resident_function_body_logical_frontend_consumer_descriptors_status_ready_id()))))) {
				metricsReport->resident_function_body_logical_frontend_consumer_descriptor_lookup_resolved_count = __latency_fn_resident_function_body_logical_frontend_consumer_descriptors_uint32_from_int((cast<int_t<>>(metricsReport->resident_function_body_logical_frontend_consumer_descriptor_lookup_resolved_count) + static_cast<int_t<> >(1)));
				return row;
			}
		}
	}
	metricsReport->resident_function_body_logical_frontend_consumer_descriptor_lookup_fallback_scan_count = __latency_fn_resident_function_body_logical_frontend_consumer_descriptors_uint32_from_int((cast<int_t<>>(metricsReport->resident_function_body_logical_frontend_consumer_descriptor_lookup_fallback_scan_count) + static_cast<int_t<> >(1)));
	auto __latency_local_0 = descriptorReport->resident_function_body_logical_frontend_consumer_descriptors;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>(((php::identical(cast<int_t<>>(row->owner_run_id), cast<int_t<>>(ownerRunId)) && php::identical(cast<int_t<>>(row->source_unit_id), cast<int_t<>>(sourceUnitId))) && php::identical(cast<int_t<>>(row->status_id), cast<int_t<>>(__latency_fn_resident_function_body_logical_frontend_consumer_descriptors_status_ready_id()))))) {
			metricsReport->resident_function_body_logical_frontend_consumer_descriptor_lookup_resolved_count = __latency_fn_resident_function_body_logical_frontend_consumer_descriptors_uint32_from_int((cast<int_t<>>(metricsReport->resident_function_body_logical_frontend_consumer_descriptor_lookup_resolved_count) + static_cast<int_t<> >(1)));
			return row;
		}
	}
	ResidentFunctionBodyLogicalFrontendConsumerDescriptorRow empty = ResidentFunctionBodyLogicalFrontendConsumerDescriptorRow{};
	return empty;
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_logical_frontend_consumer_descriptors[]; }
namespace scpp {
void __latency_fn_resident_function_body_logical_frontend_consumer_descriptors_append_memory_estimate(shared_p<CompilerProjectRunReport>& report, int_t<> descriptorRowCount, int_t<> bodySpanRowCount) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_logical_frontend_consumer_descriptors::append_memory_estimate", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_logical_frontend_consumer_descriptors.phs", __latency_lines_resident_function_body_logical_frontend_consumer_descriptors[35]);
	int_t<> rowCount = required_cast<int_t<>>((descriptorRowCount + bodySpanRowCount));
	if (static_cast<bool>((rowCount <= static_cast<int_t<> >(0)))) {
		return;
	}
	int_t<> bytes = required_cast<int_t<>>(((descriptorRowCount * static_cast<int_t<> >(sizeof(ResidentFunctionBodyLogicalFrontendConsumerDescriptorRow))) + (bodySpanRowCount * static_cast<int_t<> >(sizeof(ResidentFunctionBodyLogicalFrontendBodySpanDescriptorRow)))));
	__latency_fn_performance_memory_estimates_append_row(report, __latency_fn_performance_memory_estimates_row(__latency_fn_structure_row_ids_next_dense_id(php::count(report->performance_memory_estimates)), __latency_fn_performance_memory_estimates_bucket_resident_rows_id(), __latency_fn_resident_function_body_logical_frontend_consumer_descriptors_uint32_from_int(rowCount), __latency_fn_resident_function_body_logical_frontend_consumer_descriptors_uint32_from_int(bytes), __latency_fn_performance_memory_estimates_policy_compact_hot_rows_id()));
}

}

namespace scpp { extern const int __latency_lines_resident_function_body_logical_frontend_consumer_descriptors[]; }
namespace scpp {
void __latency_fn_resident_function_body_logical_frontend_consumer_descriptors_append_from_logical_views_if_needed(shared_p<CompilerProjectRunReport>& report, shared_p<CompilerProjectRunReport> previous, int_t<std::uint32_t> ownerRunId) {
	SCPP_CALL_DEPTH_GUARD("resident_function_body_logical_frontend_consumer_descriptors::append_from_logical_views_if_needed", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_function_body_logical_frontend_consumer_descriptors.phs", __latency_lines_resident_function_body_logical_frontend_consumer_descriptors[36]);
	if (static_cast<bool>((!__latency_fn_resident_function_body_logical_frontend_consumer_descriptors_has_ready_logical_views(report, cast<int_t<std::uint32_t>>(ownerRunId))))) {
		return;
	}
	int_t<> startDescriptorCount = required_cast<int_t<>>(php::count(report->resident_function_body_logical_frontend_consumer_descriptors));
	int_t<> startBodySpanCount = required_cast<int_t<>>(php::count(report->resident_function_body_logical_frontend_body_span_descriptors));
	auto __latency_local_0 = report->resident_function_body_logical_frontend_views;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto view = __latency_local_1.value_copy();
		if (static_cast<bool>((php::identical(cast<int_t<>>(view->owner_run_id), cast<int_t<>>(ownerRunId)) && php::identical(cast<int_t<>>(view->status_id), cast<int_t<>>(__latency_fn_resident_function_body_logical_frontend_views_status_ready_id()))))) {
			__latency_fn_resident_function_body_logical_frontend_consumer_descriptors_append_descriptor_for_view(report, previous, view);
		}
	}
	__latency_fn_resident_function_body_logical_frontend_consumer_descriptors_append_memory_estimate(report, (php::count(report->resident_function_body_logical_frontend_consumer_descriptors) - startDescriptorCount), (php::count(report->resident_function_body_logical_frontend_body_span_descriptors) - startBodySpanCount));
	int_t<> slotCount = required_cast<int_t<>>(__latency_fn_resident_function_body_logical_frontend_consumer_descriptors_max_source_unit_id_from_ready_descriptors(report, cast<int_t<std::uint32_t>>(ownerRunId)));
	if (static_cast<bool>((slotCount <= static_cast<int_t<> >(0)))) {
		return;
	}
	vector_t<int_t<std::uint32_t>> descriptorIds = {};
	__latency_fn_resident_function_body_logical_frontend_consumer_descriptors_build_descriptor_lookup_ids(report, cast<int_t<std::uint32_t>>(ownerRunId), slotCount, descriptorIds);
	__latency_fn_resident_function_body_logical_frontend_consumer_descriptors_append_lookup_slot_metrics(report, slotCount);
	auto __latency_local_2 = report->resident_function_body_logical_frontend_consumer_descriptors;
	for (auto __latency_local_3 : foreach_range(__latency_local_2)) {
		auto descriptor = __latency_local_3.value_copy();
		if (static_cast<bool>((php::identical(cast<int_t<>>(descriptor->owner_run_id), cast<int_t<>>(ownerRunId)) && php::identical(cast<int_t<>>(descriptor->status_id), cast<int_t<>>(__latency_fn_resident_function_body_logical_frontend_consumer_descriptors_status_ready_id()))))) {
			__latency_fn_resident_function_body_logical_frontend_consumer_descriptors_descriptor_from_lookup(report, report, descriptorIds, cast<int_t<std::uint32_t>>(ownerRunId), descriptor->source_unit_id);
		}
	}
}

}
