#pragma once
#include "scpp/int_t.hpp"
struct record_BUqBase
{
public:
public:
	virtual scpp::int_t<> dispatch_BUqBase__method_f() = 0;
scpp::int_t<> method_f();
public:
	virtual ~record_BUqBase() noexcept = default;
	record_BUqBase() = default;
	record_BUqBase(const record_BUqBase&) = default;
	record_BUqBase(record_BUqBase&&) = default;
	record_BUqBase& operator=(const record_BUqBase&) = default;
	record_BUqBase& operator=(record_BUqBase&&) = default;
};

