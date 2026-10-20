#pragma once
#include "scpp/int_t.hpp"
#include "types/n425c42617365.hpp"
struct record_DUqChild : public record_BUqBase
{
public:
public:
	scpp::int_t<> implementation_DUqChild__method_f();
	virtual scpp::int_t<> dispatch_DUqChild__method_f();
scpp::int_t<> method_f();
public:
	scpp::int_t<> dispatch_BUqBase__method_f() override;
public:
	virtual ~record_DUqChild() noexcept = default;
	record_DUqChild() = default;
	record_DUqChild(const record_DUqChild&) = default;
	record_DUqChild(record_DUqChild&&) = default;
	record_DUqChild& operator=(const record_DUqChild&) = default;
	record_DUqChild& operator=(record_DUqChild&&) = default;
};

