#pragma once
#include "types/n43.hpp"
#include "scpp/shared_p.hpp"
#include "scpp/vector_t.hpp"
struct record_B
{
public:
	scpp::vector_t<::scpp::shared_p<record_C>> field_cs;
public:
};

