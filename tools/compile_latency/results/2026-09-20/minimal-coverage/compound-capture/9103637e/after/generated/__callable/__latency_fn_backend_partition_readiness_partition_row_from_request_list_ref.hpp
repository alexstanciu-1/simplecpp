#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct BackendPartitionExecutionRow;
struct BackendRequestListRef;
BackendPartitionExecutionRow __latency_fn_backend_partition_readiness_partition_row_from_request_list_ref(int_t<std::uint32_t> executionId, BackendRequestListRef requestList);
}
