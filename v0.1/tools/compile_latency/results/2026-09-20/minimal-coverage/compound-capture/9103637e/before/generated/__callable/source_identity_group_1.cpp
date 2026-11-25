#include <scpp/lang/php.hpp>
#include "__types/SourceUnitTable.hpp"
#include "__types/SourceUnitTableRow.hpp"
#include "__callable/__latency_fn_source_identity_source_unit_key.hpp"
#include "__callable/__latency_fn_source_identity_source_unit_key_from_table.hpp"
#include "__callable/__latency_fn_source_identity_source_unit_relative_path.hpp"
#include "__callable/__latency_fn_source_identity_source_unit_relative_path_from_table.hpp"
#include "__callable/__latency_fn_source_identity_source_unit_path.hpp"
#include "__callable/__latency_fn_source_identity_source_unit_path_from_table.hpp"
#include "__callable/__latency_fn_source_identity_source_unit_equals.hpp"
#include "__callable/__latency_fn_source_identity_source_unit_debug_string.hpp"
#include "__callable/__latency_fn_source_identity_source_unit_debug_string.hpp"
#include "__callable/__latency_fn_source_identity_source_unit_debug_string_from_table.hpp"
#include "__callable/__latency_fn_source_identity_source_unit_key_from_table.hpp"
#include "__callable/__latency_fn_source_identity_source_unit_relative_path_from_table.hpp"
#include "__callable/__latency_fn_source_identity_source_unit_key.hpp"
#include "__callable/__latency_fn_source_identity_source_unit_path.hpp"
#include "__callable/__latency_fn_source_identity_source_unit_relative_path.hpp"
#include "__callable/__latency_fn_source_identity_source_unit_stable_hash.hpp"
#include "__callable/__latency_fn_source_identity_source_unit_key_from_table.hpp"
#include "__callable/__latency_fn_source_identity_source_unit_path_from_table.hpp"
#include "__callable/__latency_fn_source_identity_source_unit_relative_path_from_table.hpp"
#include "__callable/__latency_fn_source_identity_source_unit_stable_hash_from_table.hpp"
namespace scpp { extern const int __latency_lines_source_identity[]; }
namespace scpp {
string_t __latency_fn_source_identity_source_unit_key_from_table(shared_p<SourceUnitTable> table, SourceUnitTableRow row) {
	SCPP_CALL_DEPTH_GUARD("source_identity::source_unit_key_from_table", "/tmp/scpp-edit-latency-20260919/app/compile/model/source_identity.phs", __latency_lines_source_identity[12]);
	int_t<> id = required_cast<int_t<>>(cast<int_t<>>(row->source_unit_key_id));
	if (static_cast<bool>(((id > static_cast<int_t<> >(0)) && (id <= php::count(table->source_unit_keys))))) {
		return table->source_unit_keys[(id - static_cast<int_t<> >(1))];
	}
	return __latency_fn_source_identity_source_unit_key(row);
}

}

namespace scpp { extern const int __latency_lines_source_identity[]; }
namespace scpp {
string_t __latency_fn_source_identity_source_unit_relative_path_from_table(shared_p<SourceUnitTable> table, SourceUnitTableRow row) {
	SCPP_CALL_DEPTH_GUARD("source_identity::source_unit_relative_path_from_table", "/tmp/scpp-edit-latency-20260919/app/compile/model/source_identity.phs", __latency_lines_source_identity[13]);
	int_t<> id = required_cast<int_t<>>(cast<int_t<>>(row->relative_path_id));
	if (static_cast<bool>(((id > static_cast<int_t<> >(0)) && (id <= php::count(table->relative_paths))))) {
		return table->relative_paths[(id - static_cast<int_t<> >(1))];
	}
	return __latency_fn_source_identity_source_unit_relative_path(row);
}

}

namespace scpp { extern const int __latency_lines_source_identity[]; }
namespace scpp {
string_t __latency_fn_source_identity_source_unit_path_from_table(shared_p<SourceUnitTable> table, SourceUnitTableRow row) {
	SCPP_CALL_DEPTH_GUARD("source_identity::source_unit_path_from_table", "/tmp/scpp-edit-latency-20260919/app/compile/model/source_identity.phs", __latency_lines_source_identity[14]);
	int_t<> id = required_cast<int_t<>>(cast<int_t<>>(row->path_id));
	if (static_cast<bool>(((id > static_cast<int_t<> >(0)) && (id <= php::count(table->paths))))) {
		return table->paths[(id - static_cast<int_t<> >(1))];
	}
	return __latency_fn_source_identity_source_unit_path(row);
}

}

namespace scpp { extern const int __latency_lines_source_identity[]; }
namespace scpp {
bool_t __latency_fn_source_identity_source_unit_equals(SourceUnitTableRow left, SourceUnitTableRow right) {
	SCPP_CALL_DEPTH_GUARD("source_identity::source_unit_equals", "/tmp/scpp-edit-latency-20260919/app/compile/model/source_identity.phs", __latency_lines_source_identity[15]);
	if (static_cast<bool>((((cast<int_t<>>(left->source_unit_id) > static_cast<int_t<> >(0)) && (cast<int_t<>>(right->source_unit_id) > static_cast<int_t<> >(0))) && php::not_identical(cast<int_t<>>(left->source_unit_id), cast<int_t<>>(right->source_unit_id))))) {
		return bool_t(static_cast<bool_t>(false));
	}
	if (static_cast<bool>((php::identical(cast<int_t<>>(left->source_unit_key_id), static_cast<int_t<> >(0)) || php::identical(cast<int_t<>>(right->source_unit_key_id), static_cast<int_t<> >(0))))) {
		return bool_t(static_cast<bool_t>(false));
	}
	return (((((((((php::identical(left->source_unit_key_id, right->source_unit_key_id) && php::identical(left->relative_path_id, right->relative_path_id)) && php::identical(left->path_id, right->path_id)) && php::identical(left->language_id, right->language_id)) && php::identical(left->status_id, right->status_id)) && php::identical(left->dirty_signal_id, right->dirty_signal_id)) && php::identical(left->reuse_signal_id, right->reuse_signal_id)) && php::identical(left->partition_id, right->partition_id)) && php::identical(left->source_length, right->source_length)) && php::identical(left->line_count, right->line_count));
}

}

namespace scpp { extern const int __latency_lines_source_identity[]; }
namespace scpp {
string_t __latency_fn_source_identity_source_unit_debug_string(SourceUnitTableRow row) {
	SCPP_CALL_DEPTH_GUARD("source_identity::source_unit_debug_string", "/tmp/scpp-edit-latency-20260919/app/compile/model/source_identity.phs", __latency_lines_source_identity[16]);
	if (static_cast<bool>((cast<int_t<>>(row->source_unit_id) > static_cast<int_t<> >(0)))) {
		return (string_t("source_unit_id:") + cast<string_t>(row->source_unit_id));
	}
	return string_t("");
}

}

namespace scpp { extern const int __latency_lines_source_identity[]; }
namespace scpp {
string_t __latency_fn_source_identity_source_unit_debug_string_from_table(shared_p<SourceUnitTable> table, SourceUnitTableRow row) {
	SCPP_CALL_DEPTH_GUARD("source_identity::source_unit_debug_string_from_table", "/tmp/scpp-edit-latency-20260919/app/compile/model/source_identity.phs", __latency_lines_source_identity[17]);
	string_t sourceUnitKey = required_cast<string_t>(__latency_fn_source_identity_source_unit_key_from_table(table, row));
	if (static_cast<bool>(php::condition_truthy(php::not_identical(sourceUnitKey, string_t(""))))) {
		return sourceUnitKey;
	}
	string_t relativePath = required_cast<string_t>(__latency_fn_source_identity_source_unit_relative_path_from_table(table, row));
	if (static_cast<bool>(php::condition_truthy(php::not_identical(relativePath, string_t(""))))) {
		return (string_t("source:") + cast<string_t>(relativePath));
	}
	return __latency_fn_source_identity_source_unit_debug_string(row);
}

}

namespace scpp { extern const int __latency_lines_source_identity[]; }
namespace scpp {
int_t<std::uint64_t> __latency_fn_source_identity_source_unit_stable_hash(SourceUnitTableRow row) {
	SCPP_CALL_DEPTH_GUARD("source_identity::source_unit_stable_hash", "/tmp/scpp-edit-latency-20260919/app/compile/model/source_identity.phs", __latency_lines_source_identity[18]);
	string_t identity = required_cast<string_t>((string_t("source_unit_table_identity:v1:") + cast<string_t>(__latency_fn_source_identity_source_unit_key(row)) + string_t(":") + cast<string_t>(__latency_fn_source_identity_source_unit_relative_path(row)) + string_t(":") + cast<string_t>(__latency_fn_source_identity_source_unit_path(row)) + string_t(":") + cast<string_t>(row->language_id) + string_t(":") + cast<string_t>(row->status_id) + string_t(":") + cast<string_t>(row->dirty_signal_id) + string_t(":") + cast<string_t>(row->reuse_signal_id) + string_t(":") + cast<string_t>(row->partition_id) + string_t(":") + cast<string_t>(row->source_length) + string_t(":") + cast<string_t>(row->line_count)));
	return php::stable_hash_string_u64(identity);
}

}

namespace scpp { extern const int __latency_lines_source_identity[]; }
namespace scpp {
int_t<std::uint64_t> __latency_fn_source_identity_source_unit_stable_hash_from_table(shared_p<SourceUnitTable> table, SourceUnitTableRow row) {
	SCPP_CALL_DEPTH_GUARD("source_identity::source_unit_stable_hash_from_table", "/tmp/scpp-edit-latency-20260919/app/compile/model/source_identity.phs", __latency_lines_source_identity[19]);
	string_t identity = required_cast<string_t>((string_t("source_unit_table_identity:v1:") + cast<string_t>(__latency_fn_source_identity_source_unit_key_from_table(table, row)) + string_t(":") + cast<string_t>(__latency_fn_source_identity_source_unit_relative_path_from_table(table, row)) + string_t(":") + cast<string_t>(__latency_fn_source_identity_source_unit_path_from_table(table, row)) + string_t(":") + cast<string_t>(row->language_id) + string_t(":") + cast<string_t>(row->status_id) + string_t(":") + cast<string_t>(row->dirty_signal_id) + string_t(":") + cast<string_t>(row->reuse_signal_id) + string_t(":") + cast<string_t>(row->partition_id) + string_t(":") + cast<string_t>(row->source_length) + string_t(":") + cast<string_t>(row->line_count)));
	return php::stable_hash_string_u64(identity);
}

}
