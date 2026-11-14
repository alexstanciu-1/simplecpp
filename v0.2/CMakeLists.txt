cmake_minimum_required(VERSION 3.21)
project(scpp_v02_foundation LANGUAGES CXX)

if(NOT CMAKE_GENERATOR STREQUAL "Ninja")
  message(FATAL_ERROR "v0.2 uses Ninja. Configure with cmake --preset default.")
endif()
if(NOT CMAKE_CXX_COMPILER_ID MATCHES "Clang")
  message(FATAL_ERROR "v0.2 uses Clang. Configure with cmake --preset default.")
endif()
find_program(SCPP_MOLD_LINKER NAMES ld.mold mold REQUIRED)
add_link_options("--ld-path=${SCPP_MOLD_LINKER}")
message(STATUS "v0.2 linker: ${SCPP_MOLD_LINKER}; default build preset: 8 jobs")

# PHP prepares and renders runtime APIs; the C++ probe supplies target facts.
find_package(Python3 3.10 REQUIRED COMPONENTS Interpreter)
unset(PHP_MT_EXECUTABLE CACHE)
if(PHP_EXECUTABLE STREQUAL "${CMAKE_CURRENT_SOURCE_DIR}/tools/php-mt")
  unset(PHP_EXECUTABLE CACHE)
endif()
find_program(PHP_EXECUTABLE NAMES php php8.5 REQUIRED)
execute_process(COMMAND ${PHP_EXECUTABLE} -r "exit(PHP_VERSION_ID >= 80500 && PHP_INT_SIZE === 8 ? 0 : 1);"
  RESULT_VARIABLE php_supported)
if(NOT php_supported EQUAL 0)
  message(FATAL_ERROR "v0.2 tools require 64-bit PHP 8.5+; set PHP_EXECUTABLE to a supported PHP binary")
endif()
set(php_generator_source ${CMAKE_CURRENT_SOURCE_DIR}/tools/api_generator/generate.php)
# Track PHP renderers and both template languages recursively across the owning-area folders.
file(GLOB_RECURSE generator_sources CONFIGURE_DEPENDS
  ${CMAKE_CURRENT_SOURCE_DIR}/tools/api_generator/php/*.php
  ${CMAKE_CURRENT_SOURCE_DIR}/tools/api_generator/templates/*.cpp.tpl
  ${CMAKE_CURRENT_SOURCE_DIR}/tools/api_generator/templates/*.php.tpl)
list(APPEND generator_sources ${php_generator_source})
set(api_tooling ${CMAKE_CURRENT_BINARY_DIR}/scpp-api-tooling)
configure_file(tools/api_generator/tooling.py.in ${api_tooling} @ONLY)
file(CHMOD ${api_tooling} PERMISSIONS OWNER_READ OWNER_WRITE OWNER_EXECUTE GROUP_READ GROUP_EXECUTE WORLD_READ WORLD_EXECUTE)
add_custom_target(scpp_api_tooling DEPENDS ${generator_sources} ${api_tooling})

# Always revalidate compiler/header context; the probe preserves unchanged files.
string(TOUPPER "${CMAKE_BUILD_TYPE}" probe_build_type)
separate_arguments(probe_flags NATIVE_COMMAND "${CMAKE_CXX_FLAGS} ${CMAKE_CXX_FLAGS_${probe_build_type}}")
set(probe_flag_args)
foreach(flag IN LISTS probe_flags)
  list(APPEND probe_flag_args "--flag=${flag}")
endforeach()
set(native_facts ${CMAKE_CURRENT_BINARY_DIR}/native-layout/facts.json)
set(native_context ${CMAKE_CURRENT_BINARY_DIR}/native-layout/context.json)
add_custom_target(scpp_layout_facts
  COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/tools/layout_probe/probe.py
    --cxx ${CMAKE_CXX_COMPILER} ${probe_flag_args} "--flag=--ld-path=${SCPP_MOLD_LINKER}"
    --cross-compiling ${CMAKE_CROSSCOMPILING} --out ${CMAKE_CURRENT_BINARY_DIR}/native-layout
  COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/tools/layout_probe/qualification.py
    --cxx ${CMAKE_CXX_COMPILER} ${probe_flag_args} --flag=-std=c++23 "--link-flag=--ld-path=${SCPP_MOLD_LINKER}"
    --cache ${CMAKE_CURRENT_BINARY_DIR}/native-qualification
  BYPRODUCTS ${native_facts} ${native_context}
  VERBATIM)
set(generator_launcher ${CMAKE_CURRENT_BINARY_DIR}/scpp_api_generator)
configure_file(tools/api_generator/launcher.py.in ${generator_launcher} @ONLY)
file(CHMOD ${generator_launcher} PERMISSIONS OWNER_READ OWNER_WRITE OWNER_EXECUTE GROUP_READ GROUP_EXECUTE WORLD_READ WORLD_EXECUTE)
add_custom_target(scpp_api_generator DEPENDS scpp_api_tooling scpp_layout_facts)

set(runtime_metadata ${CMAKE_CURRENT_SOURCE_DIR}/runtime/metadata/runtime.json)
include(cmake/RuntimePublication.cmake)
include(cmake/ProviderOutputs.cmake)
include(cmake/Datetime.cmake)
include(cmake/Json.cmake)
scpp_provider_outputs(runtime_outputs ${runtime_generated} ${runtime_publication})
list(APPEND runtime_outputs
  ${runtime_generated}/public/scpp/arithmetic.hpp
  ${runtime_generated}/public/scpp/owner_access.hpp
  ${runtime_generated}/public/scpp/entry.hpp
  ${runtime_generated}/private/output_provider.hpp
  ${runtime_generated}/public/scpp/numeric.hpp
  ${runtime_generated}/public/scpp/short_names.hpp
  ${runtime_generated}/src/runtime.cpp
  ${runtime_generated}/prepared.json
  ${runtime_generated}/inventory.json)
string(JSON hash_publication_type ERROR_VARIABLE hash_publication_error TYPE "${runtime_profile}" hashes)
if(hash_publication_type STREQUAL "OBJECT")
  list(APPEND runtime_outputs ${runtime_generated}/src/hash.cpp)
endif()
if(SCPP_ENABLE_DATETIME)
  list(APPEND runtime_outputs ${runtime_generated}/public/scpp/datetime.hpp ${runtime_generated}/src/datetime.cpp)
endif()
string(JSON boxed_publication_type ERROR_VARIABLE boxed_publication_error TYPE "${runtime_profile}" boxed)
if(boxed_publication_type STREQUAL "OBJECT")
  list(APPEND runtime_outputs ${runtime_generated}/public/scpp/boxed.hpp ${runtime_generated}/src/boxed.cpp)
  string(JSON boxed_dispatch ERROR_VARIABLE boxed_dispatch_error GET "${runtime_profile}" boxed scalar_dispatch)
  if(boxed_dispatch)
    list(APPEND runtime_outputs ${runtime_generated}/public/scpp/boxed_kind.hpp ${runtime_generated}/public/scpp/boxed_key.hpp)
  endif()
endif()
if(SCPP_ENABLE_JSON)
  list(APPEND runtime_outputs
    ${runtime_generated}/public/scpp/json.hpp ${runtime_generated}/src/json.cpp)
endif()
# This source-local publication may have been written by another build profile.
# Check its contents on every build; unchanged output keeps its timestamps.
add_custom_target(scpp_runtime_publication
  COMMAND ${CMAKE_COMMAND}
    -DGENERATOR=${generator_launcher} -DMETADATA=${runtime_metadata}
    -DPUBLICATION=${runtime_publication} -DOUTPUT=${runtime_generated}
    -P ${CMAKE_CURRENT_SOURCE_DIR}/cmake/PublishRuntime.cmake
  BYPRODUCTS ${runtime_outputs}
  DEPENDS scpp_api_generator ${generator_sources} ${api_tooling} ${native_facts} ${native_context} ${generator_launcher} ${runtime_metadata} ${runtime_publication}
  VERBATIM)
add_library(scpp_runtime STATIC ${runtime_generated}/src/runtime.cpp runtime/src/output/output.cpp runtime/src/entry/entry.cpp runtime/src/ownership/owner_access.cpp runtime/src/numeric/arithmetic.cpp)
if(hash_publication_type STREQUAL "OBJECT")
  target_sources(scpp_runtime PRIVATE ${runtime_generated}/src/hash.cpp)
endif()
add_dependencies(scpp_runtime scpp_runtime_publication)
# The PHP compiler consumes the same validated publication demand as the runtime.
# Runtime-only source exports intentionally omit the compiler tree.
if(EXISTS "${CMAKE_CURRENT_SOURCE_DIR}/compiler/boot.php")
  set(compiler_generated ${CMAKE_CURRENT_SOURCE_DIR}/compiler/generated)
  add_custom_target(scpp_compiler_publication ALL
    COMMAND ${CMAKE_COMMAND}
      -DPHP=${PHP_EXECUTABLE} -DGENERATOR=${php_generator_source}
      -DMETADATA=${runtime_metadata} -DPUBLICATION=${runtime_publication} -DFACTS=${native_facts} -DCONTEXT=${native_context}
      -DOUTPUT=${compiler_generated}
      -P ${CMAKE_CURRENT_SOURCE_DIR}/cmake/PublishCompiler.cmake
    BYPRODUCTS ${compiler_generated}/types/int8.php ${compiler_generated}/types/int16.php ${compiler_generated}/types/int32.php ${compiler_generated}/types/uint16.php ${compiler_generated}/types/uint32.php ${compiler_generated}/types/int64.php ${compiler_generated}/types/null.php ${compiler_generated}/types/bool.php ${compiler_generated}/types/uint8.php ${compiler_generated}/types/float64.php ${compiler_generated}/types/string.php ${compiler_generated}/types/registry.php ${compiler_generated}/types/vectors.php ${compiler_generated}/types/carriers.php ${compiler_generated}/types/boxed.php ${compiler_generated}/types/hashes.php ${compiler_generated}/bindings/entry.php ${compiler_generated}/inventory.json
    DEPENDS scpp_layout_facts ${generator_sources} ${native_facts} ${native_context} ${runtime_metadata} ${runtime_publication}
    VERBATIM)
  add_dependencies(scpp_runtime scpp_compiler_publication)
endif()
target_include_directories(scpp_runtime
  PUBLIC ${runtime_generated}/public
  PRIVATE ${runtime_generated}/private ${CMAKE_CURRENT_SOURCE_DIR}/runtime/src/numeric ${CMAKE_CURRENT_SOURCE_DIR}/runtime/src/sequence ${CMAKE_CURRENT_SOURCE_DIR}/runtime/src/hash)
target_compile_features(scpp_runtime PUBLIC cxx_std_23)

if(SCPP_ENABLE_DATETIME)
  scpp_add_datetime(scpp_datetime scpp_runtime ${runtime_generated})
  add_executable(scpp_datetime_demo examples/datetime_demo.cpp)
  target_link_libraries(scpp_datetime_demo PRIVATE scpp_datetime)
endif()

# Private storage shared by generated boxed APIs and module implementations.
add_library(scpp_packed_objects OBJECT runtime/src/hash/packed_key.cpp)
target_compile_features(scpp_packed_objects PRIVATE cxx_std_23)
add_library(scpp_packed_storage STATIC $<TARGET_OBJECTS:scpp_packed_objects>)
target_compile_features(scpp_packed_storage PUBLIC cxx_std_23)
add_library(scpp_boxed_objects OBJECT runtime/src/boxed/boxed_value.cpp runtime/src/boxed/boxed_conversion.cpp)
target_include_directories(scpp_boxed_objects PRIVATE runtime/src/hash)
target_compile_features(scpp_boxed_objects PRIVATE cxx_std_23)
add_library(scpp_boxed_storage STATIC $<TARGET_OBJECTS:scpp_boxed_objects>)
target_link_libraries(scpp_boxed_storage PRIVATE scpp_packed_storage)
target_compile_features(scpp_boxed_storage PUBLIC cxx_std_23)

# Application builders link one prebuilt runtime archive. Reuse the same private objects in proof archives.
if(boxed_publication_type STREQUAL "OBJECT")
  target_sources(scpp_runtime PRIVATE ${runtime_generated}/src/boxed.cpp
    $<TARGET_OBJECTS:scpp_boxed_objects> $<TARGET_OBJECTS:scpp_packed_objects>)
  target_include_directories(scpp_runtime PRIVATE
    ${CMAKE_CURRENT_SOURCE_DIR}/runtime/src/boxed ${CMAKE_CURRENT_SOURCE_DIR}/runtime/src/ownership)
endif()
if(SCPP_ENABLE_JSON)
  scpp_add_json(scpp_json scpp_runtime ${runtime_generated})
  add_executable(scpp_json_demo examples/json_demo.cpp)
  target_link_libraries(scpp_json_demo PRIVATE scpp_json)
endif()

add_executable(scpp_runtime_demo examples/runtime_demo.cpp)
target_link_libraries(scpp_runtime_demo PRIVATE scpp_runtime)

include(CTest)
if(BUILD_TESTING)
  # Wide-number regression consumers have their own explicit numeric publication.
  # Its generated directory is a sibling, never a child of another publication.
  set(numeric_proof_generated ${CMAKE_CURRENT_BINARY_DIR}/generated-numeric-proof)
  set(numeric_proof_publication ${CMAKE_CURRENT_SOURCE_DIR}/tests/generator_parity/numeric_publication.json)
  scpp_provider_outputs(numeric_proof_outputs ${numeric_proof_generated} ${numeric_proof_publication})
  foreach(path public/scpp/numeric.hpp
      public/scpp/short_names.hpp src/runtime.cpp prepared.json inventory.json)
    list(APPEND numeric_proof_outputs ${numeric_proof_generated}/${path})
  endforeach()
  add_custom_command(OUTPUT ${numeric_proof_outputs}
    COMMAND ${generator_launcher} ${runtime_metadata} ${numeric_proof_publication} ${numeric_proof_generated}
    DEPENDS scpp_api_generator ${generator_sources} ${api_tooling} ${native_facts} ${native_context}
      ${generator_launcher} ${runtime_metadata} ${numeric_proof_publication} VERBATIM)
  add_library(numeric_proof_runtime STATIC ${numeric_proof_generated}/src/runtime.cpp runtime/src/numeric/arithmetic.cpp)
  target_include_directories(numeric_proof_runtime PUBLIC ${numeric_proof_generated}/public
    PRIVATE ${CMAKE_CURRENT_SOURCE_DIR}/runtime/src/numeric ${CMAKE_CURRENT_SOURCE_DIR}/runtime/src/sequence)
  target_compile_features(numeric_proof_runtime PUBLIC cxx_std_23)
  set(checked_generated ${CMAKE_CURRENT_BINARY_DIR}/generated-checked-conversions)
  scpp_provider_outputs(checked_outputs ${checked_generated} ${CMAKE_CURRENT_SOURCE_DIR}/tests/checked_conversions_publication.json)
  foreach(path src/runtime.cpp prepared.json inventory.json
      public/scpp/numeric.hpp public/scpp/short_names.hpp
      public/scpp/arithmetic.hpp public/scpp/entry.hpp)
    list(APPEND checked_outputs ${checked_generated}/${path})
  endforeach()
  add_custom_command(OUTPUT ${checked_outputs}
    COMMAND ${generator_launcher} ${runtime_metadata}
      ${CMAKE_CURRENT_SOURCE_DIR}/tests/checked_conversions_publication.json ${checked_generated}
    DEPENDS scpp_api_generator ${generator_sources} ${native_facts} ${native_context}
      ${runtime_metadata} ${CMAKE_CURRENT_SOURCE_DIR}/tests/checked_conversions_publication.json VERBATIM)
  add_library(checked_conversion_runtime STATIC ${checked_generated}/src/runtime.cpp)
  target_include_directories(checked_conversion_runtime PUBLIC ${checked_generated}/public
    PRIVATE ${CMAKE_CURRENT_SOURCE_DIR}/runtime/src/numeric)
  target_compile_features(checked_conversion_runtime PUBLIC cxx_std_23)
  add_executable(checked_conversions_test tests/checked_conversions.cpp)
  target_link_libraries(checked_conversions_test PRIVATE checked_conversion_runtime)
  add_test(NAME checked_conversions COMMAND checked_conversions_test)
  add_test(NAME checked_conversion_generation COMMAND ${PHP_EXECUTABLE}
    ${CMAKE_CURRENT_SOURCE_DIR}/tools/api_generator/tests/checked_conversions.php ${native_facts} ${native_context})
  add_executable(byte_consumer_test tests/byte_consumer.cpp)
  target_link_libraries(byte_consumer_test PRIVATE scpp_runtime)
  add_test(NAME byte_consumer COMMAND byte_consumer_test)
  add_test(NAME entry_runtime COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/tests/entry_runtime.py)
  add_test(NAME output_runtime COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/tests/output_runtime.py)
  add_test(NAME compiler_entry_binding COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/entry_binding.php)
  add_test(NAME compiler_entry_binding_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/entry_binding_native.py)
  set_tests_properties(compiler_entry_binding_native PROPERTIES TIMEOUT 60)
  add_test(NAME compiler_entry COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/entry.php)
  add_test(NAME compiler_frontend COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/frontend.php)
  add_test(NAME compiler_style COMMAND ${CMAKE_COMMAND} -E env PHP_EXECUTABLE=${PHP_EXECUTABLE}
    ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tools/check_style.py)
  add_test(NAME compiler_scopes COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/scopes.php)
  add_test(NAME compiler_ast_file COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/ast_file.php)
  add_test(NAME compiler_vector_operations COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/vector_operations.php)
  add_test(NAME compiler_vector_operations_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/vector_operations_native.py)
  add_test(NAME compiler_carrier_values COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/carrier_values.php)
  add_test(NAME compiler_carrier_values_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/carrier_values_native.py)
  add_test(NAME compiler_boxed_scalars COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/boxed_scalars.php)
  add_test(NAME compiler_boxed_scalars_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/boxed_scalars_native.py)
  add_test(NAME compiler_hash_operations COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/hash_operations.php)
  add_test(NAME compiler_hash_operations_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/hash_operations_native.py)
  add_test(NAME compiler_hash_types COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/hash_types.php)
  add_test(NAME compiler_hash_types_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/hash_types_native.py)
  add_test(NAME compiler_carrier_types COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/carrier_types.php)
  add_test(NAME compiler_carrier_types_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/carrier_types_native.py)
  add_test(NAME compiler_vector_types COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/vector_types.php)
  add_test(NAME compiler_vector_types_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/vector_types_native.py)
  add_test(NAME compiler_type_references COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/type_references.php)
  add_test(NAME compiler_ast_locations COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/ast_locations.php)
  add_test(NAME compiler_ast_preparation COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/ast_preparation.php)
  add_test(NAME compiler_declaration_facts COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/declaration_facts.php)
  add_test(NAME compiler_duplicates_cli COMMAND ${CMAKE_COMMAND} -E env PHP_EXECUTABLE=${PHP_EXECUTABLE}
    ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/duplicates_cli.py)
  add_test(NAME compiler_incremental COMMAND ${CMAKE_COMMAND} -E env PHP_EXECUTABLE=${PHP_EXECUTABLE}
    ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/incremental.py)
  add_test(NAME compiler_duplicates COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/duplicates.php)
  add_test(NAME compiler_functions COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/functions.php)
  add_test(NAME compiler_direct_lowering COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/direct_lowering.php)
  add_test(NAME compiler_lowering_catalog COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/lowering_catalog.php)
  add_test(NAME compiler_direct_lowering_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/direct_lowering_native.py)
  set_tests_properties(compiler_direct_lowering_native PROPERTIES TIMEOUT 120)
  add_test(NAME compiler_mixed_ordering COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/mixed_ordering.php)
  add_test(NAME compiler_mixed_ordering_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/mixed_ordering_native.py)
  set_tests_properties(compiler_mixed_ordering_native PROPERTIES TIMEOUT 60)
  add_test(NAME compiler_mixed_arithmetic COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/mixed_arithmetic.php)
  add_test(NAME compiler_mixed_arithmetic_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/mixed_arithmetic_native.py)
  set_tests_properties(compiler_mixed_arithmetic_native PROPERTIES TIMEOUT 60)
  add_test(NAME compiler_mixed_compounds COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/mixed_compounds.php)
  add_test(NAME compiler_mixed_compounds_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/mixed_compounds_native.py)
  set_tests_properties(compiler_mixed_compounds_native PROPERTIES TIMEOUT 60)
  add_test(NAME compiler_mixed_equality COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/mixed_equality.php)
  add_test(NAME compiler_mixed_equality_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/mixed_equality_native.py)
  set_tests_properties(compiler_mixed_equality_native PROPERTIES TIMEOUT 60)
  add_test(NAME compiler_string_equality COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/string_equality.php)
  add_test(NAME compiler_string_equality_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/string_equality_native.py)
  set_tests_properties(compiler_string_equality_native PROPERTIES TIMEOUT 60)
  add_test(NAME compiler_power COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/power.php)
  add_test(NAME compiler_power_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/power_native.py)
  set_tests_properties(compiler_power compiler_power_native PROPERTIES TIMEOUT 60)
  add_test(NAME compiler_bitwise COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/bitwise.php)
  add_test(NAME compiler_bitwise_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/bitwise_native.py)
  set_tests_properties(compiler_bitwise compiler_bitwise_native PROPERTIES TIMEOUT 60)
  add_test(NAME compiler_integer_unary_updates COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/integer_unary_updates.php)
  add_test(NAME compiler_integer_unary_updates_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/integer_unary_updates_native.py)
  set_tests_properties(compiler_integer_unary_updates compiler_integer_unary_updates_native PROPERTIES TIMEOUT 60)
  add_test(NAME compiler_mixed_numeric_families COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/mixed_numeric_families.php)
  add_test(NAME compiler_mixed_numeric_families_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/mixed_numeric_families_native.py)
  set_tests_properties(compiler_mixed_numeric_families compiler_mixed_numeric_families_native PROPERTIES TIMEOUT 60)
  add_test(NAME compiler_family_conversions COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/family_conversions.php)
  add_test(NAME compiler_family_conversions_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/family_conversions_native.py)
  set_tests_properties(compiler_family_conversions compiler_family_conversions_native PROPERTIES TIMEOUT 60)
  add_test(NAME compiler_optional_integers COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/optional_integers.php)
  add_test(NAME compiler_optional_integers_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/optional_integers_native.py)
  set_tests_properties(compiler_optional_integers compiler_optional_integers_native PROPERTIES TIMEOUT 120)
  add_test(NAME compiler_enums COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/enums.php)
  add_test(NAME compiler_enums_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/enums_native.py)
  set_tests_properties(compiler_enums compiler_enums_native PROPERTIES TIMEOUT 120)
  add_test(NAME compiler_integer_compounds COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/integer_compounds.php)
  add_test(NAME compiler_integer_compounds_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/integer_compounds_native.py)
  set_tests_properties(compiler_integer_compounds compiler_integer_compounds_native PROPERTIES TIMEOUT 60)
  add_test(NAME compiler_integer_promotion COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/integer_promotion.php)
  add_test(NAME compiler_integer_promotion_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/integer_promotion_native.py)
  set_tests_properties(compiler_integer_promotion compiler_integer_promotion_native PROPERTIES TIMEOUT 60)
  add_test(NAME compiler_byte_alias COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/byte_alias.php)
  add_test(NAME compiler_byte_alias_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/byte_alias_native.py)
  set_tests_properties(compiler_byte_alias_native PROPERTIES TIMEOUT 60)
  add_test(NAME compiler_float_updates COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/float_updates.php)
  add_test(NAME compiler_float_updates_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/float_updates_native.py)
  set_tests_properties(compiler_float_updates_native PROPERTIES TIMEOUT 60)
  add_test(NAME compiler_float_compounds COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/float_compounds.php)
  add_test(NAME compiler_float_compounds_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/float_compounds_native.py)
  set_tests_properties(compiler_float_compounds_native PROPERTIES TIMEOUT 60)
  add_test(NAME compiler_float_arithmetic COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/float_arithmetic.php)
  add_test(NAME compiler_float_arithmetic_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/float_arithmetic_native.py)
  set_tests_properties(compiler_float_arithmetic_native PROPERTIES TIMEOUT 60)
  add_test(NAME compiler_arithmetic COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/arithmetic.php)
  add_test(NAME compiler_logical COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/logical.php)
  add_test(NAME compiler_transfers COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/transfers.php)
  add_test(NAME compiler_for COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/for.php)
  add_test(NAME compiler_unary COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/unary.php)
  add_test(NAME compiler_unary_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/unary_native.py)
  set_tests_properties(compiler_unary_native PROPERTIES TIMEOUT 60)
  add_test(NAME compiler_updates COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/updates.php)
  add_test(NAME compiler_updates_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/updates_native.py)
  set_tests_properties(compiler_updates_native PROPERTIES TIMEOUT 60)
  add_test(NAME compiler_compounds COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/compounds.php)
  add_test(NAME compiler_compounds_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/compounds_native.py)
  set_tests_properties(compiler_compounds_native PROPERTIES TIMEOUT 60)
  add_test(NAME compiler_type_bindings COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/type_bindings.php)
  add_test(NAME compiler_casts COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/casts.php)
  add_test(NAME compiler_casts_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/casts_native.py)
  set_tests_properties(compiler_casts_native PROPERTIES TIMEOUT 60)
  add_test(NAME compiler_constants COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/constants.php)
  add_test(NAME compiler_constants_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/constants_native.py)
  set_tests_properties(compiler_constants_native PROPERTIES TIMEOUT 60)
  add_test(NAME compiler_for_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/for_native.py)
  set_tests_properties(compiler_for_native PROPERTIES TIMEOUT 60)
  add_test(NAME compiler_transfers_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/transfers_native.py)
  set_tests_properties(compiler_transfers_native PROPERTIES TIMEOUT 60)
  add_test(NAME compiler_logical_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/logical_native.py)
  set_tests_properties(compiler_logical_native PROPERTIES TIMEOUT 60)
  add_test(NAME compiler_arithmetic_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/arithmetic_native.py)
  set_tests_properties(compiler_arithmetic_native PROPERTIES TIMEOUT 60)
  add_test(NAME compiler_arithmetic_argument_order COMMAND ${Python3_EXECUTABLE} -B
    ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/arithmetic_argument_order.py --cxx ${CMAKE_CXX_COMPILER})
  add_test(NAME arithmetic_runtime COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/tests/arithmetic_runtime.py)
  add_test(NAME compiler_comparisons COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/comparisons.php)
  add_test(NAME compiler_comparisons_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/comparisons_native.py)
  set_tests_properties(compiler_comparisons_native PROPERTIES TIMEOUT 60)
  add_test(NAME compiler_loops COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/loops.php)
  add_test(NAME compiler_loops_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/loops_native.py)
  set_tests_properties(compiler_loops_native PROPERTIES TIMEOUT 60)
  add_test(NAME compiler_truthiness_bindings COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/truthiness_bindings.php)
  add_test(NAME compiler_conditions COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/conditions.php)
  add_test(NAME compiler_conditions_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/conditions_native.py)
  add_test(NAME compiler_reference_parameters COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/reference_parameters.php)
  add_test(NAME compiler_reference_parameters_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/reference_parameters_native.py)
  add_test(NAME compiler_parameter_defaults COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/parameter_defaults.php)
  add_test(NAME compiler_parameter_defaults_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/parameter_defaults_native.py)
  add_test(NAME compiler_typed_functions COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/typed_functions.php)
  add_test(NAME compiler_typed_functions_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/typed_functions_native.py)
  add_test(NAME compiler_integer_radix_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/integer_radix_native.py)
  add_test(NAME compiler_module_availability COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/module_availability.py)
  add_test(NAME compiler_constant_evaluation COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/constant_evaluation.php)
  add_test(NAME compiler_literal_families COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/literal_families.php)
  add_test(NAME compiler_declaration_policy COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/declaration_policy.php)
  add_test(NAME compiler_inference COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/inference.php)
  add_test(NAME compiler_inference_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/inference_native.py)
  add_test(NAME compiler_assignments COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/assignments.php)
  add_test(NAME compiler_assignments_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/assignments_native.py)
  add_test(NAME compiler_text_casts COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/text_casts.php)
  add_test(NAME compiler_text_casts_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/strings_native.py --fixture text_casts.php)
  add_test(NAME compiler_string_index COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/string_index.php)
  add_test(NAME compiler_string_index_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/strings_native.py --fixture string_index.php)
  add_test(NAME compiler_interpolation COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/interpolation.php)
  add_test(NAME compiler_interpolation_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/strings_native.py --fixture interpolation.php)
  add_test(NAME compiler_structs_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/structs_native.py)
  add_test(NAME compiler_struct_constructors_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/struct_constructors_native.py)
  add_test(NAME compiler_struct_methods_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/struct_methods_native.py)
  add_test(NAME compiler_struct_constants_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/struct_constants_native.py)
  add_test(NAME compiler_struct_properties_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/struct_properties_native.py)
  add_test(NAME compiler_weak_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/weak_native.py)
  add_test(NAME compiler_shared_references_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/shared_references_native.py)
  add_test(NAME compiler_app_namespace_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/app_namespace_native.py)
  add_test(NAME compiler_strlen COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/strlen.php)
  add_test(NAME compiler_strlen_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/strings_native.py --fixture strlen.php)
  add_test(NAME compiler_strings_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/strings_native.py)
  add_test(NAME compiler_strings COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/strings.php)
  add_test(NAME compiler_echo_chain COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/echo_chain.php)
  add_test(NAME compiler_echo_chain_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/echo_chain_native.py)
  set_tests_properties(compiler_echo_chain_native PROPERTIES TIMEOUT 60)
  add_test(NAME compiler_output COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/output.php)
  add_test(NAME compiler_modules COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/modules.php)
  add_test(NAME compiler_source_changes COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/source_changes.php)
  add_test(NAME compiler_loading_plumbing COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/loading_plumbing.php)
  add_test(NAME compiler_project COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/project_compiler.php)
  add_test(NAME compiler_incremental_publication COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/incremental_publication.php)
  add_test(NAME compiler_unit_headers_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/unit_headers_native.py)
  set_tests_properties(compiler_unit_headers_native PROPERTIES TIMEOUT 90)
  add_test(NAME compiler_incremental_executable_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/incremental_executable_native.py)
  set_tests_properties(compiler_incremental_executable_native PROPERTIES TIMEOUT 90)
  add_test(NAME compiler_incremental_frontend COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/incremental_frontend.php)
  add_test(NAME compiler_preparation_work COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/preparation_work.php)
  add_test(NAME compiler_incremental_preparation COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/incremental_preparation.php)
  add_test(NAME compiler_grouped_observers COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/grouped_observers.php)
  add_test(NAME compiler_symbol_retirement COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/retention/retirement.php)
  add_test(NAME compiler_failure_cleanup COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/retention/failures.php)
  add_test(NAME compiler_incremental_equivalence COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/retention/equivalence.py)
  set_tests_properties(compiler_incremental_equivalence PROPERTIES TIMEOUT 120 ENVIRONMENT "PHP_EXECUTABLE=${PHP_EXECUTABLE}")
  add_test(NAME compiler_dependency_watches COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/dependency_watches.php)
  add_test(NAME compiler_namespaces COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/namespaces.php)
  add_test(NAME compiler_imports COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/imports.php)
  add_test(NAME compiler_globals COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/globals.php)
  add_test(NAME compiler_scalars_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/scalars_native.py)
  add_test(NAME compiler_floating_native COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/floating_native.py)
  add_test(NAME compiler_floating COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/floating.php)
  add_test(NAME compiler_scalars COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/scalars.php)
  add_test(NAME compiler_types COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/types.php)
  add_test(NAME compiler_frontend_service COMMAND ${CMAKE_COMMAND} -E env PHP_EXECUTABLE=${PHP_EXECUTABLE}
    ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/frontend_service.py)
  set_tests_properties(compiler_frontend_service PROPERTIES TIMEOUT 60)
  add_test(NAME compiler_manifests COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/manifests.php)
  add_test(NAME compiler_program COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/compiler/tests/program.php)
  add_test(NAME generator_cleanup COMMAND ${PHP_EXECUTABLE} ${CMAKE_CURRENT_SOURCE_DIR}/tools/api_generator/tests/publication.php)
  add_test(NAME compiler_lowering_generation COMMAND ${PHP_EXECUTABLE}
    ${CMAKE_CURRENT_SOURCE_DIR}/tools/api_generator/tests/compiler.php ${native_facts} ${native_context})
  add_test(NAME collection_argument_targets COMMAND ${Python3_EXECUTABLE} -B
    ${CMAKE_CURRENT_SOURCE_DIR}/tools/api_generator/tests/collection_arguments.py ${native_facts} ${native_context})
  add_test(NAME hash_provider_exports COMMAND ${Python3_EXECUTABLE} -B ${CMAKE_CURRENT_SOURCE_DIR}/tools/api_generator/tests/hash_providers.py)
  add_test(NAME carrier_provider_exports COMMAND ${Python3_EXECUTABLE} -B
    ${CMAKE_CURRENT_SOURCE_DIR}/tools/api_generator/tests/carrier_providers.py)
  add_test(NAME vector_provider_exports COMMAND ${Python3_EXECUTABLE} -B
    ${CMAKE_CURRENT_SOURCE_DIR}/tools/api_generator/tests/vector_providers.py)
  add_test(NAME runtime_submodule_providers COMMAND ${Python3_EXECUTABLE} -B
    ${CMAKE_CURRENT_SOURCE_DIR}/tools/api_generator/tests/submodules.py)
  add_test(NAME numeric_contract COMMAND ${PHP_EXECUTABLE}
    ${CMAKE_CURRENT_SOURCE_DIR}/tools/api_generator/tests/numeric.php ${native_facts} ${native_context})
  add_test(NAME generator_input_contract COMMAND ${PHP_EXECUTABLE}
    ${CMAKE_CURRENT_SOURCE_DIR}/tools/api_generator/tests/contracts.php ${native_facts} ${native_context})
  add_test(NAME generator_parity COMMAND ${Python3_EXECUTABLE} -B
    ${CMAKE_CURRENT_SOURCE_DIR}/tests/generator_parity/parity.py
    --reference ${generator_launcher}
    --source ${CMAKE_CURRENT_SOURCE_DIR}
    --work ${CMAKE_CURRENT_BINARY_DIR}/generator-parity)
  add_test(NAME runtime_family_contract COMMAND ${Python3_EXECUTABLE} -B
    ${CMAKE_CURRENT_SOURCE_DIR}/tests/generator_parity/runtime_parity.py
    --generator ${api_tooling} --facts ${native_facts} --context ${native_context}
    --work ${CMAKE_CURRENT_BINARY_DIR}/runtime-family-contract)
  add_test(NAME scalar_contract COMMAND ${Python3_EXECUTABLE} -B
    ${CMAKE_CURRENT_SOURCE_DIR}/tests/generator_parity/scalar_parity.py
    --generator ${api_tooling} --facts ${native_facts} --context ${native_context}
    --work ${CMAKE_CURRENT_BINARY_DIR}/scalar-contract --emit
    --cxx ${CMAKE_CXX_COMPILER} --flag=-stdlib=libc++)
  add_test(NAME native_qualification_cache COMMAND ${Python3_EXECUTABLE} -B
    ${CMAKE_CURRENT_SOURCE_DIR}/tools/layout_probe/test_qualification.py --cxx ${CMAKE_CXX_COMPILER})
  add_test(NAME native_probe_contract COMMAND ${Python3_EXECUTABLE} -B
    ${CMAKE_CURRENT_SOURCE_DIR}/tools/layout_probe/test_integration.py
    --generator ${api_tooling} --cxx ${CMAKE_CXX_COMPILER}
    --flag=-stdlib=libc++ --flag=--ld-path=${SCPP_MOLD_LINKER}
    --work ${CMAKE_CURRENT_BINARY_DIR}/probe-contract)
  add_test(NAME generator_parity_comparison COMMAND ${Python3_EXECUTABLE} -B
    ${CMAKE_CURRENT_SOURCE_DIR}/tests/generator_parity/test_comparison.py)
  add_test(NAME carrier_publication COMMAND ${CMAKE_COMMAND}
    -DMOLD_LINKER=${SCPP_MOLD_LINKER}
    -DGENERATOR=${generator_launcher}
    -DMETADATA=${runtime_metadata} -DPUBLICATION=${numeric_proof_publication}
    -DCXX=${CMAKE_CXX_COMPILER} "-DCXX_FLAGS=${CMAKE_CXX_FLAGS}"
    -DSOURCE=${CMAKE_CURRENT_SOURCE_DIR}
    -DWORK=${CMAKE_CURRENT_BINARY_DIR}/carrier-proof
    -P ${CMAKE_CURRENT_SOURCE_DIR}/tests/carrier_publication.cmake)
  add_executable(carrier_consumer_test tests/carrier_consumer.cpp)
  target_link_libraries(carrier_consumer_test PRIVATE numeric_proof_runtime)
  add_test(NAME carrier_consumer COMMAND carrier_consumer_test)
  add_test(NAME runtime_demo COMMAND scpp_runtime_demo)
  set_tests_properties(runtime_demo PROPERTIES PASS_REGULAR_EXPRESSION "Ada: 21; ratio=7")
  add_executable(vector_consumer_test tests/vector_consumer.cpp)
  target_link_libraries(vector_consumer_test PRIVATE numeric_proof_runtime)
  add_test(NAME vector_consumer COMMAND vector_consumer_test)
  add_test(NAME vector_publication COMMAND ${CMAKE_COMMAND}
    -DMOLD_LINKER=${SCPP_MOLD_LINKER}
    -DGENERATOR=${generator_launcher}
    -DMETADATA=${runtime_metadata} -DPUBLICATION=${numeric_proof_publication}
    -DCXX=${CMAKE_CXX_COMPILER} "-DCXX_FLAGS=${CMAKE_CXX_FLAGS}"
    -DSOURCE=${CMAKE_CURRENT_SOURCE_DIR}
    -DWORK=${CMAKE_CURRENT_BINARY_DIR}/vector-proof
    -P ${CMAKE_CURRENT_SOURCE_DIR}/tests/vector_publication.cmake)
  add_executable(string_consumer_test tests/string_consumer.cpp)
  target_link_libraries(string_consumer_test PRIVATE numeric_proof_runtime)
  add_test(NAME string_consumer COMMAND string_consumer_test)
  add_test(NAME string_publication COMMAND ${CMAKE_COMMAND}
    -DMOLD_LINKER=${SCPP_MOLD_LINKER}
    -DGENERATOR=${generator_launcher}
    -DMETADATA=${runtime_metadata} -DPUBLICATION=${numeric_proof_publication}
    -DCXX=${CMAKE_CXX_COMPILER} "-DCXX_FLAGS=${CMAKE_CXX_FLAGS}"
    -DSOURCE=${CMAKE_CURRENT_SOURCE_DIR}
    -DWORK=${CMAKE_CURRENT_BINARY_DIR}/string-proof
    -P ${CMAKE_CURRENT_SOURCE_DIR}/tests/string_publication.cmake)
  add_executable(sequence_semantics_test tests/sequence_semantics.cpp)
  target_include_directories(sequence_semantics_test PRIVATE ${CMAKE_CURRENT_SOURCE_DIR}/runtime/src/sequence)
  target_compile_features(sequence_semantics_test PRIVATE cxx_std_23)
  add_test(NAME sequence_semantics COMMAND sequence_semantics_test)
  add_executable(floating_consumer_test tests/floating_consumer.cpp)
  target_link_libraries(floating_consumer_test PRIVATE numeric_proof_runtime)
  add_test(NAME floating_consumer COMMAND floating_consumer_test)
  add_executable(value_consumer_test tests/value_consumer.cpp)
  target_link_libraries(value_consumer_test PRIVATE numeric_proof_runtime)
  add_test(NAME value_consumer COMMAND value_consumer_test)
  add_executable(integer_consumer_test tests/integer_consumer.cpp)
  target_link_libraries(integer_consumer_test PRIVATE numeric_proof_runtime)
  add_test(NAME integer_consumer COMMAND integer_consumer_test)
  add_test(NAME runtime_freshness COMMAND scpp_api_generator
    ${runtime_metadata} ${runtime_publication} ${runtime_generated} --check)
  add_test(NAME runtime_generation COMMAND ${CMAKE_COMMAND}
    -DCXX=${CMAKE_CXX_COMPILER} "-DCXX_FLAGS=${CMAKE_CXX_FLAGS}"
    -DGENERATOR=${generator_launcher}
    -DMETADATA=${runtime_metadata} -DPUBLICATION=${numeric_proof_publication}
    -DWORK=${CMAKE_CURRENT_BINARY_DIR}/generation-proof
    -P ${CMAKE_CURRENT_SOURCE_DIR}/tests/runtime_generation.cmake)
  add_test(NAME runtime_public_boundary COMMAND ${CMAKE_COMMAND}
    -DCXX=${CMAKE_CXX_COMPILER} "-DCXX_FLAGS=${CMAKE_CXX_FLAGS}"
    -DGENERATED=${numeric_proof_generated} -DWORK=${CMAKE_CURRENT_BINARY_DIR}/public-proof
    -P ${CMAKE_CURRENT_SOURCE_DIR}/tests/runtime_public_boundary.cmake)
  add_test(NAME integer_full_publication COMMAND ${CMAKE_COMMAND}
    -DMOLD_LINKER=${SCPP_MOLD_LINKER}
    -DGENERATOR=${generator_launcher}
    -DMETADATA=${runtime_metadata} -DPUBLICATION=${numeric_proof_publication}
    -DCXX=${CMAKE_CXX_COMPILER} "-DCXX_FLAGS=${CMAKE_CXX_FLAGS}"
    -DSOURCE=${CMAKE_CURRENT_SOURCE_DIR}
    -DWORK=${CMAKE_CURRENT_BINARY_DIR}/integer-full-proof
    -P ${CMAKE_CURRENT_SOURCE_DIR}/tests/integer_full_publication.cmake)
  add_test(NAME scalar_alias_publication COMMAND ${CMAKE_COMMAND}
    -DGENERATOR=${generator_launcher}
    -DMETADATA=${runtime_metadata} -DPUBLICATION=${numeric_proof_publication}
    -DCXX=${CMAKE_CXX_COMPILER} "-DCXX_FLAGS=${CMAKE_CXX_FLAGS}"
    -DWORK=${CMAKE_CURRENT_BINARY_DIR}/native-publication-proof
    -P ${CMAKE_CURRENT_SOURCE_DIR}/tests/scalar_alias_publication.cmake)
endif()


if(BUILD_TESTING)
  add_subdirectory(tests/ownership)
  add_subdirectory(tests/hash)
  add_subdirectory(tests/packed)
  add_subdirectory(tests/mixed)
  add_subdirectory(tests/dynamic)
  add_subdirectory(tests/boxed)
  add_subdirectory(tests/boxed_results)
  add_subdirectory(tests/json)
  add_subdirectory(tests/datetime)
endif()
