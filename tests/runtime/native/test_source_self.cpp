#include "scpp/source_self.hpp"
#include <cassert>
#include <iostream>

struct left : virtual scpp::source_self_state { virtual ~left() = default; };
struct right : virtual scpp::source_self_state { virtual ~right() = default; };
struct record : left, right { int value = 7; };

template<class T> bool unbound(T* value) {
	try { auto owner = scpp::retain_source_self(value); }
	catch (const scpp::runtime_error&) { return true; }
	return false;
}

int main() {
	auto original = scpp::source_shared<record>();
	auto other = scpp::source_shared<record>();
	auto retained = scpp::retain_source_self(static_cast<right*>(original.get()));
	assert(!original.native_value().owner_before(retained.native_value()));
	assert(!retained.native_value().owner_before(original.native_value()));
	record copied(*original), moved(std::move(copied));
	assert(unbound(&copied) && unbound(&moved));
	*other = *original;
	auto assigned = scpp::retain_source_self(other.get());
	assert(!other.native_value().owner_before(assigned.native_value()));
	assert(!assigned.native_value().owner_before(other.native_value()));
	std::weak_ptr<record> weak = original.native_value();
	original.reset();
	assert(!weak.expired());
	retained.reset();
	assert(weak.expired());
	auto fresh_copy = scpp::source_shared<record>(moved);
	assert(!unbound(fresh_copy.get()));
	assert(unbound(&moved));
	std::cout << "source self identity, lifecycle and unbound guards passed; state bytes="
		<< sizeof(scpp::source_self_state) << '\n';
}
