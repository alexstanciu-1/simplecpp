#pragma once
#include "scpp/callable_t.hpp"
#include "scpp/bool_t.hpp"
#include "scpp/int_t.hpp"
#include <thread>
namespace task_transfer_probe {
template <typename Callback> scpp::bool_t allowed(Callback callback) {
 return scpp::bool_t(callback.transfer_allowed());
}
template <typename Callback> scpp::int_t<> execute(Callback callback) {
 callback.require_transfer();
 scpp::int_t<> value;
 std::thread worker([callback, &value]() mutable { value = callback(); });
 worker.join();
 return value;
}
}
