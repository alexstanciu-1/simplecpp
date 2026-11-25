#pragma once
#include "types/n41.hpp"
struct record_B : public record_A
{
public:
public:
	virtual ~record_B() noexcept = default;
	record_B() = default;
	record_B(const record_B&) = default;
	record_B(record_B&&) = default;
	record_B& operator=(const record_B&) = default;
	record_B& operator=(record_B&&) = default;
};

