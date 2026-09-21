#include <scpp/lang/php.hpp>
#include "__callable/__latency_fn_row_segment_policy_reserved_segment_bytes.hpp"
#include "__callable/__latency_fn_row_segment_policy_segment_slack_bytes.hpp"
#include "__callable/__latency_fn_row_segment_policy_used_row_bytes.hpp"
namespace scpp { extern const int __latency_lines_row_segment_policy[]; }
namespace scpp {
int_t<> __latency_fn_row_segment_policy_segment_slack_bytes(int_t<std::uint32_t> rowCount, int_t<std::uint32_t> segmentCount, int_t<std::uint32_t> segmentCapacity, int_t<> rowSizeBytes) {
	SCPP_CALL_DEPTH_GUARD("row_segment_policy::segment_slack_bytes", "/tmp/scpp-edit-latency-20260919/app/structure_kernel/row_segment_policy.phs", __latency_lines_row_segment_policy[16]);
	int_t<> slack = required_cast<int_t<>>((__latency_fn_row_segment_policy_reserved_segment_bytes(cast<int_t<std::uint32_t>>(segmentCount), cast<int_t<std::uint32_t>>(segmentCapacity), rowSizeBytes) - __latency_fn_row_segment_policy_used_row_bytes(cast<int_t<std::uint32_t>>(rowCount), rowSizeBytes)));
	if (static_cast<bool>((slack < static_cast<int_t<> >(0)))) {
		return static_cast<int_t<> >(0);
	}
	return slack;
}

}
