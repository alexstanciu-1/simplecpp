#pragma once
#include "types/n42.hpp"
#include "scpp/shared_p.hpp"
#include "scpp/string_t.hpp"
#include "scpp/hash_t.hpp"
struct record_A
{
public:
	scpp::hash_t<::scpp::shared_p<record_B>, scpp::string_t> field_bs;
public:
};

