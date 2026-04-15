#pragma once

#include <cstddef>
#include <string>
#include <unordered_map>

namespace scpp::fcgi {

using string_map_t = std::unordered_map<std::string, std::string>;

// Normalized web request model shared by the PHP bridge and the FastCGI host.
// Header keys are lowercase. Query params use a simple last-write-wins map in v1.
struct request_t {
	std::string method = "GET";
	std::string path = "/";
	std::string query_string;
	std::string body;
	string_map_t headers;
	string_map_t cookies;
	string_map_t query_params;
};

// Minimal response contract for the FastCGI host.
struct response_t {
	int status_code = 200;
	string_map_t headers;
	std::string body;

	[[nodiscard]] static response_t text(int status_code, std::string body_text, std::string content_type = "text/plain; charset=utf-8")
	{
		response_t response;
		response.status_code = status_code;
		response.headers.emplace("content-type", std::move(content_type));
		response.body = std::move(body_text);
		return response;
	}

	[[nodiscard]] static response_t json(int status_code, std::string json_body)
	{
		response_t response;
		response.status_code = status_code;
		response.headers.emplace("content-type", "application/json; charset=utf-8");
		response.body = std::move(json_body);
		return response;
	}

	[[nodiscard]] static response_t error_json(int status_code, std::string code, std::string message)
	{
		return json(
			status_code,
			std::string{"{\"ok\":false,\"error\":{\"code\":\""}
				+ std::move(code)
				+ "\",\"message\":\""
				+ std::move(message)
				+ "\"}}"
		);
	}
};

// Project-level FastCGI entrypoint.
// The FastCGI host links against user/project code that defines this symbol.
[[nodiscard]] response_t http_handle(const request_t& request);

} // namespace scpp::fcgi
