# Register exhaustive proofs only in builds that explicitly request them.
set(proofs
  static_callable_properties erased_reference_returns owned_callable_fields
  static_owned_callables instance_owned_callables
  boxed_scalars callable_collections callable_reference_inputs
  staged_ownership source_loader_callbacks
  runtime_operand_projection undefined_key collection_bootstrap
  input_records incremental_executable weak shared_references reference_parameters
  bootstrap_donor_verification named_callbacks failure_expressions readonly
  fixed_array ternary
  switch required_fields integer_radix
  bootstrap_runtime_lookup finally exceptions
  collection_references collection_append named_construction struct_constants shared_lookup
  coalesce coalesce_assignment
  hash_find match match_conditions
  inline_adapters named_arguments app_namespace
  hash_contains called_methods object_access ast_parent_lifetime
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

add_test(NAME compiler_native_build_full COMMAND ${Python3_EXECUTABLE} -B
  ${PROJECT_SOURCE_DIR}/compiler/tests/native_build.py)
set_tests_properties(compiler_native_build_full PROPERTIES LABELS on_demand ENVIRONMENT "XDEBUG_MODE=off")

add_test(NAME compiler_keyed_initialization_native_full COMMAND ${Python3_EXECUTABLE} -B
  ${PROJECT_SOURCE_DIR}/compiler/tests/keyed_initialization_native.py --out ${CMAKE_CURRENT_BINARY_DIR}/keyed-native)
add_test(NAME boxed_publication_full COMMAND ${Python3_EXECUTABLE} -B ${PROJECT_SOURCE_DIR}/tests/boxed/publication.py
  --generator ${api_tooling} --source ${PROJECT_SOURCE_DIR} --facts ${native_facts}
  --context ${native_context} --cxx ${CMAKE_CXX_COMPILER})
set_tests_properties(compiler_keyed_initialization_native_full boxed_publication_full PROPERTIES LABELS on_demand)

add_test(NAME compiler_keyed_initialization_incremental_full COMMAND ${Python3_EXECUTABLE} -B
  ${PROJECT_SOURCE_DIR}/compiler/tests/keyed_initialization_incremental.py --out ${CMAKE_CURRENT_BINARY_DIR}/keyed-incremental)
add_test(NAME compiler_destination_append_full COMMAND ${Python3_EXECUTABLE} -B
  ${PROJECT_SOURCE_DIR}/compiler/tests/destination_append.py)
set_tests_properties(compiler_keyed_initialization_incremental_full compiler_destination_append_full PROPERTIES LABELS on_demand)

add_test(NAME compiler_incremental_equivalence_full COMMAND ${Python3_EXECUTABLE} -B
  ${PROJECT_SOURCE_DIR}/compiler/tests/retention/equivalence.py)
set_tests_properties(compiler_incremental_equivalence_full PROPERTIES LABELS on_demand ENVIRONMENT "PHP_EXECUTABLE=${PHP_EXECUTABLE}")
add_test(NAME compiler_list_initialization_native_full COMMAND ${Python3_EXECUTABLE} -B
  ${PROJECT_SOURCE_DIR}/compiler/tests/list_initialization_native.py --out ${CMAKE_CURRENT_BINARY_DIR}/list-native)
add_test(NAME json_publication_full COMMAND ${Python3_EXECUTABLE} -B ${PROJECT_SOURCE_DIR}/tests/json/publication.py
  --generator ${api_tooling} --source ${PROJECT_SOURCE_DIR} --facts ${native_facts}
  --context ${native_context} --cxx ${CMAKE_CXX_COMPILER})
set_tests_properties(compiler_list_initialization_native_full json_publication_full PROPERTIES LABELS on_demand)

add_test(NAME compiler_collection_transfer_full COMMAND ${Python3_EXECUTABLE} -B
  ${PROJECT_SOURCE_DIR}/compiler/tests/collection_transfer.py)
add_test(NAME datetime_publication_full COMMAND ${Python3_EXECUTABLE} -B
  ${PROJECT_SOURCE_DIR}/tests/datetime/publication.py --generator ${generator_launcher}
  --metadata ${runtime_metadata} --publication ${PROJECT_SOURCE_DIR}/tests/datetime/publication.json
  --cxx ${CMAKE_CXX_COMPILER} "--flags=${CMAKE_CXX_FLAGS}" --source ${PROJECT_SOURCE_DIR}
  --work ${CMAKE_CURRENT_BINARY_DIR}/datetime-publication-full)
set_tests_properties(compiler_collection_transfer_full datetime_publication_full PROPERTIES LABELS on_demand)

add_test(NAME compiler_scoped_byte_view_native_full COMMAND ${Python3_EXECUTABLE} -B
  ${PROJECT_SOURCE_DIR}/compiler/tests/scoped_byte_view_native.py ${native_facts} ${native_context})
add_test(NAME native_probe_contract_full COMMAND ${Python3_EXECUTABLE} -B
  ${PROJECT_SOURCE_DIR}/tools/layout_probe/test_integration.py --generator ${api_tooling} --cxx ${CMAKE_CXX_COMPILER}
  --flag=-stdlib=libc++ --flag=--ld-path=${SCPP_MOLD_LINKER} --work ${CMAKE_CURRENT_BINARY_DIR}/probe-contract-full)
set_tests_properties(compiler_scoped_byte_view_native_full native_probe_contract_full PROPERTIES LABELS on_demand)

add_test(NAME compiler_boxed_collection_coverage_full COMMAND ${Python3_EXECUTABLE} -B
  ${PROJECT_SOURCE_DIR}/compiler/tests/boxed_collection_coverage.py)
add_test(NAME native_qualification_cache_full COMMAND ${Python3_EXECUTABLE} -B
  ${PROJECT_SOURCE_DIR}/tools/layout_probe/test_qualification.py --cxx ${CMAKE_CXX_COMPILER})
add_test(NAME generator_parity_full COMMAND ${Python3_EXECUTABLE} -B
  ${PROJECT_SOURCE_DIR}/tests/generator_parity/parity.py --reference ${generator_launcher}
  --source ${PROJECT_SOURCE_DIR} --work ${CMAKE_CURRENT_BINARY_DIR}/generator-parity-full)
set_tests_properties(compiler_boxed_collection_coverage_full native_qualification_cache_full generator_parity_full PROPERTIES LABELS on_demand)

add_test(NAME concrete_templates_full COMMAND ${Python3_EXECUTABLE} -B
  ${PROJECT_SOURCE_DIR}/tools/api_generator/tests/concrete.py)
set_tests_properties(concrete_templates_full PROPERTIES LABELS on_demand)

add_test(NAME compiler_scalar_fact_edits_full COMMAND ${Python3_EXECUTABLE} -B
  ${PROJECT_SOURCE_DIR}/compiler/tests/scalar_fact_edits.py)
add_test(NAME hash_provider_exports_full COMMAND ${Python3_EXECUTABLE} -B
  ${PROJECT_SOURCE_DIR}/tools/api_generator/tests/hash_providers.py)
set_tests_properties(compiler_scalar_fact_edits_full hash_provider_exports_full PROPERTIES LABELS on_demand)

add_test(NAME compiler_keyed_initialization_ownership_full COMMAND ${Python3_EXECUTABLE} -B
  ${PROJECT_SOURCE_DIR}/compiler/tests/keyed_initialization_ownership.py --out ${CMAKE_CURRENT_BINARY_DIR}/keyed-ownership-full)
add_test(NAME compiler_frontend_service_full COMMAND ${CMAKE_COMMAND} -E env PHP_EXECUTABLE=${PHP_EXECUTABLE}
  ${Python3_EXECUTABLE} -B ${PROJECT_SOURCE_DIR}/compiler/tests/frontend_service.py)
set_tests_properties(compiler_keyed_initialization_ownership_full compiler_frontend_service_full PROPERTIES LABELS on_demand)

add_test(NAME runtime_submodule_providers_full COMMAND ${Python3_EXECUTABLE} -B
  ${PROJECT_SOURCE_DIR}/tools/api_generator/tests/submodules.py)
add_test(NAME scalar_contract_full COMMAND ${Python3_EXECUTABLE} -B
  ${PROJECT_SOURCE_DIR}/tests/generator_parity/scalar_parity.py
  --generator ${api_tooling} --facts ${native_facts} --context ${native_context}
  --work ${CMAKE_CURRENT_BINARY_DIR}/scalar-contract-full --emit
  --cxx ${CMAKE_CXX_COMPILER} --flag=-stdlib=libc++)
set_tests_properties(runtime_submodule_providers_full scalar_contract_full PROPERTIES LABELS on_demand)

add_test(NAME runtime_family_contract_full COMMAND ${Python3_EXECUTABLE} -B
  ${PROJECT_SOURCE_DIR}/tests/generator_parity/runtime_parity.py
  --generator ${api_tooling} --facts ${native_facts} --context ${native_context}
  --work ${CMAKE_CURRENT_BINARY_DIR}/runtime-family-contract-full)
add_test(NAME vector_provider_exports_full COMMAND ${Python3_EXECUTABLE} -B
  ${PROJECT_SOURCE_DIR}/tools/api_generator/tests/vector_providers.py)
set_tests_properties(runtime_family_contract_full vector_provider_exports_full PROPERTIES LABELS on_demand)
