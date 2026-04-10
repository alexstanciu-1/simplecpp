#include "scpp/runtime.hpp"

namespace scpp::fcgi {

response_t http_handle(const request_t& request)
{
	if (request.path == "/invoice/render" && request.method == "POST") {
		return response_t::json(200, "{\"ok\":true,\"route\":\"invoice.render\"}");
	}

	return response_t::error_json(404, "not_found", "route not found");
}

} // namespace scpp::fcgi
