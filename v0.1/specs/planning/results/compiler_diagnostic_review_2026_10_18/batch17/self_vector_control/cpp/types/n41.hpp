#pragma once
#include "scpp/shared_p.hpp"
#include "scpp/vector_t.hpp"
struct record_A
{
public:
	scpp::vector_t<::scpp::shared_p<record_A>> field_as;
public:
};

