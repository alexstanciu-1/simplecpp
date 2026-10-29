set(ownership_generated ${runtime_generated}/ownership-proof)
set(ownership_publication ${CMAKE_CURRENT_SOURCE_DIR}/publication.json)
set(ownership_outputs)
foreach(path public/scpp/integers.hpp public/scpp/values.hpp public/scpp/numeric.hpp
    public/scpp/native_bridge.hpp public/scpp/short_names.hpp public/scpp/ownership.hpp
    src/runtime.cpp src/ownership.cpp prepared.json inventory.json)
  list(APPEND ownership_outputs ${ownership_generated}/${path})
endforeach()
add_custom_command(OUTPUT ${ownership_outputs}
  COMMAND ${generator_launcher} ${runtime_metadata} ${ownership_publication} ${ownership_generated}
  DEPENDS scpp_api_generator ${generator_sources} ${api_tooling} ${native_facts} ${native_context} ${generator_launcher} ${runtime_metadata} ${ownership_publication}
  VERBATIM)
add_custom_target(ownership_publication DEPENDS ${ownership_outputs})
add_library(ownership_proof_runtime STATIC ${ownership_generated}/src/runtime.cpp ${ownership_generated}/src/ownership.cpp records.cpp)
add_dependencies(ownership_proof_runtime ownership_publication)
target_include_directories(ownership_proof_runtime PUBLIC ${ownership_generated}/public ${CMAKE_CURRENT_SOURCE_DIR}/include
  PRIVATE ${PROJECT_SOURCE_DIR}/runtime/src/numeric ${PROJECT_SOURCE_DIR}/runtime/src/ownership)
target_compile_features(ownership_proof_runtime PUBLIC cxx_std_23)
find_package(Threads REQUIRED)
add_executable(ownership_consumer_test consumer.cpp)
target_link_libraries(ownership_consumer_test PRIVATE ownership_proof_runtime Threads::Threads)
add_test(NAME ownership_consumer COMMAND ownership_consumer_test)
add_test(NAME ownership_freshness COMMAND ${generator_launcher} ${runtime_metadata} ${ownership_publication} ${ownership_generated} --check)
add_test(NAME ownership_boundary COMMAND ${CMAKE_COMMAND}
  -DCXX=${CMAKE_CXX_COMPILER} "-DCXX_FLAGS=${CMAKE_CXX_FLAGS}"
  -DPRIVATE=${PROJECT_SOURCE_DIR}/runtime/src/ownership
  -DGENERATED=${ownership_generated} -DRECORDS=${CMAKE_CURRENT_SOURCE_DIR}/include
  -DWORK=${CMAKE_CURRENT_BINARY_DIR}/boundary
  -P ${CMAKE_CURRENT_SOURCE_DIR}/boundary.cmake)

add_executable(ownership_value_const_test value_const_consumer.cpp)
target_link_libraries(ownership_value_const_test PRIVATE ownership_proof_runtime)
add_test(NAME ownership_value_const COMMAND ownership_value_const_test)
