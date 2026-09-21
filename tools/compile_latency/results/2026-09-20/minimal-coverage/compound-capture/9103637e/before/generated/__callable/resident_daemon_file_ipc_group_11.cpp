#include <scpp/lang/php.hpp>
#include "__types/ResidentDaemonResponseRow.hpp"
#include "__types/ResidentDaemonSourceSlotEventRow.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_response_stable_hash.hpp"
#include "__callable/__latency_fn_resident_daemon_file_ipc_source_slot_event_stable_hash.hpp"
namespace scpp { extern const int __latency_lines_resident_daemon_file_ipc[]; }
namespace scpp {
int_t<std::uint64_t> __latency_fn_resident_daemon_file_ipc_response_stable_hash(ResidentDaemonResponseRow row) {
	SCPP_CALL_DEPTH_GUARD("resident_daemon_file_ipc::response_stable_hash", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_daemon_file_ipc.phs", __latency_lines_resident_daemon_file_ipc[114]);
	string_t identity = required_cast<string_t>((string_t("resident_daemon_response:v2:") + cast<string_t>(cast<int_t<>>(row->response_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->request_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->stdout_text_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->stderr_text_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->summary_text_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->elapsed_us)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->status_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->exit_code))));
	return php::stable_hash_string_u64(identity);
}

}

namespace scpp { extern const int __latency_lines_resident_daemon_file_ipc[]; }
namespace scpp {
int_t<std::uint64_t> __latency_fn_resident_daemon_file_ipc_source_slot_event_stable_hash(ResidentDaemonSourceSlotEventRow row) {
	SCPP_CALL_DEPTH_GUARD("resident_daemon_file_ipc::source_slot_event_stable_hash", "/tmp/scpp-edit-latency-20260919/app/compile/incremental/resident_daemon_file_ipc.phs", __latency_lines_resident_daemon_file_ipc[115]);
	string_t identity = required_cast<string_t>((string_t("resident_daemon_event:v2:") + cast<string_t>(cast<int_t<>>(row->event_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->request_id)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->packed_event)) + string_t(":") + cast<string_t>(cast<int_t<>>(row->status_id))));
	return php::stable_hash_string_u64(identity);
}

}
