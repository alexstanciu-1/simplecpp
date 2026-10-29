# An alternative runtime publication: do not link it with scpp_runtime.
set(compiler_generated ${runtime_generated}/compiler)
set(compiler_publication ${CMAKE_CURRENT_SOURCE_DIR}/publication.json)
set(compiler_outputs)
foreach(path public/scpp/integers.hpp public/scpp/values.hpp public/scpp/numeric.hpp
    public/scpp/native_bridge.hpp public/scpp/short_names.hpp src/runtime.cpp prepared.json inventory.json)
  list(APPEND compiler_outputs ${compiler_generated}/${path})
endforeach()
add_custom_command(OUTPUT ${compiler_outputs}
  COMMAND scpp_api_generator ${runtime_metadata} ${compiler_publication} ${compiler_generated}
  DEPENDS scpp_api_generator ${runtime_metadata} ${compiler_publication}
  VERBATIM)
add_custom_target(scpp_compiler_publication DEPENDS ${compiler_outputs})
add_library(scpp_compiler_runtime STATIC ${compiler_generated}/src/runtime.cpp)
add_dependencies(scpp_compiler_runtime scpp_compiler_publication)
target_include_directories(scpp_compiler_runtime PUBLIC ${compiler_generated}/public
  PRIVATE ${PROJECT_SOURCE_DIR}/runtime/src/numeric)
target_compile_features(scpp_compiler_runtime PUBLIC cxx_std_23)
add_library(scpp_compiler_model STATIC src/token.cpp src/token_buffer.cpp src/token_sequence.cpp)
target_include_directories(scpp_compiler_model PUBLIC ${CMAKE_CURRENT_SOURCE_DIR}/include)
target_link_libraries(scpp_compiler_model PUBLIC scpp_compiler_runtime)
if(BUILD_TESTING)
  add_executable(compiler_token_sequence_test tests/token_sequence.cpp)
  target_link_libraries(compiler_token_sequence_test PRIVATE scpp_compiler_model)
  add_test(NAME compiler_token_sequence COMMAND compiler_token_sequence_test)
  add_executable(compiler_token_buffer_test tests/token_buffer.cpp)
  target_link_libraries(compiler_token_buffer_test PRIVATE scpp_compiler_model)
  add_test(NAME compiler_token_buffer COMMAND compiler_token_buffer_test)
  add_executable(compiler_token_test tests/token.cpp)
  target_link_libraries(compiler_token_test PRIVATE scpp_compiler_model)
  add_test(NAME compiler_token COMMAND compiler_token_test)
  add_test(NAME compiler_runtime_freshness COMMAND scpp_api_generator
    ${runtime_metadata} ${compiler_publication} ${compiler_generated} --check)
  add_test(NAME compiler_model_boundary COMMAND ${CMAKE_COMMAND}
    -DCXX=${CMAKE_CXX_COMPILER} "-DCXX_FLAGS=${CMAKE_CXX_FLAGS}"
    -DGENERATED=${compiler_generated} -DMODEL=${CMAKE_CURRENT_SOURCE_DIR}/include
    -DWORK=${CMAKE_CURRENT_BINARY_DIR}/boundary
    -P ${CMAKE_CURRENT_SOURCE_DIR}/tests/boundary.cmake)
endif()
