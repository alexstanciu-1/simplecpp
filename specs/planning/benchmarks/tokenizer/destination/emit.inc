            if (Destination_Trial::$selected !== null) {
                $site = Destination_Trial::$selected;
                if (isset($site->skip[spl_object_id($statement)])) continue;
                if ($statement === $site->append) {
                    $inputs = [];
                    foreach ($site->writes as $write) {
                        $inputs[] = $this->value($write->value, $global_references, $declarations, $required_headers);
                    }
                    $receiver = $this->value($statement->receiver, $global_references, $declarations, $required_headers);
                    $lines[] = "\t(" . $receiver . ').append_fields(' . implode(', ', $inputs) . ');';
                    continue;
                }
            }
