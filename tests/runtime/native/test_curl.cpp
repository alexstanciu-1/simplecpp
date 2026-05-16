#include "test_common.hpp"

#include "modules/curl/curl.hpp"

#include <chrono>
#include <filesystem>
#include <fstream>
#include <string>

namespace {

class temp_dir_guard final {
private:
	std::filesystem::path path_;

public:
	temp_dir_guard()
		: path_(([]() {
			const auto base = std::filesystem::path("/tmp");
			return base / ("scpp_curl_test_" + std::to_string(static_cast<long long>(std::chrono::steady_clock::now().time_since_epoch().count())));
		}())) {
		std::filesystem::create_directories(path_);
	}

	~temp_dir_guard() {
		std::error_code error;
		std::filesystem::remove_all(path_, error);
	}

	[[nodiscard]] const std::filesystem::path &path() const noexcept {
		return path_;
	}
};

[[nodiscard]] scpp::string_t file_url_for(const std::filesystem::path &path) {
	const std::string generic = std::filesystem::absolute(path).generic_string();
	if (generic.size() > 1U && generic[1] == ':') {
		return scpp::string_t("file:///" + generic);
	}
	return scpp::string_t("file://" + generic);
}

static void test_file_exec_roundtrip() {
	temp_dir_guard guard;
	const auto file_path = guard.path() / "payload.bin";
	const std::string payload("A\0B\n", 4);
	{
		std::ofstream out(file_path, std::ios::binary | std::ios::trunc);
		out.write(payload.data(), static_cast<std::streamsize>(payload.size()));
	}

	const auto created = scpp::curl::init(file_url_for(file_path));
	assert(created.has_value().native_value());
	auto handle = created.value();

	const auto user_agent = scpp::curl::setopt(handle, scpp::CURLOPT_USERAGENT, scpp::string_t("scpp-curl-test/1.0"));
	assert(user_agent.has_value().native_value());
	const auto follow = scpp::curl::setopt(handle, scpp::CURLOPT_FOLLOWLOCATION, scpp::bool_t(true));
	assert(follow.has_value().native_value());

	const auto executed = scpp::curl::exec(handle);
	assert(executed.has_value().native_value());
	assert(executed.value()->body.native_value() == payload);
	assert(executed.value()->effective_url.native_value().rfind("file://", 0) == 0);
	assert(scpp::curl::errno_code(handle).native_value() == 0);
	assert(scpp::curl::error_string(handle).native_value().empty());

	const auto info_url = scpp::curl::getinfo(handle, scpp::CURLINFO_EFFECTIVE_URL);
	assert(info_url.has_value().native_value());
	assert(info_url.value().get_string().native_value().rfind("file://", 0) == 0);

	const auto info_status = scpp::curl::getinfo(handle, scpp::CURLINFO_RESPONSE_CODE);
	assert(info_status.has_value().native_value());
	assert(info_status.value().get_int().native_value() == executed.value()->status_code.native_value());

	const auto closed = scpp::curl::close(handle);
	assert(closed.has_value().native_value());
}

static void test_error_paths_are_explicit() {
	const auto created = scpp::curl::init();
	assert(created.has_value().native_value());
	auto handle = created.value();

	const auto missing_url = scpp::curl::exec(handle);
	assert(missing_url.has_error().native_value());
	assert(missing_url.error()->get_message().native_value().find("CURLOPT_URL is required") != std::string::npos);
	assert(scpp::curl::errno_code(handle).native_value() != 0);
	assert(scpp::curl::error_string(handle).native_value().find("CURLOPT_URL") != std::string::npos);

	const auto bad_option = scpp::curl::setopt(handle, scpp::int_t(9999), scpp::string_t("x"));
	assert(bad_option.has_error().native_value());
	assert(!bad_option.error()->get_message().native_value().empty());

	const auto no_response_info = scpp::curl::getinfo(handle, scpp::CURLINFO_RESPONSE_CODE);
	assert(no_response_info.has_error().native_value());
	assert(no_response_info.error()->get_message().native_value().find("call curl_exec() first") != std::string::npos);

	const auto closed = scpp::curl::close(handle);
	assert(closed.has_value().native_value());

	const auto use_after_close = scpp::curl::exec(handle);
	assert(use_after_close.has_error().native_value());
	assert(use_after_close.error()->get_message().native_value().find("closed curl_handle") != std::string::npos);
}

static void test_reset_restores_defaults() {
	const auto created = scpp::curl::init(scpp::string_t("file:///tmp/ignored"));
	assert(created.has_value().native_value());
	auto handle = created.value();

	assert(scpp::curl::setopt(handle, scpp::CURLOPT_TIMEOUT, scpp::int_t(12)).has_value().native_value());
	assert(scpp::curl::setopt(handle, scpp::CURLOPT_POST, scpp::bool_t(true)).has_value().native_value());
	assert(scpp::curl::setopt(handle, scpp::CURLOPT_POSTFIELDS, scpp::string_t("abc")).has_value().native_value());

	const auto reset = scpp::curl::reset(handle);
	assert(reset.has_value().native_value());
	assert(handle->url.native_value().empty());
	assert(handle->post.native_value() == false);
	assert(handle->postfields.native_value().empty());
	assert(handle->timeout.native_value() == 0);
	assert(handle->errno_code.native_value() == 0);
}

} // namespace

int main() {
	test_file_exec_roundtrip();
	test_error_paths_are_explicit();
	test_reset_restores_defaults();
	return 0;
}
