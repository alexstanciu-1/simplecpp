#pragma once

#include "scpp/task_permissions.hpp"
#include "scpp/runtime_error.hpp"
#include <functional>
#include <utility>

namespace scpp {

inline void require_task_permission(task_permissions permissions, task_policy policy) {
	if (!permits_task(permissions, policy)) {
		throw runtime_error("Task callback captures are not transferable", "invalid_task_callback_transfer", "scpp::tasks", "callback transfer");
	}
}

// Signature identity and transfer permission are independent. The compiler supplies
// capture permission when erasing a closure; ordinary callable copies retain it.
template <typename Signature> class callable_t;

template <typename Result, typename... Arguments>
class callable_t<Result(Arguments...)> final {
public:
	callable_t() = default;
	callable_t(std::function<Result(Arguments...)> function, bool transferable = false)
		: callable_t(std::move(function), transferable ? task_permissions::isolated_and_joined : task_permissions::none) {}
	callable_t(std::function<Result(Arguments...)> function, task_permissions permissions)
		: function_(std::move(function)), permissions_(permissions) {}

	callable_t(const callable_t&) = default;
	callable_t& operator=(const callable_t&) = default;
	callable_t(callable_t&& other) noexcept
		: function_(std::move(other.function_)), permissions_(std::exchange(other.permissions_, task_permissions::none)) {}
	callable_t& operator=(callable_t&& other) noexcept {
		if (this != &other) {
			function_ = std::move(other.function_);
			permissions_ = std::exchange(other.permissions_, task_permissions::none);
		}
		return *this;
	}

	explicit operator bool() const noexcept { return static_cast<bool>(function_); }
	task_permissions transfer_permissions() const noexcept { return function_ ? permissions_ : task_permissions::none; }
	bool transfer_allowed(task_policy policy = task_policy::isolated_transfer) const noexcept {
		return permits_task(transfer_permissions(), policy);
	}
	void require_transfer(task_policy policy = task_policy::isolated_transfer) const {
		require_task_permission(transfer_permissions(), policy);
	}
	Result operator()(Arguments... arguments) const {
		return function_(std::forward<Arguments>(arguments)...);
	}

private:
	std::function<Result(Arguments...)> function_;
	task_permissions permissions_ = task_permissions::none;
};

} // namespace scpp

namespace scpp::internal {
// Provider argument adaptation may change the native signature, but cannot erase
// permission established for the owned source callback.
template <typename Signature, typename Function> class transfer_callback;
template <typename Function, typename Result, typename... Arguments>
class transfer_callback<Result(Arguments...), Function> final {
public:
 transfer_callback(Function function, bool transferable)
  : transfer_callback(std::move(function), transferable ? task_permissions::isolated_and_joined : task_permissions::none) {}
 transfer_callback(Function function, task_permissions permissions)
  : function_(std::move(function)), permissions_(permissions) {}
 transfer_callback(const transfer_callback&) = default;
 transfer_callback& operator=(const transfer_callback&) = default;
 transfer_callback(transfer_callback&& other)
  : function_(std::move(other.function_)), permissions_(std::exchange(other.permissions_, task_permissions::none)) {}
 transfer_callback& operator=(transfer_callback&& other) {
  if (this != &other) {
   function_ = std::move(other.function_);
   permissions_ = std::exchange(other.permissions_, task_permissions::none);
  }
  return *this;
 }
 task_permissions transfer_permissions() const noexcept { return permissions_; }
 bool transfer_allowed(task_policy policy = task_policy::isolated_transfer) const noexcept {
  return permits_task(permissions_, policy);
 }
 void require_transfer(task_policy policy = task_policy::isolated_transfer) const {
  require_task_permission(permissions_, policy);
 }
 Result operator()(Arguments... arguments) const {
  return function_(std::forward<Arguments>(arguments)...);
 }
private:
 mutable Function function_;
 task_permissions permissions_;
};
template <typename Signature, typename Function>
transfer_callback<Signature, Function> carry_callback_transfer(Function function, task_permissions permissions) {
 return transfer_callback<Signature, Function>(std::move(function), permissions);
}
template <typename Signature, typename Function>
transfer_callback<Signature, Function> carry_callback_transfer(Function function, bool transferable) {
 return transfer_callback<Signature, Function>(std::move(function), transferable);
}
}
