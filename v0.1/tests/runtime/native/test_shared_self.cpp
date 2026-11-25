#include "scpp/shared_self.hpp"
#include "scpp/memory.hpp"
#include "scpp/nullable.hpp"
#include <cassert>

struct left : virtual scpp::shared_self { virtual ~left() = default; };
struct right : virtual scpp::shared_self { virtual ~right() = default; };
struct record : left, right {
	static inline int destroyed = 0;
	~record() { ++destroyed; }
};

int main() {
	static_assert(std::is_convertible_v<scpp::shared_p<record>, scpp::nullable<scpp::shared_p<left>>>);
	static_assert(!std::is_convertible_v<scpp::shared_p<left>, scpp::nullable<scpp::shared_p<record>>>);
	auto owner = scpp::create<record>();
	auto *address = owner.get();
	auto retained = owner->retain_self(address);
	auto right_handle = owner->retain_self(static_cast<right *>(address));
	std::weak_ptr<record> weak = owner.native_value();
	assert(!owner.native_value().owner_before(right_handle.native_value()));
	assert(!right_handle.native_value().owner_before(owner.native_value()));
	owner.reset();
	assert(!weak.expired());
	assert(retained.get() == address);
	retained.reset();
	assert(!weak.expired());
	right_handle.reset();
	assert(weak.expired() && record::destroyed == 1);
	left unmanaged;
	bool rejected = false;
	try { auto invalid = unmanaged.retain_self(&unmanaged); }
	catch (const scpp::runtime_error &) { rejected = true; }
	assert(rejected);
}
