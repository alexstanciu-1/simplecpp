#pragma once
#include "scpp/shared_p.hpp"
#include "scpp/memory.hpp"
#include "scpp/int_t.hpp"

namespace handle_proof {
class ticket final {
public:
	scpp::int_t<> value;
	explicit ticket(scpp::int_t<> input) : value(input) {}
	ticket(const ticket&) = delete;
	ticket& operator=(const ticket&) = delete;
	scpp::int_t<> read() const { return value; }
	void change(scpp::int_t<> input) { value = input; }
	template<class T> T echo_value(T input) { return input; }
	scpp::int_t<> read_other(const scpp::shared_p<ticket>& other) { return other->value; }
};
inline scpp::shared_p<ticket> open_ticket(scpp::int_t<> value) {
	return scpp::shared<ticket>(value);
}
inline scpp::int_t<> ticket_read(const scpp::shared_p<ticket>& handle) {
	return handle->value;
}
inline void ticket_write(scpp::shared_p<ticket> handle, scpp::int_t<> value) {
	handle->value = value;
}
inline void ticket_clear(scpp::shared_p<ticket>& handle) {
	handle = scpp::shared_p<ticket>();
}
}
