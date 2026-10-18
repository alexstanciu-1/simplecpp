#pragma once
struct record_A
{
public:
private:
	~record_A() noexcept;
public:
	record_A() = default;
	record_A(const record_A&) = default;
	record_A(record_A&&) = default;
	record_A& operator=(const record_A&) = default;
	record_A& operator=(record_A&&) = default;
};

