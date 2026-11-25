#pragma once
#include "scpp/async_core.hpp"
#include "scpp/bool_t.hpp"
namespace async_probe {
inline scpp::async_core::scheduler* owner = nullptr;
inline void mark() { owner = scpp::async_core::scheduler::current(); }
inline scpp::bool_t same() {
	return scpp::bool_t(owner != nullptr && owner == scpp::async_core::scheduler::current());
}
}
