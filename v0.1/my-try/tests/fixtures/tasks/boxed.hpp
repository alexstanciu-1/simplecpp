#pragma once
#include "modules/tasks/tasks.hpp"
#include "scpp/index_read.hpp"
namespace task_boxed_probe {
template<class Box> Box input(bool nested) {
 auto result=scpp::tasks::detail::boxed_task_family<Box>::empty();
 scpp::tasks::detail::boxed_task_family<Box>::output(result).set(scpp::mixed_t(scpp::int_t<>(0)),Box(scpp::int_t<>(3)));
 if(!nested)return result;
 auto outer=scpp::tasks::detail::boxed_task_family<Box>::empty();
 scpp::tasks::detail::boxed_task_family<Box>::output(outer).set(scpp::mixed_t(scpp::int_t<>(0)),result);
 return outer;
}
inline void dynamic_mutate(scpp::dynamic_t<> value){value.get_table().set(scpp::mixed_t(scpp::int_t<>(0)),scpp::dynamic_t<>(scpp::int_t<>(9)));}
inline scpp::dynamic_t<> dynamic_first(scpp::dynamic_t<> value){return scpp::null_index_read(value,scpp::mixed_t(scpp::int_t<>(0)));}
inline scpp::mixed_t mixed_input(){return input<scpp::mixed_t>(false);}
inline scpp::dynamic_t<> dynamic_input(){return input<scpp::dynamic_t<>>(false);}
inline scpp::mixed_t mixed_nested(){return input<scpp::mixed_t>(true);}
inline scpp::dynamic_t<> dynamic_nested(){return input<scpp::dynamic_t<>>(true);}
}
