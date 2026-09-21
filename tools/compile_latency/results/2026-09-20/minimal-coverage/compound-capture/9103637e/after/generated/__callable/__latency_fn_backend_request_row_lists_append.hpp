#include <scpp/lang/php.hpp>
#pragma once
namespace scpp {
struct BackendRequestAuthorizationRow;
class BackendRequestRowList;
int_t<std::uint32_t> __latency_fn_backend_request_row_lists_append(shared_p<BackendRequestRowList> list, BackendRequestAuthorizationRow row);
}
