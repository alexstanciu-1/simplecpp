#include <scpp/lang/php.hpp>
#include "__types/SourceReadDescriptorRow.hpp"
#include "__types/SourceReadResultRow.hpp"
#include "__types/SourceReadTable.hpp"
#include "__types/SourceUnitTable.hpp"
#include "__types/SourceUnitTableRow.hpp"
#include "__types/source_units.hpp"
#include "__callable/__latency_fn_source_units_scope_project_manifest_sources_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_source_units_dirty_signal_source_text_or_manifest_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_source_units_reuse_signal_source_unit_rows_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_source_units_partition_source_unit_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_source_units_source_read_status_ready_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_source_units_source_read_status_failed_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_source_units_source_read_error_none_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_source_units_source_read_error_io_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_source_units_scope_name.hpp"
#include "__callable/__latency_fn_source_units_scope_project_manifest_sources_id.hpp"
#include "__callable/__latency_fn_source_units_dirty_signal_name.hpp"
#include "__callable/__latency_fn_source_units_dirty_signal_source_text_or_manifest_id.hpp"
#include "__callable/__latency_fn_source_units_reuse_signal_name.hpp"
#include "__callable/__latency_fn_source_units_reuse_signal_source_unit_rows_id.hpp"
#include "__callable/__latency_fn_source_units_partition_name.hpp"
#include "__callable/__latency_fn_source_units_partition_source_unit_id.hpp"
#include "__callable/__latency_fn_source_units_reserve_table.hpp"
#include "__callable/__latency_fn_source_units_reserve_source_read_table.hpp"
namespace scpp { extern const int __latency_lines_source_units[]; }
namespace scpp {
bool_t source_units::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == source_units::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

}

namespace scpp { extern const int __latency_lines_source_units[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_source_units_scope_project_manifest_sources_id() {
	SCPP_CALL_DEPTH_GUARD("source_units::scope_project_manifest_sources_id", "/tmp/scpp-edit-latency-20260919/app/compile/model/source_units.phs", __latency_lines_source_units[0]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_source_units[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_source_units_dirty_signal_source_text_or_manifest_id() {
	SCPP_CALL_DEPTH_GUARD("source_units::dirty_signal_source_text_or_manifest_id", "/tmp/scpp-edit-latency-20260919/app/compile/model/source_units.phs", __latency_lines_source_units[1]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_source_units[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_source_units_reuse_signal_source_unit_rows_id() {
	SCPP_CALL_DEPTH_GUARD("source_units::reuse_signal_source_unit_rows_id", "/tmp/scpp-edit-latency-20260919/app/compile/model/source_units.phs", __latency_lines_source_units[2]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_source_units[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_source_units_partition_source_unit_id() {
	SCPP_CALL_DEPTH_GUARD("source_units::partition_source_unit_id", "/tmp/scpp-edit-latency-20260919/app/compile/model/source_units.phs", __latency_lines_source_units[3]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_source_units[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_source_units_source_read_status_ready_id() {
	SCPP_CALL_DEPTH_GUARD("source_units::source_read_status_ready_id", "/tmp/scpp-edit-latency-20260919/app/compile/model/source_units.phs", __latency_lines_source_units[4]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_source_units[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_source_units_source_read_status_failed_id() {
	SCPP_CALL_DEPTH_GUARD("source_units::source_read_status_failed_id", "/tmp/scpp-edit-latency-20260919/app/compile/model/source_units.phs", __latency_lines_source_units[5]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(2));
}

}

namespace scpp { extern const int __latency_lines_source_units[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_source_units_source_read_error_none_id() {
	SCPP_CALL_DEPTH_GUARD("source_units::source_read_error_none_id", "/tmp/scpp-edit-latency-20260919/app/compile/model/source_units.phs", __latency_lines_source_units[6]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(0));
}

}

namespace scpp { extern const int __latency_lines_source_units[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_source_units_source_read_error_io_id() {
	SCPP_CALL_DEPTH_GUARD("source_units::source_read_error_io_id", "/tmp/scpp-edit-latency-20260919/app/compile/model/source_units.phs", __latency_lines_source_units[7]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_source_units[]; }
namespace scpp {
string_t __latency_fn_source_units_scope_name(int_t<std::uint16_t> scopeId) {
	SCPP_CALL_DEPTH_GUARD("source_units::scope_name", "/tmp/scpp-edit-latency-20260919/app/compile/model/source_units.phs", __latency_lines_source_units[8]);
	if (static_cast<bool>(php::identical(scopeId, __latency_fn_source_units_scope_project_manifest_sources_id()))) {
		return string_t("project_manifest_sources");
	}
	return string_t("unknown");
}

}

namespace scpp { extern const int __latency_lines_source_units[]; }
namespace scpp {
string_t __latency_fn_source_units_dirty_signal_name(int_t<std::uint16_t> id) {
	SCPP_CALL_DEPTH_GUARD("source_units::dirty_signal_name", "/tmp/scpp-edit-latency-20260919/app/compile/model/source_units.phs", __latency_lines_source_units[9]);
	if (static_cast<bool>(php::identical(id, __latency_fn_source_units_dirty_signal_source_text_or_manifest_id()))) {
		return string_t("dirty:source_text_or_manifest");
	}
	return string_t("unknown");
}

}

namespace scpp { extern const int __latency_lines_source_units[]; }
namespace scpp {
string_t __latency_fn_source_units_reuse_signal_name(int_t<std::uint16_t> id) {
	SCPP_CALL_DEPTH_GUARD("source_units::reuse_signal_name", "/tmp/scpp-edit-latency-20260919/app/compile/model/source_units.phs", __latency_lines_source_units[10]);
	if (static_cast<bool>(php::identical(id, __latency_fn_source_units_reuse_signal_source_unit_rows_id()))) {
		return string_t("reuse:source_unit_rows");
	}
	return string_t("unknown");
}

}

namespace scpp { extern const int __latency_lines_source_units[]; }
namespace scpp {
string_t __latency_fn_source_units_partition_name(int_t<std::uint16_t> id) {
	SCPP_CALL_DEPTH_GUARD("source_units::partition_name", "/tmp/scpp-edit-latency-20260919/app/compile/model/source_units.phs", __latency_lines_source_units[11]);
	if (static_cast<bool>(php::identical(id, __latency_fn_source_units_partition_source_unit_id()))) {
		return string_t("partition:source_unit");
	}
	return string_t("unknown");
}

}

namespace scpp { extern const int __latency_lines_source_units[]; }
namespace scpp {
void __latency_fn_source_units_reserve_table(shared_p<SourceUnitTable> table, int_t<> sourceCapacity) {
	SCPP_CALL_DEPTH_GUARD("source_units::reserve_table", "/tmp/scpp-edit-latency-20260919/app/compile/model/source_units.phs", __latency_lines_source_units[12]);
	php::vector_reserve(table->rows, sourceCapacity);
	php::vector_reserve(table->source_unit_keys, sourceCapacity);
	php::vector_reserve(table->relative_paths, sourceCapacity);
	php::vector_reserve(table->paths, sourceCapacity);
	php::vector_reserve(table->source_texts, sourceCapacity);
}

}

namespace scpp { extern const int __latency_lines_source_units[]; }
namespace scpp {
void __latency_fn_source_units_reserve_source_read_table(shared_p<SourceReadTable> table, int_t<> sourceCapacity) {
	SCPP_CALL_DEPTH_GUARD("source_units::reserve_source_read_table", "/tmp/scpp-edit-latency-20260919/app/compile/model/source_units.phs", __latency_lines_source_units[13]);
	php::vector_reserve(table->descriptors, sourceCapacity);
	php::vector_reserve(table->results, sourceCapacity);
	php::vector_reserve(table->source_unit_keys, sourceCapacity);
	php::vector_reserve(table->relative_paths, sourceCapacity);
	php::vector_reserve(table->paths, sourceCapacity);
	php::vector_reserve(table->source_texts, sourceCapacity);
}

}
