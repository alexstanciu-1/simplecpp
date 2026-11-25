#include <scpp/lang/php.hpp>
#include "__types/FrontendBodySummaryArtifact.hpp"
#include "__types/FrontendBodySummaryRow.hpp"
#include "__types/FrontendLocalBindingSummaryRow.hpp"
#include "__types/frontend_body_summaries.hpp"
#include "__callable/__latency_fn_frontend_body_summaries_artifact_kind_frontend_body_summary_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_frontend_body_summaries_binding_kind_explicit_local_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_frontend_body_summaries_binding_kind_implicit_assignment_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_frontend_body_summaries_summary_kind_local_binding_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_frontend_body_summaries_summary_kind_assignment_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_frontend_body_summaries_summary_kind_echo_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_frontend_body_summaries_summary_kind_condition_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_frontend_body_summaries_summary_kind_return_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_frontend_body_summaries_summary_kind_expression_dependency_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_frontend_body_summaries_status_ready_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_frontend_body_summaries_status_blocked_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_frontend_body_summaries_blocked_reason_none_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_kind_id.hpp"
#include "__callable/__latency_fn_frontend_body_summaries_blocked_reason_unsupported_statement_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_frontend_body_summaries_blocked_reason_target_not_local_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_frontend_body_summaries_blocked_reason_expression_not_summarized_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_frontend_body_summaries_artifact_kind_frontend_body_summary_id.hpp"
#include "__callable/__latency_fn_frontend_body_summaries_new_artifact.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
namespace scpp { extern const int __latency_lines_frontend_body_summaries[]; }
namespace scpp {
bool_t frontend_body_summaries::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == frontend_body_summaries::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

}

namespace scpp { extern const int __latency_lines_frontend_body_summaries[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_frontend_body_summaries_artifact_kind_frontend_body_summary_id() {
	SCPP_CALL_DEPTH_GUARD("frontend_body_summaries::artifact_kind_frontend_body_summary_id", "/tmp/scpp-edit-latency-20260919/app/compile/model/frontend_body_summaries.phs", __latency_lines_frontend_body_summaries[0]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_frontend_body_summaries[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_frontend_body_summaries_binding_kind_explicit_local_id() {
	SCPP_CALL_DEPTH_GUARD("frontend_body_summaries::binding_kind_explicit_local_id", "/tmp/scpp-edit-latency-20260919/app/compile/model/frontend_body_summaries.phs", __latency_lines_frontend_body_summaries[1]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_frontend_body_summaries[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_frontend_body_summaries_binding_kind_implicit_assignment_id() {
	SCPP_CALL_DEPTH_GUARD("frontend_body_summaries::binding_kind_implicit_assignment_id", "/tmp/scpp-edit-latency-20260919/app/compile/model/frontend_body_summaries.phs", __latency_lines_frontend_body_summaries[2]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(2));
}

}

namespace scpp { extern const int __latency_lines_frontend_body_summaries[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_frontend_body_summaries_summary_kind_local_binding_id() {
	SCPP_CALL_DEPTH_GUARD("frontend_body_summaries::summary_kind_local_binding_id", "/tmp/scpp-edit-latency-20260919/app/compile/model/frontend_body_summaries.phs", __latency_lines_frontend_body_summaries[3]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_frontend_body_summaries[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_frontend_body_summaries_summary_kind_assignment_id() {
	SCPP_CALL_DEPTH_GUARD("frontend_body_summaries::summary_kind_assignment_id", "/tmp/scpp-edit-latency-20260919/app/compile/model/frontend_body_summaries.phs", __latency_lines_frontend_body_summaries[4]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(2));
}

}

namespace scpp { extern const int __latency_lines_frontend_body_summaries[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_frontend_body_summaries_summary_kind_echo_id() {
	SCPP_CALL_DEPTH_GUARD("frontend_body_summaries::summary_kind_echo_id", "/tmp/scpp-edit-latency-20260919/app/compile/model/frontend_body_summaries.phs", __latency_lines_frontend_body_summaries[5]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(3));
}

}

namespace scpp { extern const int __latency_lines_frontend_body_summaries[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_frontend_body_summaries_summary_kind_condition_id() {
	SCPP_CALL_DEPTH_GUARD("frontend_body_summaries::summary_kind_condition_id", "/tmp/scpp-edit-latency-20260919/app/compile/model/frontend_body_summaries.phs", __latency_lines_frontend_body_summaries[6]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(4));
}

}

namespace scpp { extern const int __latency_lines_frontend_body_summaries[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_frontend_body_summaries_summary_kind_return_id() {
	SCPP_CALL_DEPTH_GUARD("frontend_body_summaries::summary_kind_return_id", "/tmp/scpp-edit-latency-20260919/app/compile/model/frontend_body_summaries.phs", __latency_lines_frontend_body_summaries[7]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(5));
}

}

namespace scpp { extern const int __latency_lines_frontend_body_summaries[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_frontend_body_summaries_summary_kind_expression_dependency_id() {
	SCPP_CALL_DEPTH_GUARD("frontend_body_summaries::summary_kind_expression_dependency_id", "/tmp/scpp-edit-latency-20260919/app/compile/model/frontend_body_summaries.phs", __latency_lines_frontend_body_summaries[8]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(6));
}

}

namespace scpp { extern const int __latency_lines_frontend_body_summaries[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_frontend_body_summaries_status_ready_id() {
	SCPP_CALL_DEPTH_GUARD("frontend_body_summaries::status_ready_id", "/tmp/scpp-edit-latency-20260919/app/compile/model/frontend_body_summaries.phs", __latency_lines_frontend_body_summaries[9]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_frontend_body_summaries[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_frontend_body_summaries_status_blocked_id() {
	SCPP_CALL_DEPTH_GUARD("frontend_body_summaries::status_blocked_id", "/tmp/scpp-edit-latency-20260919/app/compile/model/frontend_body_summaries.phs", __latency_lines_frontend_body_summaries[10]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(2));
}

}

namespace scpp { extern const int __latency_lines_frontend_body_summaries[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_frontend_body_summaries_blocked_reason_none_id() {
	SCPP_CALL_DEPTH_GUARD("frontend_body_summaries::blocked_reason_none_id", "/tmp/scpp-edit-latency-20260919/app/compile/model/frontend_body_summaries.phs", __latency_lines_frontend_body_summaries[11]);
	return __latency_fn_structure_row_ids_none_kind_id();
}

}

namespace scpp { extern const int __latency_lines_frontend_body_summaries[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_frontend_body_summaries_blocked_reason_unsupported_statement_id() {
	SCPP_CALL_DEPTH_GUARD("frontend_body_summaries::blocked_reason_unsupported_statement_id", "/tmp/scpp-edit-latency-20260919/app/compile/model/frontend_body_summaries.phs", __latency_lines_frontend_body_summaries[12]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_frontend_body_summaries[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_frontend_body_summaries_blocked_reason_target_not_local_id() {
	SCPP_CALL_DEPTH_GUARD("frontend_body_summaries::blocked_reason_target_not_local_id", "/tmp/scpp-edit-latency-20260919/app/compile/model/frontend_body_summaries.phs", __latency_lines_frontend_body_summaries[13]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(2));
}

}

namespace scpp { extern const int __latency_lines_frontend_body_summaries[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_frontend_body_summaries_blocked_reason_expression_not_summarized_id() {
	SCPP_CALL_DEPTH_GUARD("frontend_body_summaries::blocked_reason_expression_not_summarized_id", "/tmp/scpp-edit-latency-20260919/app/compile/model/frontend_body_summaries.phs", __latency_lines_frontend_body_summaries[14]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(3));
}

}

namespace scpp { extern const int __latency_lines_frontend_body_summaries[]; }
namespace scpp {
shared_p<FrontendBodySummaryArtifact> __latency_fn_frontend_body_summaries_new_artifact(int_t<std::uint32_t> sourceUnitId, int_t<std::uint32_t> declarationNodeId, int_t<> rowCapacity) {
	SCPP_CALL_DEPTH_GUARD("frontend_body_summaries::new_artifact", "/tmp/scpp-edit-latency-20260919/app/compile/model/frontend_body_summaries.phs", __latency_lines_frontend_body_summaries[15]);
	shared_p<FrontendBodySummaryArtifact> artifact = create<FrontendBodySummaryArtifact>();
	artifact->artifact_kind_id = __latency_fn_frontend_body_summaries_artifact_kind_frontend_body_summary_id();
	artifact->schema_version = __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
	artifact->source_unit_id = sourceUnitId;
	artifact->declaration_node_id = declarationNodeId;
	php::vector_reserve(artifact->locals, rowCapacity);
	php::vector_reserve(artifact->rows, (rowCapacity * static_cast<int_t<> >(2)));
	return artifact;
}

}
