#include "public_api_guard.hpp"
#include "scpp/api_generation.hpp"
#if __has_include("value.hpp") || __has_include("names.hpp")
#error "Tooling target leaked private model headers"
#endif
#include <thread>
#include <atomic>

int main()
{
	const scpp::string request(R"({"action":"names","registry":{"version":1,"types":["string"],"families":{"sequence":{"parameters":["type"]}},"mappings":[]},"types":["sequence<string>"]})");
	const auto expected = scpp::api_generation::generate(request);
	if (!expected.equals("[[\"sequence<string>\",\"sequence_0string_\"]]")) return 1;
	std::atomic<bool> valid = true;
	auto work = [&] {
		for (int i=0; i<8; ++i) {
			if (!scpp::api_generation::generate(request).equals(expected)) valid = false;
		}
	};
	std::thread first(work), second(work);
	first.join(); second.join();
	return valid ? 0 : 2;
}
