#include <scpp/lang/php.hpp>
#include "__types/BackendBinaryOperandRow.hpp"
#include "__types/BackendCallArgumentRow.hpp"
#include "__types/BackendControlFlowOperandRow.hpp"
#include "__types/BackendLocalOperandRow.hpp"
#include "__types/BackendRequestAuthorizationArtifact.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_binary_operand_by_owner_row_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_dense_index.hpp"
#include "__callable/__latency_fn_structure_row_ids_has_dense_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_local_operand_by_owner_row_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_dense_index.hpp"
#include "__callable/__latency_fn_structure_row_ids_has_dense_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_call_argument_by_owner_row_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_dense_index.hpp"
#include "__callable/__latency_fn_structure_row_ids_has_dense_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_call_arguments_by_owner_row_id.hpp"
#include "__callable/__latency_fn_backend_preflight_requests_control_flow_operand_by_owner_row_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_dense_index.hpp"
#include "__callable/__latency_fn_structure_row_ids_has_dense_id.hpp"
namespace scpp { extern const int __latency_lines_backend_preflight_requests[]; }
namespace scpp {
BackendBinaryOperandRow __latency_fn_backend_preflight_requests_binary_operand_by_owner_row_id(shared_p<BackendRequestAuthorizationArtifact> artifact, int_t<std::uint32_t> ownerRowId) {
	SCPP_CALL_DEPTH_GUARD("backend_preflight_requests::binary_operand_by_owner_row_id", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_preflight_requests.phs", __latency_lines_backend_preflight_requests[155]);
	if (static_cast<bool>(php::condition_truthy(__latency_fn_structure_row_ids_has_dense_id(ownerRowId, cast<int_t<>>(artifact->binary_operand_count))))) {
		BackendBinaryOperandRow row = artifact->binary_operands[__latency_fn_structure_row_ids_dense_index(ownerRowId)];
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->owner_row_id), cast<int_t<>>(ownerRowId)))) {
			return row;
		}
	}
	int_t<> low = required_cast<int_t<>>(static_cast<int_t<> >(0));
	int_t<> high = required_cast<int_t<>>((cast<int_t<>>(artifact->binary_operand_count) - static_cast<int_t<> >(1)));
	while (static_cast<bool>((low <= high))) {
		int_t<> middle = required_cast<int_t<>>(cast<int_t<>>(((low + high) / static_cast<int_t<> >(2))));
		BackendBinaryOperandRow row = artifact->binary_operands[middle];
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->owner_row_id), cast<int_t<>>(ownerRowId)))) {
			return row;
		}
		if (static_cast<bool>((cast<int_t<>>(row->owner_row_id) < cast<int_t<>>(ownerRowId)))) {
			low = (middle + static_cast<int_t<> >(1));
		}
		else {
			high = (middle - static_cast<int_t<> >(1));
		}
	}
	auto __latency_local_0 = artifact->binary_operands;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->owner_row_id), cast<int_t<>>(ownerRowId)))) {
			return row;
		}
	}
	BackendBinaryOperandRow empty = BackendBinaryOperandRow{};
	return empty;
}

}

namespace scpp { extern const int __latency_lines_backend_preflight_requests[]; }
namespace scpp {
BackendLocalOperandRow __latency_fn_backend_preflight_requests_local_operand_by_owner_row_id(shared_p<BackendRequestAuthorizationArtifact> artifact, int_t<std::uint32_t> ownerRowId) {
	SCPP_CALL_DEPTH_GUARD("backend_preflight_requests::local_operand_by_owner_row_id", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_preflight_requests.phs", __latency_lines_backend_preflight_requests[156]);
	if (static_cast<bool>(php::condition_truthy(__latency_fn_structure_row_ids_has_dense_id(ownerRowId, cast<int_t<>>(artifact->local_operand_count))))) {
		BackendLocalOperandRow row = artifact->local_operands[__latency_fn_structure_row_ids_dense_index(ownerRowId)];
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->owner_row_id), cast<int_t<>>(ownerRowId)))) {
			return row;
		}
	}
	int_t<> low = required_cast<int_t<>>(static_cast<int_t<> >(0));
	int_t<> high = required_cast<int_t<>>((cast<int_t<>>(artifact->local_operand_count) - static_cast<int_t<> >(1)));
	while (static_cast<bool>((low <= high))) {
		int_t<> middle = required_cast<int_t<>>(cast<int_t<>>(((low + high) / static_cast<int_t<> >(2))));
		BackendLocalOperandRow row = artifact->local_operands[middle];
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->owner_row_id), cast<int_t<>>(ownerRowId)))) {
			return row;
		}
		if (static_cast<bool>((cast<int_t<>>(row->owner_row_id) < cast<int_t<>>(ownerRowId)))) {
			low = (middle + static_cast<int_t<> >(1));
		}
		else {
			high = (middle - static_cast<int_t<> >(1));
		}
	}
	auto __latency_local_0 = artifact->local_operands;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->owner_row_id), cast<int_t<>>(ownerRowId)))) {
			return row;
		}
	}
	BackendLocalOperandRow empty = BackendLocalOperandRow{};
	return empty;
}

}

namespace scpp { extern const int __latency_lines_backend_preflight_requests[]; }
namespace scpp {
BackendCallArgumentRow __latency_fn_backend_preflight_requests_call_argument_by_owner_row_id(shared_p<BackendRequestAuthorizationArtifact> artifact, int_t<std::uint32_t> ownerRowId) {
	SCPP_CALL_DEPTH_GUARD("backend_preflight_requests::call_argument_by_owner_row_id", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_preflight_requests.phs", __latency_lines_backend_preflight_requests[157]);
	if (static_cast<bool>(php::condition_truthy(__latency_fn_structure_row_ids_has_dense_id(ownerRowId, cast<int_t<>>(artifact->call_argument_count))))) {
		BackendCallArgumentRow row = artifact->call_arguments[__latency_fn_structure_row_ids_dense_index(ownerRowId)];
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->owner_row_id), cast<int_t<>>(ownerRowId)))) {
			return row;
		}
	}
	int_t<> low = required_cast<int_t<>>(static_cast<int_t<> >(0));
	int_t<> high = required_cast<int_t<>>((cast<int_t<>>(artifact->call_argument_count) - static_cast<int_t<> >(1)));
	while (static_cast<bool>((low <= high))) {
		int_t<> middle = required_cast<int_t<>>(cast<int_t<>>(((low + high) / static_cast<int_t<> >(2))));
		BackendCallArgumentRow row = artifact->call_arguments[middle];
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->owner_row_id), cast<int_t<>>(ownerRowId)))) {
			return row;
		}
		if (static_cast<bool>((cast<int_t<>>(row->owner_row_id) < cast<int_t<>>(ownerRowId)))) {
			low = (middle + static_cast<int_t<> >(1));
		}
		else {
			high = (middle - static_cast<int_t<> >(1));
		}
	}
	auto __latency_local_0 = artifact->call_arguments;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->owner_row_id), cast<int_t<>>(ownerRowId)))) {
			return row;
		}
	}
	BackendCallArgumentRow empty = BackendCallArgumentRow{};
	return empty;
}

}

namespace scpp { extern const int __latency_lines_backend_preflight_requests[]; }
namespace scpp {
vector_t<BackendCallArgumentRow> __latency_fn_backend_preflight_requests_call_arguments_by_owner_row_id(shared_p<BackendRequestAuthorizationArtifact> artifact, int_t<std::uint32_t> ownerRowId) {
	SCPP_CALL_DEPTH_GUARD("backend_preflight_requests::call_arguments_by_owner_row_id", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_preflight_requests.phs", __latency_lines_backend_preflight_requests[158]);
	vector_t<BackendCallArgumentRow> rows = {};
	auto __latency_local_0 = artifact->call_arguments;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->owner_row_id), cast<int_t<>>(ownerRowId)))) {
			(void) rows.push_back(row);
		}
	}
	return rows;
}

}

namespace scpp { extern const int __latency_lines_backend_preflight_requests[]; }
namespace scpp {
BackendControlFlowOperandRow __latency_fn_backend_preflight_requests_control_flow_operand_by_owner_row_id(shared_p<BackendRequestAuthorizationArtifact> artifact, int_t<std::uint32_t> ownerRowId) {
	SCPP_CALL_DEPTH_GUARD("backend_preflight_requests::control_flow_operand_by_owner_row_id", "/tmp/scpp-edit-latency-20260919/app/compile/backend/backend_preflight_requests.phs", __latency_lines_backend_preflight_requests[159]);
	if (static_cast<bool>(php::condition_truthy(__latency_fn_structure_row_ids_has_dense_id(ownerRowId, cast<int_t<>>(artifact->control_flow_operand_count))))) {
		BackendControlFlowOperandRow row = artifact->control_flow_operands[__latency_fn_structure_row_ids_dense_index(ownerRowId)];
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->owner_row_id), cast<int_t<>>(ownerRowId)))) {
			return row;
		}
	}
	int_t<> low = required_cast<int_t<>>(static_cast<int_t<> >(0));
	int_t<> high = required_cast<int_t<>>((cast<int_t<>>(artifact->control_flow_operand_count) - static_cast<int_t<> >(1)));
	while (static_cast<bool>((low <= high))) {
		int_t<> middle = required_cast<int_t<>>(cast<int_t<>>(((low + high) / static_cast<int_t<> >(2))));
		BackendControlFlowOperandRow row = artifact->control_flow_operands[middle];
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->owner_row_id), cast<int_t<>>(ownerRowId)))) {
			return row;
		}
		if (static_cast<bool>((cast<int_t<>>(row->owner_row_id) < cast<int_t<>>(ownerRowId)))) {
			low = (middle + static_cast<int_t<> >(1));
		}
		else {
			high = (middle - static_cast<int_t<> >(1));
		}
	}
	auto __latency_local_0 = artifact->control_flow_operands;
	for (auto __latency_local_1 : foreach_range(__latency_local_0)) {
		auto row = __latency_local_1.value_copy();
		if (static_cast<bool>(php::identical(cast<int_t<>>(row->owner_row_id), cast<int_t<>>(ownerRowId)))) {
			return row;
		}
	}
	BackendControlFlowOperandRow empty = BackendControlFlowOperandRow{};
	return empty;
}

}
