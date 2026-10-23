#pragma once
#include "scpp/callable_t.hpp"
#include "scpp/bool_t.hpp"
#include "scpp/int_t.hpp"
#include <thread>
namespace task_transfer_probe {
template <typename Callback> scpp::bool_t allowed(Callback callback) {
 return scpp::bool_t(callback.transfer_allowed());
}
template <typename Callback> scpp::bool_t joined_allowed(Callback callback) {
 return scpp::bool_t(callback.transfer_allowed(scpp::task_policy::joined_shared));
}
template <typename Callback> scpp::int_t<> execute(Callback callback) {
 // Admission must check the provider contract before entering this function.
 scpp::int_t<> value;
 std::thread worker([callback, &value]() mutable { value = callback(); });
 worker.join();
 return value;
}
}

namespace task_transfer_probe {
template <typename Callback> auto generic(Callback callback) {
 return callback();
}
template <typename Callback> scpp::int_t<> after(Callback callback, scpp::int_t<>) {
 return execute(callback);
}
}
