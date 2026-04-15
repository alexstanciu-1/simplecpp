#include <algorithm>
#include <atomic>
#include <cerrno>
#include <csignal>
#include <cctype>
#include <cstdlib>
#include <cstring>
#include <filesystem>
#include <iostream>
#include <mutex>
#include <string>
#include <string_view>
#include <thread>
#include <vector>
#include <stdexcept>

#include <fcgiapp.h>

#include "scpp/fastcgi.hpp"

#ifndef SCPP_FCGI_DEFAULT_WORKERS
#define SCPP_FCGI_DEFAULT_WORKERS 1
#endif

#ifndef SCPP_FCGI_DEFAULT_MAX_BODY_SIZE
#define SCPP_FCGI_DEFAULT_MAX_BODY_SIZE (4 * 1024 * 1024)
#endif

#ifndef SCPP_FCGI_DEFAULT_MAX_REQUESTS
#define SCPP_FCGI_DEFAULT_MAX_REQUESTS 0
#endif

namespace {

struct server_config_t {
	std::string socket_path;
	int workers = SCPP_FCGI_DEFAULT_WORKERS;
	std::size_t max_body_size = static_cast<std::size_t>(SCPP_FCGI_DEFAULT_MAX_BODY_SIZE);
	std::size_t max_requests = static_cast<std::size_t>(SCPP_FCGI_DEFAULT_MAX_REQUESTS);
	bool debug = false;
};

std::atomic<bool> g_stop_requested{false};
std::atomic<std::size_t> g_total_requests{0};
std::mutex g_log_mutex;

void log_line(const std::string& message)
{
	std::lock_guard<std::mutex> lock(g_log_mutex);
	std::cerr << "[scpp-fcgi] " << message << std::endl;
}

void signal_handler(int)
{
	g_stop_requested.store(true, std::memory_order_relaxed);
}

std::string lowercase_copy(std::string value)
{
	std::transform(value.begin(), value.end(), value.begin(), [](unsigned char ch) {
		return static_cast<char>(std::tolower(ch));
	});
	return value;
}

std::string url_decode(std::string_view input)
{
	std::string output;
	output.reserve(input.size());
	for (std::size_t i = 0; i < input.size(); ++i) {
		const char ch = input[i];
		if (ch == '+' ) {
			output.push_back(' ');
			continue;
		}
		if (ch == '%' && i + 2 < input.size()) {
			const char hi = input[i + 1];
			const char lo = input[i + 2];
			if (std::isxdigit(static_cast<unsigned char>(hi)) != 0 && std::isxdigit(static_cast<unsigned char>(lo)) != 0) {
				std::string hex;
				hex.push_back(hi);
				hex.push_back(lo);
				output.push_back(static_cast<char>(std::strtol(hex.c_str(), nullptr, 16)));
				i += 2;
				continue;
			}
		}
		output.push_back(ch);
	}
	return output;
}

scpp::fcgi::string_map_t parse_query_string(const std::string& query_string)
{
	scpp::fcgi::string_map_t params;
	std::size_t start = 0;
	while (start <= query_string.size()) {
		const std::size_t end = query_string.find('&', start);
		const std::string_view pair = end == std::string::npos
			? std::string_view{query_string}.substr(start)
			: std::string_view{query_string}.substr(start, end - start);
		if (!pair.empty()) {
			const std::size_t eq = pair.find('=');
			const std::string key = url_decode(pair.substr(0, eq));
			const std::string value = eq == std::string_view::npos ? std::string{} : url_decode(pair.substr(eq + 1));
			params[key] = value;
		}
		if (end == std::string::npos) {
			break;
		}
		start = end + 1;
	}
	return params;
}

scpp::fcgi::string_map_t parse_cookie_header(const std::string& cookie_header)
{
	scpp::fcgi::string_map_t cookies;
	std::size_t start = 0;
	while (start < cookie_header.size()) {
		while (start < cookie_header.size() && (cookie_header[start] == ' ' || cookie_header[start] == ';')) {
			++start;
		}
		const std::size_t end = cookie_header.find(';', start);
		const std::string_view pair = end == std::string::npos
			? std::string_view{cookie_header}.substr(start)
			: std::string_view{cookie_header}.substr(start, end - start);
		const std::size_t eq = pair.find('=');
		if (eq != std::string_view::npos) {
			cookies.emplace(std::string{pair.substr(0, eq)}, std::string{pair.substr(eq + 1)});
		}
		if (end == std::string::npos) {
			break;
		}
		start = end + 1;
	}
	return cookies;
}

std::string read_stdin(FCGX_Request& request, std::size_t max_body_size)
{
	std::string body;
	char buffer[8192];
	while (true) {
		const int count = FCGX_GetStr(buffer, static_cast<int>(sizeof(buffer)), request.in);
		if (count <= 0) {
			break;
		}
		body.append(buffer, static_cast<std::size_t>(count));
		if (body.size() > max_body_size) {
			throw std::runtime_error("request body exceeds configured max_body_size");
		}
	}
	return body;
}

std::string getenv_or_empty(char** envp, const char* name)
{
	const char* value = FCGX_GetParam(name, envp);
	return value == nullptr ? std::string{} : std::string{value};
}

scpp::fcgi::request_t build_request(FCGX_Request& raw_request, std::size_t max_body_size)
{
	scpp::fcgi::request_t request;
	request.method = getenv_or_empty(raw_request.envp, "REQUEST_METHOD");
	if (request.method.empty()) {
		request.method = "GET";
	}
	request.path = getenv_or_empty(raw_request.envp, "DOCUMENT_URI");
	if (request.path.empty()) {
		request.path = getenv_or_empty(raw_request.envp, "REQUEST_URI");
		const std::size_t query_pos = request.path.find('?');
		if (query_pos != std::string::npos) {
			request.path.erase(query_pos);
		}
	}
	if (request.path.empty()) {
		request.path = "/";
	}
	request.query_string = getenv_or_empty(raw_request.envp, "QUERY_STRING");
	request.query_params = parse_query_string(request.query_string);
	request.body = read_stdin(raw_request, max_body_size);

	for (char** env = raw_request.envp; env != nullptr && *env != nullptr; ++env) {
		const std::string_view item{*env};
		const std::size_t eq = item.find('=');
		if (eq == std::string_view::npos) {
			continue;
		}
		const std::string key{item.substr(0, eq)};
		const std::string value{item.substr(eq + 1)};
		if (key.rfind("HTTP_", 0) == 0) {
			std::string normalized = key.substr(5);
			std::replace(normalized.begin(), normalized.end(), '_', '-');
			request.headers[lowercase_copy(std::move(normalized))] = value;
			continue;
		}
		if (key == "CONTENT_TYPE") {
			request.headers["content-type"] = value;
			continue;
		}
		if (key == "CONTENT_LENGTH") {
			request.headers["content-length"] = value;
		}
	}

	const auto cookieIt = request.headers.find("cookie");
	if (cookieIt != request.headers.end()) {
		request.cookies = parse_cookie_header(cookieIt->second);
	}

	return request;
}

std::string status_text_for(int status_code)
{
	switch (status_code) {
		case 200: return "OK";
		case 400: return "Bad Request";
		case 404: return "Not Found";
		case 413: return "Payload Too Large";
		case 500: return "Internal Server Error";
		case 503: return "Service Unavailable";
		default: return "Status";
	}
}

void write_response(FCGX_Request& raw_request, const scpp::fcgi::response_t& response)
{
	FCGX_FPrintF(raw_request.out, "Status: %d %s\r\n", response.status_code, status_text_for(response.status_code).c_str());
	bool has_content_type = false;
	for (const auto& [key, value] : response.headers) {
		if (key == "content-type") {
			has_content_type = true;
		}
		FCGX_FPrintF(raw_request.out, "%s: %s\r\n", key.c_str(), value.c_str());
	}
	if (!has_content_type) {
		FCGX_FPrintF(raw_request.out, "content-type: application/json; charset=utf-8\r\n");
	}
	FCGX_FPrintF(raw_request.out, "content-length: %zu\r\n\r\n", response.body.size());
	if (!response.body.empty()) {
		FCGX_PutStr(response.body.data(), static_cast<int>(response.body.size()), raw_request.out);
	}
}

scpp::fcgi::response_t dispatch_request(const scpp::fcgi::request_t& request)
{
	if (request.path == "/__health") {
		return scpp::fcgi::response_t::json(200, "{\"ok\":true}");
	}

	try {
		return scpp::fcgi::http_handle(request);
	} catch (const std::exception& e) {
		return scpp::fcgi::response_t::error_json(500, "internal_error", e.what());
	} catch (...) {
		return scpp::fcgi::response_t::error_json(500, "internal_error", "unknown exception");
	}
}

int worker_loop(int socket_fd, const server_config_t& config)
{
	FCGX_Request request;
	FCGX_InitRequest(&request, socket_fd, 0);
	while (!g_stop_requested.load(std::memory_order_relaxed)) {
		const int accept_result = FCGX_Accept_r(&request);
		if (accept_result < 0) {
			if (g_stop_requested.load(std::memory_order_relaxed)) {
				break;
			}
			log_line("FCGX_Accept_r failed with code " + std::to_string(accept_result));
			return 1;
		}

		const std::size_t handled = g_total_requests.fetch_add(1, std::memory_order_relaxed) + 1;
		try {
			const auto normalized = build_request(request, config.max_body_size);
			const auto response = dispatch_request(normalized);
			write_response(request, response);
		} catch (const std::exception& e) {
			const auto response = scpp::fcgi::response_t::error_json(413, "request_error", e.what());
			write_response(request, response);
		}
		FCGX_Finish_r(&request);

		if (config.max_requests > 0 && handled >= config.max_requests) {
			g_stop_requested.store(true, std::memory_order_relaxed);
			break;
		}
	}
	return 0;
}

server_config_t parse_args(int argc, char** argv)
{
	server_config_t config;
	config.debug = std::getenv("SCPP_DEBUG") != nullptr;
	for (int i = 1; i < argc; ++i) {
		const std::string_view arg{argv[i]};
		if (arg == "--socket" && i + 1 < argc) {
			config.socket_path = argv[++i];
			continue;
		}
		if (arg == "--workers" && i + 1 < argc) {
			config.workers = std::max(1, std::atoi(argv[++i]));
			continue;
		}
		if (arg == "--max-body-size" && i + 1 < argc) {
			config.max_body_size = static_cast<std::size_t>(std::strtoull(argv[++i], nullptr, 10));
			continue;
		}
		if (arg == "--max-requests" && i + 1 < argc) {
			config.max_requests = static_cast<std::size_t>(std::strtoull(argv[++i], nullptr, 10));
			continue;
		}
		if (arg == "--help") {
			std::cout << "Usage: app_fcgi --socket /run/scpp/app.sock [--workers N] [--max-body-size BYTES] [--max-requests N]\n";
			std::exit(0);
		}
	}
	if (config.socket_path.empty()) {
		throw std::runtime_error("missing required --socket argument");
	}
	return config;
}

void ensure_socket_parent_dir(const std::string& socket_path)
{
	const std::filesystem::path socket_fs_path{socket_path};
	const auto parent = socket_fs_path.parent_path();
	if (!parent.empty()) {
		std::filesystem::create_directories(parent);
	}
}

void remove_stale_socket(const std::string& socket_path)
{
	if (!socket_path.starts_with('/')) {
		return;
	}
	std::error_code ec;
	if (std::filesystem::exists(socket_path, ec)) {
		std::filesystem::remove(socket_path, ec);
	}
}

} // namespace

int main(int argc, char** argv)
{
	try {
		const auto config = parse_args(argc, argv);
		std::signal(SIGINT, signal_handler);
		std::signal(SIGTERM, signal_handler);
		ensure_socket_parent_dir(config.socket_path);
		remove_stale_socket(config.socket_path);

		if (config.debug) {
			log_line("starting FastCGI host on " + config.socket_path);
		}

		FCGX_Init();
		const int socket_fd = FCGX_OpenSocket(config.socket_path.c_str(), config.workers * 4);
		if (socket_fd < 0) {
			log_line("failed to open FastCGI socket: " + config.socket_path + " errno=" + std::to_string(errno));
			return 1;
		}

		std::vector<std::thread> workers;
		workers.reserve(static_cast<std::size_t>(config.workers));
		for (int i = 0; i < config.workers; ++i) {
			workers.emplace_back([socket_fd, &config]() {
				worker_loop(socket_fd, config);
			});
		}
		for (auto& worker : workers) {
			worker.join();
		}

		remove_stale_socket(config.socket_path);
		return 0;
	} catch (const std::exception& e) {
		log_line(std::string{"fatal: "} + e.what());
		return 1;
	}
}
