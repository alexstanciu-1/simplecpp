#pragma once
#include "types/n42.hpp"
#include "scpp/shared_p.hpp"
#include "functional"
struct record_A
{
public:
	std::function<::scpp::shared_p<record_B>()> field_f;
public:
};

