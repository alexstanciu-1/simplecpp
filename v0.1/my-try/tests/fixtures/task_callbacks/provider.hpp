#pragma once
#include "scpp/int_t.hpp"
#include <functional>
#include <thread>

namespace task_callbacks_probe {
using integer = scpp::int_t<>;
inline std::function<integer(integer, integer)> retained;
inline void save(std::function<integer(integer, integer)> callback) { retained = std::move(callback); }
inline integer invoke() {
	integer result;
	std::thread worker([&] { result = retained(integer(3), integer(4)); });
	worker.join();
	retained = {};
	return result;
}
inline void zero(std::function<void()> callback) { callback(); }
inline void pair(std::function<void(integer, integer)> callback) { callback(integer(5), integer(6)); }
}
