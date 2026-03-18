#pragma once

#include <memory>
#include <type_traits>
#include <utility>

namespace scpp {

	/**
	 * Simple C++ managed shared-ownership wrapper.
	 *
	 * Notes:
	 * - this is a minimal proposal intended to support create<T>()
	 * - public surface is intentionally small
	 * - runtime code may expand this later with comparison helpers, null support, etc.
	 */
	template <typename T>
	class shared_p {
	private:
		std::shared_ptr<T> m_ptr;

	public:
		/**
		 * Default null / empty state.
		 */
		shared_p() noexcept = default;

		/**
		 * Internal bridge constructor from std::shared_ptr.
		 *
		 * This is intentionally explicit so generated code does not
		 * accidentally rely on implicit C++ pointer conversions.
		 */
		explicit shared_p(std::shared_ptr<T> ptr) noexcept
			: m_ptr(std::move(ptr)) {
		}

		/**
		 * Controlled raw accessor for runtime internals.
		 *
		 * Generated code should not use this as a general escape hatch.
		 */
		[[nodiscard]] const std::shared_ptr<T>& raw_ptr() const noexcept {
			return m_ptr;
		}

		/**
		 * Access pointee.
		 *
		 * These operators are intentionally provided because generated code
		 * will likely need normal object/member access after create<T>().
		 */
		[[nodiscard]] T& operator*() const noexcept {
			return *m_ptr;
		}

		[[nodiscard]] T* operator->() const noexcept {
			return m_ptr.get();
		}

		/**
		 * Null / empty check helper.
		 */
		[[nodiscard]] bool is_null() const noexcept {
			return !m_ptr;
		}

		/**
		 * Identity comparison.
		 */
		friend bool operator==(const shared_p& lhs, const shared_p& rhs) noexcept {
			return lhs.m_ptr == rhs.m_ptr;
		}

		friend bool operator!=(const shared_p& lhs, const shared_p& rhs) noexcept {
			return lhs.m_ptr != rhs.m_ptr;
		}
	};

	/**
	 * Managed object creation helper.
	 *
	 * Language-level source constructs such as:
	 *
	 *     auto x = new MyClass();
	 *
	 * are lowered by the S2S compiler into:
	 *
	 *     auto x = create<MyClass>(...);
	 *
	 * This function is the semantic object-allocation entry point for
	 * generated code. It must not expose raw C++ allocation semantics.
	 */
	template <typename T, typename... Args>
	[[nodiscard]] shared_p<T> create(Args&&... args) {
		static_assert(!std::is_reference_v<T>, "create<T> does not accept reference types");
		static_assert(!std::is_const_v<T>, "create<T> should be used with non-const object types");
		static_assert(!std::is_volatile_v<T>, "create<T> should be used with non-volatile object types");

		return shared_p<T>(
			std::make_shared<T>(std::forward<Args>(args)...)
		);
	}

} /* namespace scpp */




