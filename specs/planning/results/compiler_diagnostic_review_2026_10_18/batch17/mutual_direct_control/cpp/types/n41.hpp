#pragma once
#include "scpp/shared_p.hpp"
struct record_B;
struct record_A
{
public:
	::scpp::shared_p<record_B> field_b;
public:
};

