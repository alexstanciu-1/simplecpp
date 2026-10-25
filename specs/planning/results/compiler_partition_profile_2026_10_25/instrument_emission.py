from pathlib import Path

def instrument(source):
 def marker(name):return "\n\t\tfs_output_write(fs_output_root(fs_read_text('stages.txt')), 'profile-"+name+"', '');\n"
 p=source/'05_backend/cpp/generate.php';s=p.read_text()
 for old,name in [('\t\t$this->render_sources($definitions, $bodies, $jobs);','render-start'),('\t\t$result /** Storage<cpp_artifact> */ = new Storage();','headers-start'),('\t\t$groups /** Storage<cpp_implementation_group> */ = CPP_Implementation_Groups::assemble','groups-start'),('\t\t$entry_owner = object_cast($entry_file','groups-end'),('\t\t\t$this->specializations->append_artifacts($result);','specializations-start'),('\t\treturn $result;','generation-end')]:
  assert old in s,old;s=s.replace(old,marker(name)+old,1)
 p.write_text(s)
 p=source/'05_backend/cpp/specializations.php';s=p.read_text()
 for old,name in [('$depths = $this->layout_depths($artifacts);','layout-depths-start'),('$order = $this->emission_order();','sort-start'),('$member_prefixes /** hash<string> */ = [];','specialization-assembly-start'),('for ($depth = 0; $depth <= $maximum_depth; $depth++)','layout-output-start'),('foreach ($implementations as $bucket => $implementation)','implementation-output-start')]:
  assert old in s,old;s=s.replace(old,marker(name)+old,1)
 p.write_text(s)

 p=source/'compiler/output_publication.php';s=p.read_text()
 for old,name in [("$current /** hash<string> */ = [];",'publication-manifest-end'),("$journal = '{\"format\":\"scpp-output-journal-v1\",\"files\":[';",'publication-preflight-end'),("$this->mark_pending($journal);",'publication-journal-end'),("foreach ($next as $path => $artifact) {\n\t\t\tfs_output_write",'publication-writes-start'),("fs_output_write($this->root, '.scpp-output.manifest', $manifest);",'publication-writes-end')]:
  assert old in s,old;s=s.replace(old,marker(name)+old,1)
 p.write_text(s)
