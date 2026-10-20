#pragma once
#include "scpp/lang/php.hpp"
#include "operators/arithmetic/boxed_numeric.hpp"
#include <cassert>

using ShiftInt = scpp::int_t<>;
template<class T> long shift_payload(const T& value) {
	return scpp::cast<ShiftInt>(value).native_value();
}
template<class W, class F> void observe_binary(F operation, long expected) {
	W left{ShiftInt{3}};
	ShiftInt events{0};
	auto result = operation(left, W{ShiftInt{1}}, events);
	assert(shift_payload(result) == expected);
	assert(shift_payload(left) == 99 && events.native_value() == 12);
	for (int count : {-1, 64, 65}) {
		left = W{ShiftInt{3}}; events = ShiftInt{0};
		bool caught = false;
		try { (void)operation(left, W{ShiftInt{count}}, events); }
		catch (const scpp::ValueError&) { caught = true; }
		assert(caught && shift_payload(left) == 99 && events.native_value() == 12);
	}
}
template<class W, class F> void observe_store(F operation, long expected) {
	W left{ShiftInt{3}};
	ShiftInt events{0};
	operation(left, W{ShiftInt{1}}, events);
	assert(shift_payload(left) == expected && events.native_value() == 2);
	for (int count : {-1, 64, 65}) {
		left = W{ShiftInt{3}}; events = ShiftInt{0};
		bool caught = false;
		try { operation(left, W{ShiftInt{count}}, events); }
		catch (const scpp::ValueError&) { caught = true; }
		// The authored RHS mutation survives; failed write-back must not restore
		// the old snapshot or overwrite the RHS effect with a computed value.
		assert(caught && shift_payload(left) == 99 && events.native_value() == 2);
	}
}

template<class F> void expect_guard(F operation) {
	bool caught = false;
	try { operation(); }
	catch (const scpp::ValueError&) { assert(false && "Count validation ran before operand admission"); }
	catch (const std::exception&) { caught = true; }
	assert(caught);
}
template<class W, class F, class S> void observe_guards(F operation, S store, W absent) {
	ShiftInt events{0};
	W left = absent;
	expect_guard([&] { (void)operation(left, W{ShiftInt{-1}}, events); });
	assert(events.native_value() == 1); // Left extraction prevents RHS evaluation.
	events = ShiftInt{0}; left = W{ShiftInt{3}};
	expect_guard([&] { (void)operation(left, absent, events); });
	assert(events.native_value() == 12 && shift_payload(left) == 99);
	events = ShiftInt{0}; left = absent;
	expect_guard([&] { store(left, W{ShiftInt{64}}, events); });
	assert(events.native_value() == 0);
	events = ShiftInt{0}; left = W{ShiftInt{3}};
	expect_guard([&] { store(left, absent, events); });
	assert(events.native_value() == 2 && shift_payload(left) == 99);
}
template<class W, class F> void observe_kinds(F operation) {
	for (W wrong : {W{scpp::bool_t{false}}, W{scpp::float_t{1.0}}, W{scpp::string_t{"1"}}, W{scpp::null}}) {
		ShiftInt events{0};
		W left = wrong;
		expect_guard([&] { (void)operation(left, W{ShiftInt{64}}, events); });
		assert(events.native_value() == 12 && shift_payload(left) == 99);
	}
}
