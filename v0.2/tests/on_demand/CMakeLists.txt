# Register exhaustive proofs only in builds that explicitly request them.
set(proofs
  shared_upcasts interface_access expression_static
  structs struct_properties struct_methods struct_constructors classes
  boxed_collection_methods boxed_clear boxed_literals boxed_fields boxed_index_mutation
  protected_static_data protected_static
  abstract_methods interfaces virtual_methods extends parent_methods capture_free_lambdas python_flavor
  application_vectors application_hashes application_vector_signatures
  application_collection_fields application_collection_references
  application_collection_iteration application_collection_methods)
if(async_frames)
  list(APPEND proofs async)
endif()
foreach(proof IN LISTS proofs)
  add_test(NAME compiler_${proof}_native_full COMMAND ${Python3_EXECUTABLE} -B
    ${PROJECT_SOURCE_DIR}/compiler/tests/${proof}_native.py)
  set_tests_properties(compiler_${proof}_native_full PROPERTIES LABELS on_demand ENVIRONMENT "XDEBUG_MODE=off")
endforeach()
if(mt_segments)
  add_test(NAME compiler_mt_segments_native_full COMMAND ${Python3_EXECUTABLE} -B
    ${PROJECT_SOURCE_DIR}/compiler/tests/mt_segments_native.py ${native_facts} $<TARGET_FILE:scpp_runtime> ${CMAKE_CXX_COMPILER})
  set_tests_properties(compiler_mt_segments_native_full PROPERTIES LABELS on_demand)
endif()

get_property(fixture_tests GLOBAL PROPERTY scpp_full_fixture_tests)
foreach(feature IN LISTS fixture_tests)
  add_test(NAME compiler_${feature}_native_full COMMAND ${Python3_EXECUTABLE} -B
    ${PROJECT_SOURCE_DIR}/compiler/tests/strings_native.py --fixture ${feature}.php)
  set_tests_properties(compiler_${feature}_native_full PROPERTIES LABELS on_demand ENVIRONMENT "XDEBUG_MODE=off")
endforeach()

foreach(mode items chains)
  add_test(NAME compiler_python_${mode}_full COMMAND ${Python3_EXECUTABLE} -B
    ${PROJECT_SOURCE_DIR}/compiler/tests/python_flavor_native.py --${mode})
  set_tests_properties(compiler_python_${mode}_full PROPERTIES LABELS on_demand ENVIRONMENT "XDEBUG_MODE=off")
endforeach()

foreach(kind list keyed)
  add_test(NAME ${kind}_initialization_providers_full COMMAND ${Python3_EXECUTABLE} -B
    ${PROJECT_SOURCE_DIR}/tools/api_generator/tests/${kind}_initialization.py --out ${CMAKE_CURRENT_BINARY_DIR}/${kind}-providers)
  set_tests_properties(${kind}_initialization_providers_full PROPERTIES LABELS on_demand)
endforeach()

add_test(NAME compiler_last_use_transfer_full COMMAND ${Python3_EXECUTABLE} -B
  ${PROJECT_SOURCE_DIR}/compiler/tests/last_use_transfer.py)
add_test(NAME compiler_arithmetic_argument_order_full COMMAND ${Python3_EXECUTABLE} -B
  ${PROJECT_SOURCE_DIR}/compiler/tests/arithmetic_argument_order.py --cxx ${CMAKE_CXX_COMPILER})
set_tests_properties(compiler_last_use_transfer_full compiler_arithmetic_argument_order_full PROPERTIES LABELS on_demand)
