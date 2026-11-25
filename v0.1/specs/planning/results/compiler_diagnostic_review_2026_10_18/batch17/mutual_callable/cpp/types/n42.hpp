#pragma once
#include "types/n41.hpp"
#include "scpp/shared_p.hpp"
#include "functional"
struct record_B
{
public:
	std::function<::scpp::shared_p<record_A>()> field_f;
public:
};

