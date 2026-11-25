#pragma once
#include "types/n42.hpp"
#include "scpp/shared_p.hpp"
#include "scpp/vector_t.hpp"
struct record_A
{
public:
	scpp::vector_t<::scpp::shared_p<record_B>> field_bs;
public:
};

