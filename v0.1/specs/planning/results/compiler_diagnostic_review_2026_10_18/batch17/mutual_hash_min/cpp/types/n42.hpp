#pragma once
#include "types/n41.hpp"
#include "scpp/shared_p.hpp"
#include "scpp/string_t.hpp"
#include "scpp/hash_t.hpp"
struct record_B
{
public:
	scpp::hash_t<::scpp::shared_p<record_A>, scpp::string_t> field_as;
public:
};

