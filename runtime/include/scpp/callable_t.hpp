#pragma once

#include "scpp/runtime_error.hpp"
#include <functional>
#include <utility>

namespace scpp {

// Signature identity and transfer permission are independent. The compiler supplies
// capture permission when erasing a closure; ordinary callable copies retain it.
template <typename Signature> class callable_t;

template <typename Result, typename... Arguments>
class callable_t<Result(Arguments...)> final {
public:
	callable_t() = default;
	callable_t(std::function<Result(Arguments...)> function, bool transferable = false)
		: function_(std::move(function)), transferable_(transferable) {}

	callable_t(const callable_t&) = default;
	callable_t& operator=(const callable_t&) = default;
	callable_t(callable_t&& other) noexcept
		: function_(std::move(other.function_)), transferable_(std::exchange(other.transferable_, false)) {}
	callable_t& operator=(callable_t&& other) noexcept {
		if (this != &other) {
			function_ = std::move(other.function_);
			transferable_ = std::exchange(other.transferable_, false);
		}
		return *this;
	}

	explicit operator bool() const noexcept { return static_cast<bool>(function_); }
	bool transfer_allowed() const noexcept { return function_ && transferable_; }
	void require_transfer() const {
		if (!transfer_allowed()) {
			throw runtime_error("Task callback captures are not transferable", "invalid_task_callback_transfer", "scpp::tasks", "callback transfer");
		}
	}
	Result operator()(Arguments... arguments) const {
		return function_(std::forward<Arguments>(arguments)...);
	}

private:
	std::function<Result(Arguments...)> function_;
	bool transferable_ = false;
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
  : function_(std::move(function)), transferable_(transferable) {}
 bool transfer_allowed() const noexcept { return transferable_; }
 void require_transfer() const {
  if (!transferable_) {
   throw runtime_error("Task callback captures are not transferable", "invalid_task_callback_transfer", "scpp::tasks", "callback transfer");
  }
 }
 Result operator()(Arguments... arguments) const {
  return function_(std::forward<Arguments>(arguments)...);
 }
private:
 mutable Function function_;
 bool transferable_;
};
template <typename Signature, typename Function>
transfer_callback<Signature, Function> carry_callback_transfer(Function function, bool transferable) {
 return transfer_callback<Signature, Function>(std::move(function), transferable);
}
}
