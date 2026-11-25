#include <scpp/lang/php.hpp>
#include "__types/proof_metrics.hpp"
#include "__callable/__latency_fn_proof_metrics_kind_counter_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_uint16_from_int.hpp"
#include "__callable/__latency_fn_proof_metrics_report_aggregate_owner_id.hpp"
#include "__callable/__latency_fn_structure_row_ids_none_id.hpp"
#include "__callable/__latency_fn_proof_metrics_key_mt_single_pipeline_live_publication_summary_runs.hpp"
#include "__callable/__latency_fn_proof_metrics_key_mt_single_pipeline_live_publication_summary_selected_stages.hpp"
#include "__callable/__latency_fn_proof_metrics_key_mt_single_pipeline_live_publication_summary_one_worker_selected.hpp"
#include "__callable/__latency_fn_proof_metrics_key_mt_single_pipeline_live_publication_summary_multi_worker_selected.hpp"
#include "__callable/__latency_fn_proof_metrics_key_mt_single_pipeline_live_publication_summary_promotion_blocked.hpp"
#include "__callable/__latency_fn_proof_metrics_key_mt_single_pipeline_live_publication_summary_side_effect_blocked.hpp"
#include "__callable/__latency_fn_proof_metrics_key_mt_single_pipeline_live_publication_summary_published_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_mt_single_pipeline_live_publication_summary_payload_copy_bytes.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_read_descriptor_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_read_result_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_read_source_bytes.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_read_line_count.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_read_result_metadata_bytes.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_read_failure_rows.hpp"
#include "__callable/__latency_fn_proof_metrics_key_source_read_manifest_order_match.hpp"
namespace scpp { extern const int __latency_lines_proof_metrics[]; }
namespace scpp {
bool_t proof_metrics::__scpp_static_accepts(const void* __scpp_token) {
	if (__scpp_token == proof_metrics::__scpp_static_token()) {
		return static_cast<bool_t>(true);
	}
	return static_cast<bool_t>(false);
}

}

namespace scpp { extern const int __latency_lines_proof_metrics[]; }
namespace scpp {
int_t<std::uint16_t> __latency_fn_proof_metrics_kind_counter_id() {
	SCPP_CALL_DEPTH_GUARD("proof_metrics::kind_counter_id", "/tmp/scpp-edit-latency-20260919/app/compile/support/proof_metrics.phs", __latency_lines_proof_metrics[0]);
	return __latency_fn_structure_row_ids_uint16_from_int(static_cast<int_t<> >(1));
}

}

namespace scpp { extern const int __latency_lines_proof_metrics[]; }
namespace scpp {
int_t<std::uint32_t> __latency_fn_proof_metrics_report_aggregate_owner_id() {
	SCPP_CALL_DEPTH_GUARD("proof_metrics::report_aggregate_owner_id", "/tmp/scpp-edit-latency-20260919/app/compile/support/proof_metrics.phs", __latency_lines_proof_metrics[1]);
	return __latency_fn_structure_row_ids_none_id();
}

}

namespace scpp { extern const int __latency_lines_proof_metrics[]; }
namespace scpp {
string_t __latency_fn_proof_metrics_key_mt_single_pipeline_live_publication_summary_runs() {
	SCPP_CALL_DEPTH_GUARD("proof_metrics::key_mt_single_pipeline_live_publication_summary_runs", "/tmp/scpp-edit-latency-20260919/app/compile/support/proof_metrics.phs", __latency_lines_proof_metrics[2]);
	return string_t("mt_single_pipeline_live_publication_summary_runs");
}

}

namespace scpp { extern const int __latency_lines_proof_metrics[]; }
namespace scpp {
string_t __latency_fn_proof_metrics_key_mt_single_pipeline_live_publication_summary_selected_stages() {
	SCPP_CALL_DEPTH_GUARD("proof_metrics::key_mt_single_pipeline_live_publication_summary_selected_stages", "/tmp/scpp-edit-latency-20260919/app/compile/support/proof_metrics.phs", __latency_lines_proof_metrics[3]);
	return string_t("mt_single_pipeline_live_publication_summary_selected_stages");
}

}

namespace scpp { extern const int __latency_lines_proof_metrics[]; }
namespace scpp {
string_t __latency_fn_proof_metrics_key_mt_single_pipeline_live_publication_summary_one_worker_selected() {
	SCPP_CALL_DEPTH_GUARD("proof_metrics::key_mt_single_pipeline_live_publication_summary_one_worker_selected", "/tmp/scpp-edit-latency-20260919/app/compile/support/proof_metrics.phs", __latency_lines_proof_metrics[4]);
	return string_t("mt_single_pipeline_live_publication_summary_one_worker_selected");
}

}

namespace scpp { extern const int __latency_lines_proof_metrics[]; }
namespace scpp {
string_t __latency_fn_proof_metrics_key_mt_single_pipeline_live_publication_summary_multi_worker_selected() {
	SCPP_CALL_DEPTH_GUARD("proof_metrics::key_mt_single_pipeline_live_publication_summary_multi_worker_selected", "/tmp/scpp-edit-latency-20260919/app/compile/support/proof_metrics.phs", __latency_lines_proof_metrics[5]);
	return string_t("mt_single_pipeline_live_publication_summary_multi_worker_selected");
}

}

namespace scpp { extern const int __latency_lines_proof_metrics[]; }
namespace scpp {
string_t __latency_fn_proof_metrics_key_mt_single_pipeline_live_publication_summary_promotion_blocked() {
	SCPP_CALL_DEPTH_GUARD("proof_metrics::key_mt_single_pipeline_live_publication_summary_promotion_blocked", "/tmp/scpp-edit-latency-20260919/app/compile/support/proof_metrics.phs", __latency_lines_proof_metrics[6]);
	return string_t("mt_single_pipeline_live_publication_summary_promotion_blocked");
}

}

namespace scpp { extern const int __latency_lines_proof_metrics[]; }
namespace scpp {
string_t __latency_fn_proof_metrics_key_mt_single_pipeline_live_publication_summary_side_effect_blocked() {
	SCPP_CALL_DEPTH_GUARD("proof_metrics::key_mt_single_pipeline_live_publication_summary_side_effect_blocked", "/tmp/scpp-edit-latency-20260919/app/compile/support/proof_metrics.phs", __latency_lines_proof_metrics[7]);
	return string_t("mt_single_pipeline_live_publication_summary_side_effect_blocked");
}

}

namespace scpp { extern const int __latency_lines_proof_metrics[]; }
namespace scpp {
string_t __latency_fn_proof_metrics_key_mt_single_pipeline_live_publication_summary_published_rows() {
	SCPP_CALL_DEPTH_GUARD("proof_metrics::key_mt_single_pipeline_live_publication_summary_published_rows", "/tmp/scpp-edit-latency-20260919/app/compile/support/proof_metrics.phs", __latency_lines_proof_metrics[8]);
	return string_t("mt_single_pipeline_live_publication_summary_published_rows");
}

}

namespace scpp { extern const int __latency_lines_proof_metrics[]; }
namespace scpp {
string_t __latency_fn_proof_metrics_key_mt_single_pipeline_live_publication_summary_payload_copy_bytes() {
	SCPP_CALL_DEPTH_GUARD("proof_metrics::key_mt_single_pipeline_live_publication_summary_payload_copy_bytes", "/tmp/scpp-edit-latency-20260919/app/compile/support/proof_metrics.phs", __latency_lines_proof_metrics[9]);
	return string_t("mt_single_pipeline_live_publication_summary_payload_copy_bytes");
}

}

namespace scpp { extern const int __latency_lines_proof_metrics[]; }
namespace scpp {
string_t __latency_fn_proof_metrics_key_source_read_descriptor_rows() {
	SCPP_CALL_DEPTH_GUARD("proof_metrics::key_source_read_descriptor_rows", "/tmp/scpp-edit-latency-20260919/app/compile/support/proof_metrics.phs", __latency_lines_proof_metrics[10]);
	return string_t("source_read_descriptor_rows");
}

}

namespace scpp { extern const int __latency_lines_proof_metrics[]; }
namespace scpp {
string_t __latency_fn_proof_metrics_key_source_read_result_rows() {
	SCPP_CALL_DEPTH_GUARD("proof_metrics::key_source_read_result_rows", "/tmp/scpp-edit-latency-20260919/app/compile/support/proof_metrics.phs", __latency_lines_proof_metrics[11]);
	return string_t("source_read_result_rows");
}

}

namespace scpp { extern const int __latency_lines_proof_metrics[]; }
namespace scpp {
string_t __latency_fn_proof_metrics_key_source_read_source_bytes() {
	SCPP_CALL_DEPTH_GUARD("proof_metrics::key_source_read_source_bytes", "/tmp/scpp-edit-latency-20260919/app/compile/support/proof_metrics.phs", __latency_lines_proof_metrics[12]);
	return string_t("source_read_source_bytes");
}

}

namespace scpp { extern const int __latency_lines_proof_metrics[]; }
namespace scpp {
string_t __latency_fn_proof_metrics_key_source_read_line_count() {
	SCPP_CALL_DEPTH_GUARD("proof_metrics::key_source_read_line_count", "/tmp/scpp-edit-latency-20260919/app/compile/support/proof_metrics.phs", __latency_lines_proof_metrics[13]);
	return string_t("source_read_line_count");
}

}

namespace scpp { extern const int __latency_lines_proof_metrics[]; }
namespace scpp {
string_t __latency_fn_proof_metrics_key_source_read_result_metadata_bytes() {
	SCPP_CALL_DEPTH_GUARD("proof_metrics::key_source_read_result_metadata_bytes", "/tmp/scpp-edit-latency-20260919/app/compile/support/proof_metrics.phs", __latency_lines_proof_metrics[14]);
	return string_t("source_read_result_metadata_bytes");
}

}

namespace scpp { extern const int __latency_lines_proof_metrics[]; }
namespace scpp {
string_t __latency_fn_proof_metrics_key_source_read_failure_rows() {
	SCPP_CALL_DEPTH_GUARD("proof_metrics::key_source_read_failure_rows", "/tmp/scpp-edit-latency-20260919/app/compile/support/proof_metrics.phs", __latency_lines_proof_metrics[15]);
	return string_t("source_read_failure_rows");
}

}

namespace scpp { extern const int __latency_lines_proof_metrics[]; }
namespace scpp {
string_t __latency_fn_proof_metrics_key_source_read_manifest_order_match() {
	SCPP_CALL_DEPTH_GUARD("proof_metrics::key_source_read_manifest_order_match", "/tmp/scpp-edit-latency-20260919/app/compile/support/proof_metrics.phs", __latency_lines_proof_metrics[16]);
	return string_t("source_read_manifest_order_match");
}

}
