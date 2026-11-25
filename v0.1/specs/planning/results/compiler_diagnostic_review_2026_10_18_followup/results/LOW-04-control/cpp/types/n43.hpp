#pragma once
struct record_C
{
public:
public:
	~record_C() noexcept;
public:
	record_C() = default;
	record_C(const record_C&) = default;
	record_C(record_C&&) = default;
	record_C& operator=(const record_C&) = default;
	record_C& operator=(record_C&&) = default;
};

