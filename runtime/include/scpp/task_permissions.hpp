#pragma once

namespace scpp {

// API requirements and environment permissions are independent. Owned captures
// support both policies; shared captures support only the joined policy.
enum class task_policy { isolated_transfer, joined_shared };
enum class task_permissions : unsigned { none = 0, joined_shared = 2, isolated_and_joined = 3 };

constexpr task_permissions intersect_task_permissions(task_permissions left, task_permissions right) noexcept {
	return static_cast<task_permissions>(static_cast<unsigned>(left) & static_cast<unsigned>(right));
}
constexpr bool permits_task(task_permissions permissions, task_policy policy) noexcept {
	switch (policy) {
		case task_policy::isolated_transfer: return (static_cast<unsigned>(permissions) & 1u) != 0;
		case task_policy::joined_shared: return (static_cast<unsigned>(permissions) & 2u) != 0;
	}
	return false;
}

} // namespace scpp
