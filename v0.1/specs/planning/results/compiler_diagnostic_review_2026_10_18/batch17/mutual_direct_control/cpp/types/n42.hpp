#pragma once
#include "scpp/shared_p.hpp"
struct record_A;
struct record_B
{
public:
	::scpp::shared_p<record_A> field_a;
public:
};

