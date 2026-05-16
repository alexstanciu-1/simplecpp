#include "modules/curl/curl.hpp"

#if SCPP_HAS_CURL

#include <curl/curl.h>

#include <cstdint>
#include <cstring>
#include <string>
#include <utility>

namespace scpp::curl {

namespace {

constexpr std::int64_t local_error_bad_argument = static_cast<std::int64_t>(CURLE_BAD_FUNCTION_ARGUMENT);
constexpr std::int64_t local_error_failed_init = static_cast<std::int64_t>(CURLE_FAILED_INIT);

struct slist_guard final {
	curl_slist *head = nullptr;

	~slist_guard() {
		if (head != nullptr) {
			curl_slist_free_all(head);
		}
	}

	slist_guard(const slist_guard &) = delete;
	slist_guard &operator=(const slist_guard &) = delete;
	slist_guard() = default;
};

[[nodiscard]] bool ensure_global_init() {
	static const bool initialized = []() {
		return curl_global_init(CURL_GLOBAL_DEFAULT) == CURLE_OK;
	}();
	return initialized;
}

[[nodiscard]] result<shared_p<handle>> require_open_handle(const shared_p<handle> &resource, const char *function_name) {
	if (!resource.has_value().native_value() || resource.get() == nullptr) {
		return error_t(string_t(std::string(function_name) + " requires a valid curl_handle"));
	}
	if (resource->closed.native_value()) {
		resource->errno_code = int_t(local_error_bad_argument);
		resource->error = string_t(std::string(function_name) + " cannot use a closed curl_handle");
		return error_t(resource->error);
	}
	return resource;
}

void clear_error(handle &resource) {
	resource.errno_code = int_t(0);
	resource.error = string_t("");
}

template <typename TResult>
[[nodiscard]] result<TResult> fail(handle &resource, std::int64_t code, const std::string &message) {
	resource.errno_code = int_t(code);
	resource.error = string_t(message);
	resource.last_response = null;
	return error_t(resource.error);
}

[[nodiscard]] const char *option_name(const int_t &option) {
	switch (option.native_value()) {
		case 1: return "CURLOPT_URL";
		case 2: return "CURLOPT_RETURNTRANSFER";
		case 3: return "CURLOPT_HTTPHEADER";
		case 4: return "CURLOPT_POST";
		case 5: return "CURLOPT_POSTFIELDS";
		case 6: return "CURLOPT_CUSTOMREQUEST";
		case 7: return "CURLOPT_TIMEOUT";
		case 8: return "CURLOPT_CONNECTTIMEOUT";
		case 9: return "CURLOPT_FOLLOWLOCATION";
		case 10: return "CURLOPT_USERAGENT";
		case 11: return "CURLOPT_SSL_VERIFYPEER";
		case 12: return "CURLOPT_SSL_VERIFYHOST";
		default: return "unknown CURLOPT";
	}
}

[[nodiscard]] std::string trim_header_line(const char *data, std::size_t size) {
	std::string line(data, size);
	while (!line.empty() && (line.back() == '\n' || line.back() == '\r')) {
		line.pop_back();
	}
	return line;
}

[[nodiscard]] std::size_t write_body(char *ptr, std::size_t size, std::size_t nmemb, void *userdata) {
	const std::size_t bytes = size * nmemb;
	auto *buffer = static_cast<std::string *>(userdata);
	buffer->append(ptr, bytes);
	return bytes;
}

[[nodiscard]] std::size_t write_header(char *ptr, std::size_t size, std::size_t nmemb, void *userdata) {
	const std::size_t bytes = size * nmemb;
	auto *buffer = static_cast<vector_t<string_t> *>(userdata);
	const std::string line = trim_header_line(ptr, bytes);
	if (!line.empty()) {
		buffer->append(string_t(line));
	}
	return bytes;
}

[[nodiscard]] result<bool_t> apply_option(CURL *easy, CURLoption option, long value, handle &resource, const char *name) {
	const CURLcode rc = curl_easy_setopt(easy, option, value);
	if (rc != CURLE_OK) {
		return fail<bool_t>(resource, static_cast<std::int64_t>(rc), std::string("curl_exec(): failed to apply ") + name + ": " + curl_easy_strerror(rc));
	}
	return bool_t(true);
}

[[nodiscard]] result<bool_t> apply_option(CURL *easy, CURLoption option, const char *value, handle &resource, const char *name) {
	const CURLcode rc = curl_easy_setopt(easy, option, value);
	if (rc != CURLE_OK) {
		return fail<bool_t>(resource, static_cast<std::int64_t>(rc), std::string("curl_exec(): failed to apply ") + name + ": " + curl_easy_strerror(rc));
	}
	return bool_t(true);
}

} // namespace

void handle::reset_state() {
	url = string_t("");
	returntransfer = bool_t(true);
	httpheader = vector_t<string_t>();
	post = bool_t(false);
	postfields = string_t("");
	customrequest = string_t("");
	timeout = int_t(0);
	connecttimeout = int_t(0);
	followlocation = bool_t(false);
	useragent = string_t("simplecpp-curl/1.0");
	ssl_verifypeer = bool_t(true);
	ssl_verifyhost = int_t(2);
	errno_code = int_t(0);
	error = string_t("");
	last_response = null;
}

result<shared_p<handle>> init() {
	auto resource = shared<handle>();
	if (!ensure_global_init()) {
		return error_t(string_t("curl_init(): libcurl global initialization failed"));
	}
	resource->reset_state();
	resource->closed = bool_t(false);
	return resource;
}

result<shared_p<handle>> init(const string_t &url) {
	auto resource = init();
	if (!resource.has_value().native_value()) {
		return *resource.error();
	}
	resource.value()->url = url;
	return resource.value();
}

result<bool_t> setopt(const shared_p<handle> &resource, const int_t &option, const string_t &value) {
	auto checked = require_open_handle(resource, "curl_setopt()");
	if (!checked.has_value().native_value()) {
		return *checked.error();
	}
	auto &handle_ref = *checked.value();
	clear_error(handle_ref);
	switch (option.native_value()) {
		case 1: handle_ref.url = value; return bool_t(true);
		case 5: handle_ref.postfields = value; return bool_t(true);
		case 6: handle_ref.customrequest = value; return bool_t(true);
		case 10: handle_ref.useragent = value; return bool_t(true);
		default:
			return fail<bool_t>(handle_ref, local_error_bad_argument,
				std::string("curl_setopt(): option ") + option_name(option) + " does not accept a string value in this pass");
	}
}

result<bool_t> setopt(const shared_p<handle> &resource, const int_t &option, const int_t &value) {
	auto checked = require_open_handle(resource, "curl_setopt()");
	if (!checked.has_value().native_value()) {
		return *checked.error();
	}
	auto &handle_ref = *checked.value();
	clear_error(handle_ref);
	switch (option.native_value()) {
		case 2: handle_ref.returntransfer = bool_t(value.native_value() != 0); return bool_t(true);
		case 4: handle_ref.post = bool_t(value.native_value() != 0); return bool_t(true);
		case 7: handle_ref.timeout = value; return bool_t(true);
		case 8: handle_ref.connecttimeout = value; return bool_t(true);
		case 9: handle_ref.followlocation = bool_t(value.native_value() != 0); return bool_t(true);
		case 11: handle_ref.ssl_verifypeer = bool_t(value.native_value() != 0); return bool_t(true);
		case 12:
			if (value.native_value() != 0 && value.native_value() != 2) {
				return fail<bool_t>(handle_ref, local_error_bad_argument,
					"curl_setopt(): CURLOPT_SSL_VERIFYHOST only supports 0 or 2 in this pass");
			}
			handle_ref.ssl_verifyhost = value;
			return bool_t(true);
		default:
			return fail<bool_t>(handle_ref, local_error_bad_argument,
				std::string("curl_setopt(): unsupported integer option ") + option_name(option));
	}
}

result<bool_t> setopt(const shared_p<handle> &resource, const int_t &option, const bool_t &value) {
	return setopt(resource, option, int_t(value.native_value() ? 1 : 0));
}

result<bool_t> setopt(const shared_p<handle> &resource, const int_t &option, const vector_t<string_t> &value) {
	auto checked = require_open_handle(resource, "curl_setopt()");
	if (!checked.has_value().native_value()) {
		return *checked.error();
	}
	auto &handle_ref = *checked.value();
	clear_error(handle_ref);
	if (option.native_value() != 3) {
		return fail<bool_t>(handle_ref, local_error_bad_argument,
			std::string("curl_setopt(): option ") + option_name(option) + " does not accept vector<string> in this pass");
	}
	handle_ref.httpheader = value;
	return bool_t(true);
}

result<shared_p<response>> exec(const shared_p<handle> &resource) {
	auto checked = require_open_handle(resource, "curl_exec()");
	if (!checked.has_value().native_value()) {
		return *checked.error();
	}
	auto &handle_ref = *checked.value();
	clear_error(handle_ref);

	if (handle_ref.url.native_value().empty()) {
		return fail<shared_p<response>>(handle_ref, local_error_bad_argument, "curl_exec(): CURLOPT_URL is required before execution");
	}

	CURL *easy = curl_easy_init();
	if (easy == nullptr) {
		return fail<shared_p<response>>(handle_ref, local_error_failed_init, "curl_exec(): libcurl failed to initialize an easy handle");
	}

	std::string body_buffer;
	vector_t<string_t> header_buffer;
	char error_buffer[CURL_ERROR_SIZE];
	error_buffer[0] = '\0';
	slist_guard request_headers;

	static_cast<void>(curl_easy_setopt(easy, CURLOPT_ERRORBUFFER, error_buffer));

	auto option_result = apply_option(easy, ::CURLOPT_URL, handle_ref.url.native_value().c_str(), handle_ref, "CURLOPT_URL");
	if (!option_result.has_value().native_value()) {
		curl_easy_cleanup(easy);
		return *option_result.error();
	}
	option_result = apply_option(easy, ::CURLOPT_NOSIGNAL, 1L, handle_ref, "CURLOPT_NOSIGNAL");
	if (!option_result.has_value().native_value()) {
		curl_easy_cleanup(easy);
		return *option_result.error();
	}
	option_result = apply_option(easy, ::CURLOPT_FOLLOWLOCATION, handle_ref.followlocation.native_value() ? 1L : 0L, handle_ref, "CURLOPT_FOLLOWLOCATION");
	if (!option_result.has_value().native_value()) {
		curl_easy_cleanup(easy);
		return *option_result.error();
	}
	option_result = apply_option(easy, ::CURLOPT_SSL_VERIFYPEER, handle_ref.ssl_verifypeer.native_value() ? 1L : 0L, handle_ref, "CURLOPT_SSL_VERIFYPEER");
	if (!option_result.has_value().native_value()) {
		curl_easy_cleanup(easy);
		return *option_result.error();
	}
	option_result = apply_option(easy, ::CURLOPT_SSL_VERIFYHOST, static_cast<long>(handle_ref.ssl_verifyhost.native_value()), handle_ref, "CURLOPT_SSL_VERIFYHOST");
	if (!option_result.has_value().native_value()) {
		curl_easy_cleanup(easy);
		return *option_result.error();
	}

	if (!handle_ref.useragent.native_value().empty()) {
		option_result = apply_option(easy, ::CURLOPT_USERAGENT, handle_ref.useragent.native_value().c_str(), handle_ref, "CURLOPT_USERAGENT");
		if (!option_result.has_value().native_value()) {
			curl_easy_cleanup(easy);
			return *option_result.error();
		}
	}
	if (handle_ref.timeout.native_value() > 0) {
		option_result = apply_option(easy, ::CURLOPT_TIMEOUT, static_cast<long>(handle_ref.timeout.native_value()), handle_ref, "CURLOPT_TIMEOUT");
		if (!option_result.has_value().native_value()) {
			curl_easy_cleanup(easy);
			return *option_result.error();
		}
	}
	if (handle_ref.connecttimeout.native_value() > 0) {
		option_result = apply_option(easy, ::CURLOPT_CONNECTTIMEOUT, static_cast<long>(handle_ref.connecttimeout.native_value()), handle_ref, "CURLOPT_CONNECTTIMEOUT");
		if (!option_result.has_value().native_value()) {
			curl_easy_cleanup(easy);
			return *option_result.error();
		}
	}
	if (handle_ref.post.native_value()) {
		option_result = apply_option(easy, ::CURLOPT_POST, 1L, handle_ref, "CURLOPT_POST");
		if (!option_result.has_value().native_value()) {
			curl_easy_cleanup(easy);
			return *option_result.error();
		}
	}
	if (!handle_ref.postfields.native_value().empty()) {
		option_result = apply_option(easy, ::CURLOPT_POSTFIELDS, handle_ref.postfields.native_value().c_str(), handle_ref, "CURLOPT_POSTFIELDS");
		if (!option_result.has_value().native_value()) {
			curl_easy_cleanup(easy);
			return *option_result.error();
		}
		static_cast<void>(curl_easy_setopt(easy, ::CURLOPT_POSTFIELDSIZE, static_cast<long>(handle_ref.postfields.native_value().size())));
	}
	if (!handle_ref.customrequest.native_value().empty()) {
		option_result = apply_option(easy, ::CURLOPT_CUSTOMREQUEST, handle_ref.customrequest.native_value().c_str(), handle_ref, "CURLOPT_CUSTOMREQUEST");
		if (!option_result.has_value().native_value()) {
			curl_easy_cleanup(easy);
			return *option_result.error();
		}
	}
	if (!handle_ref.httpheader.empty().native_value()) {
		for (std::size_t index = 0; index < handle_ref.httpheader.size(); ++index) {
			curl_slist *next = curl_slist_append(request_headers.head, handle_ref.httpheader[index].native_value().c_str());
			if (next == nullptr) {
				curl_easy_cleanup(easy);
				return fail<shared_p<response>>(handle_ref, local_error_failed_init, "curl_exec(): failed to allocate request header list");
			}
			request_headers.head = next;
		}
		const CURLcode header_rc = curl_easy_setopt(easy, ::CURLOPT_HTTPHEADER, request_headers.head);
		if (header_rc != CURLE_OK) {
			curl_easy_cleanup(easy);
			return fail<shared_p<response>>(handle_ref, static_cast<std::int64_t>(header_rc), std::string("curl_exec(): failed to apply CURLOPT_HTTPHEADER: ") + curl_easy_strerror(header_rc));
		}
	}

	static_cast<void>(curl_easy_setopt(easy, ::CURLOPT_WRITEFUNCTION, &write_body));
	static_cast<void>(curl_easy_setopt(easy, ::CURLOPT_WRITEDATA, &body_buffer));
	static_cast<void>(curl_easy_setopt(easy, ::CURLOPT_HEADERFUNCTION, &write_header));
	static_cast<void>(curl_easy_setopt(easy, ::CURLOPT_HEADERDATA, &header_buffer));

	const CURLcode rc = curl_easy_perform(easy);
	if (rc != CURLE_OK) {
		const std::string detail = error_buffer[0] != '\0' ? std::string(error_buffer) : std::string(curl_easy_strerror(rc));
		curl_easy_cleanup(easy);
		return fail<shared_p<response>>(handle_ref, static_cast<std::int64_t>(rc), std::string("curl_exec(): ") + detail);
	}

	auto reply = shared<response>();
	reply->body = string_t(body_buffer);
	reply->headers = header_buffer;

	long response_code = 0;
	long header_size = 0;
	long request_size = 0;
	long redirect_count = 0;
	char *effective_url = nullptr;
	char *content_type = nullptr;

	static_cast<void>(curl_easy_getinfo(easy, ::CURLINFO_RESPONSE_CODE, &response_code));
	static_cast<void>(curl_easy_getinfo(easy, ::CURLINFO_HEADER_SIZE, &header_size));
	static_cast<void>(curl_easy_getinfo(easy, ::CURLINFO_REQUEST_SIZE, &request_size));
	static_cast<void>(curl_easy_getinfo(easy, ::CURLINFO_REDIRECT_COUNT, &redirect_count));
	static_cast<void>(curl_easy_getinfo(easy, ::CURLINFO_EFFECTIVE_URL, &effective_url));
	static_cast<void>(curl_easy_getinfo(easy, ::CURLINFO_CONTENT_TYPE, &content_type));

#ifdef CURLINFO_TOTAL_TIME_T
	curl_off_t total_time_us = 0;
	if (curl_easy_getinfo(easy, ::CURLINFO_TOTAL_TIME_T, &total_time_us) == CURLE_OK) {
		reply->total_time_ms = int_t(static_cast<std::int64_t>(total_time_us / 1000));
	}
#else
	double total_time_seconds = 0.0;
	if (curl_easy_getinfo(easy, ::CURLINFO_TOTAL_TIME, &total_time_seconds) == CURLE_OK) {
		reply->total_time_ms = int_t(static_cast<std::int64_t>(total_time_seconds * 1000.0));
	}
#endif

	reply->status_code = int_t(static_cast<std::int64_t>(response_code));
	reply->header_size = int_t(static_cast<std::int64_t>(header_size));
	reply->request_size = int_t(static_cast<std::int64_t>(request_size));
	reply->redirect_count = int_t(static_cast<std::int64_t>(redirect_count));
	if (effective_url != nullptr) {
		reply->effective_url = string_t(effective_url);
	}
	if (content_type != nullptr) {
		reply->content_type = string_t(content_type);
	}

	curl_easy_cleanup(easy);
	handle_ref.last_response = reply;
	clear_error(handle_ref);
	return reply;
}

result<mixed_t> getinfo(const shared_p<handle> &resource, const int_t &info) {
	auto checked = require_open_handle(resource, "curl_getinfo()");
	if (!checked.has_value().native_value()) {
		return *checked.error();
	}
	auto &handle_ref = *checked.value();
	clear_error(handle_ref);
	if (!handle_ref.last_response.has_value().native_value() || handle_ref.last_response.get() == nullptr) {
		return fail<mixed_t>(handle_ref, local_error_bad_argument, "curl_getinfo(): no response is available; call curl_exec() first");
	}

	const auto &reply = *handle_ref.last_response.get();
	switch (info.native_value()) {
		case 101: return mixed_t(reply.status_code);
		case 102: return mixed_t(reply.effective_url);
		case 103: return mixed_t(reply.content_type);
		case 104: return mixed_t(reply.total_time_ms);
		case 105: return mixed_t(reply.header_size);
		case 106: return mixed_t(reply.request_size);
		case 107: return mixed_t(reply.redirect_count);
		default:
			return fail<mixed_t>(handle_ref, local_error_bad_argument, "curl_getinfo(): unsupported CURLINFO selector in this pass");
	}
}

int_t errno_code(const shared_p<handle> &resource) {
	if (!resource.has_value().native_value() || resource.get() == nullptr) {
		return int_t(0);
	}
	return resource->errno_code;
}

string_t error_string(const shared_p<handle> &resource) {
	if (!resource.has_value().native_value() || resource.get() == nullptr) {
		return string_t("");
	}
	return resource->error;
}

result<bool_t> reset(const shared_p<handle> &resource) {
	auto checked = require_open_handle(resource, "curl_reset()");
	if (!checked.has_value().native_value()) {
		return *checked.error();
	}
	checked.value()->reset_state();
	checked.value()->closed = bool_t(false);
	return bool_t(true);
}

result<bool_t> close(const shared_p<handle> &resource) {
	auto checked = require_open_handle(resource, "curl_close()");
	if (!checked.has_value().native_value()) {
		return *checked.error();
	}
	checked.value()->closed = bool_t(true);
	checked.value()->last_response = null;
	clear_error(*checked.value());
	return bool_t(true);
}

string_t strerror(const int_t &code) {
	return string_t(curl_easy_strerror(static_cast<CURLcode>(code.native_value())));
}

} // namespace scpp::curl

#endif
