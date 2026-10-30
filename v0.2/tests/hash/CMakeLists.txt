set(hash_generated ${CMAKE_BINARY_DIR}/generated-hash-proof)
set(hash_publication ${CMAKE_CURRENT_SOURCE_DIR}/publication.json)
set(hash_outputs)
foreach(path public/scpp/integers.hpp public/scpp/values.hpp public/scpp/numeric.hpp
    public/scpp/native_bridge.hpp public/scpp/short_names.hpp public/scpp/string.hpp public/scpp/carriers.hpp public/scpp/hashes.hpp
    src/runtime.cpp src/hash.cpp prepared.json inventory.json)
  list(APPEND hash_outputs ${hash_generated}/${path})
endforeach()
add_custom_command(OUTPUT ${hash_outputs}
  COMMAND ${generator_launcher} ${runtime_metadata} ${hash_publication} ${hash_generated}
  DEPENDS scpp_api_generator ${generator_sources} ${api_tooling} ${native_facts} ${native_context} ${generator_launcher} ${runtime_metadata} ${hash_publication}
  VERBATIM)
add_custom_target(hash_publication DEPENDS ${hash_outputs})
add_library(hash_proof_runtime STATIC ${hash_generated}/src/runtime.cpp ${hash_generated}/src/hash.cpp)
add_dependencies(hash_proof_runtime hash_publication)
target_include_directories(hash_proof_runtime PUBLIC ${hash_generated}/public
  PRIVATE ${PROJECT_SOURCE_DIR}/runtime/src/numeric ${PROJECT_SOURCE_DIR}/runtime/src/sequence ${PROJECT_SOURCE_DIR}/runtime/src/hash)
target_compile_features(hash_proof_runtime PUBLIC cxx_std_23)
add_executable(hash_consumer_test consumer.cpp)
target_link_libraries(hash_consumer_test PRIVATE hash_proof_runtime)
add_test(NAME hash_consumer COMMAND hash_consumer_test)
add_executable(hash_storage_test storage.cpp)
target_include_directories(hash_storage_test PRIVATE ${PROJECT_SOURCE_DIR}/runtime/src/hash)
target_compile_features(hash_storage_test PRIVATE cxx_std_23)
add_test(NAME hash_storage COMMAND hash_storage_test)
add_test(NAME hash_freshness COMMAND ${generator_launcher} ${runtime_metadata} ${hash_publication} ${hash_generated} --check)
add_test(NAME hash_boundary COMMAND ${CMAKE_COMMAND}
  -DCXX=${CMAKE_CXX_COMPILER} "-DCXX_FLAGS=${CMAKE_CXX_FLAGS}"
  -DGENERATED=${hash_generated} -DWORK=${CMAKE_CURRENT_BINARY_DIR}/boundary
  -P ${CMAKE_CURRENT_SOURCE_DIR}/boundary.cmake)
