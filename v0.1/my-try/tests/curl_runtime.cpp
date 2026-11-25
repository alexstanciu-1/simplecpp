#include "modules/curl/curl.hpp"
#include <cassert>
#include <iostream>
#include <string>

int main(int argc, char **argv) {
	assert(argc == 2);
	using namespace scpp;
	const std::string base(argv[1]);
	auto created = curl::init(string_t(base + "/payload.txt"));
	assert(created.has_value().native_value());
	auto handle = created.value();
	auto alias = handle;
	assert(curl::setopt(handle, CURLOPT_TIMEOUT, int_t<>(5)).has_value().native_value());
	auto get = curl::exec(handle);
	assert(get.has_value().native_value());
	auto retained = get.value();
	assert(retained->status_code.native_value() == 200);
	assert(retained->body.native_value() == "curl-http\n");
	assert(curl::getinfo(handle, CURLINFO_RESPONSE_CODE).value().get_int().native_value() == 200);
	assert(curl::getinfo(handle, CURLINFO_EFFECTIVE_URL).value().get_string().native_value() == base + "/payload.txt");
	assert(curl::getinfo(handle, CURLINFO_CONTENT_TYPE).has_value().native_value());
	for (const int selector : {104, 105, 106, 107}) {
		assert(curl::getinfo(handle, int_t<>(selector)).value().get_int().native_value() >= 0);
	}
	assert(curl::getinfo(handle, int_t<>(9999)).has_error().native_value());
	assert(curl::errno_code(alias).native_value() != 0);
	assert(!curl::error_string(alias).native_value().empty());
	assert(curl::reset(alias).has_value().native_value());
	assert(curl::errno_code(handle).native_value() == 0);
	assert(curl::getinfo(handle, CURLINFO_RESPONSE_CODE).has_error().native_value());
	assert(retained->body.native_value() == "curl-http\n");

	const std::string binary("a\0b\n", 4);
	assert(curl::setopt(handle, CURLOPT_URL, string_t(base + "/echo-post")).has_value().native_value());
	assert(curl::setopt(handle, CURLOPT_POST, bool_t(true)).has_value().native_value());
	assert(curl::setopt(handle, CURLOPT_POSTFIELDS, string_t(binary)).has_value().native_value());
	vector_t<string_t> headers;
	headers.push_back(string_t("Content-Type: application/octet-stream"));
	assert(curl::setopt(handle, CURLOPT_HTTPHEADER, headers).has_value().native_value());
	auto post = curl::exec(handle);
	assert(post.has_value().native_value());
	assert(post.value()->status_code.native_value() == 200);
	assert(post.value()->body.native_value() == binary);
	assert(post.value()->header_size.native_value() > 0);
	assert(post.value()->request_size.native_value() > 0);
	assert(post.value()->headers.size() > 0);

	assert(curl::setopt(handle, CURLOPT_URL, string_t(base + "/echo-json")).has_value().native_value());
	assert(curl::setopt(handle, CURLOPT_POSTFIELDS, string_t("{\"name\":\"alex\",\"count\":2}")).has_value().native_value());
	auto json = curl::exec(handle);
	assert(json.has_value().native_value());
	assert(json.value()->content_type.native_value() == "application/json");
	assert(json.value()->body.native_value() == "{\"ok\":true,\"kind\":\"json\",\"payload\":{\"name\":\"alex\",\"count\":2}}");
	assert(curl::close(alias).has_value().native_value());
	assert(curl::exec(handle).has_error().native_value());
	assert(curl::reset(handle).has_error().native_value());
	assert(curl::close(handle).has_error().native_value());
	assert(post.value()->body.native_value() == binary);
	assert(retained->body.native_value() == "curl-http\n");
	assert(!curl::strerror(int_t<>(0)).native_value().empty());
	shared_p<curl::handle> absent = null;
	assert(curl::exec(absent).has_error().native_value());
	std::cout << "curl HTTP runtime states and response lifetime pass\n";
}
