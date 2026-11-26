# Mining the v0.1 archive

The archived compiler, runtime, specs, matrix tests and comparison inputs are in `source.tar.gz`. The active compiler builds and runs independently. Extract the archive for tasks that need historical behavior or implementation details.

From the repository root, verify the checksum and extract into a fresh directory:

```sh
(cd v0.1 && printf '%s  %s\n' 8159926c00b7cb3d372e0312284deab61d6455e080204d0ba38ddea09cdf26b1 source.tar.gz | sha256sum -c -) &&
  mkdir v0.1/unpacked &&
  tar -xzf v0.1/source.tar.gz --strip-components=1 -C v0.1/unpacked
```

If the archive has already been extracted, use the existing copy. Read `v0.1/unpacked/ARCHIVE.md` for the feature inventories, historical evidence and recovery instructions. The donor compiler is under `v0.1/unpacked/my-try/`. For an occasional lookup, list members with `tar -tzf v0.1/source.tar.gz` or read a single file with `tar -xOzf v0.1/source.tar.gz v0.1/ARCHIVE.md`.

Git ignores extracted files and local mining work under this folder. Keep intended active changes in their owning directories outside this archive. Only this README and the frozen archive belong in version control here.

## Provenance

The snapshot contains 24,987 files from commit `afb1f6844a542e160b9364015bf988cb0fb90fbd`. It omits 19 generated Ninja graphs and six dependency manifests under the compile-latency results. The earlier commit retains those outputs. To reproduce the gzip level 6 archive with Git and GNU gzip, run:

```sh
git -c tar.umask=0022 archive --format=tar afb1f6844a542e160b9364015bf988cb0fb90fbd -- v0.1 \
  ':(exclude,glob)v0.1/tools/compile_latency/results/**/*.ninja' \
  ':(exclude,glob)v0.1/tools/compile_latency/results/**/dependency-manifest.json' |
  gzip -n -6 > /tmp/simplecpp-v01-rebuilt.tar.gz
```
