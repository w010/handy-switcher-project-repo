<?php




class Util extends XCoreUtil {


    /**
     * // Read projects and build output. Since they are json files already, glue them together into one json array
     *
     * @param string $dataDir
     * @param array $uuids - For maintenance (audit) purposes, we can collect uuids using this array
     * @return array
     */
    static public function getProjects(string $dataDir, array &$uuids = []): array
    {
        $filter = self::cleanInputVar($_GET['filter']);

        $projectsAll = [];
        $projects = [];

        // static::getFilesFromDirectory_filenames($dataDir, 'json'));

        if (is_dir($dataDir)) {
            foreach (static::getFilesFromDirectory_paths($dataDir, 'json') as $file) {

                // read file content
                $fileContent = file_get_contents($file);
                $fileParsedArray = @json_decode($fileContent, true);

                // ONLY files with single project, as single json object / json starting with {
                // omit files with array of projects / json starting with [{
                if (preg_match('/^\[/', trim($fileContent)))    {
                    continue;
                }
                else    {
                    $projectsAll[] = $fileParsedArray;
                }
            }
        }

        foreach ($projectsAll as $projectItem) {

            // ignore this field, don't import, don't compare. Switcher's internal use-only.
            //unset ($projectItem['sorting']);

            $Project = Loader::get(ModelProject::class, $projectItem);

            // search
            if ($filter) {
                // search for string occurrence in name
                if (stristr($Project->getName(), $filter))    {
                    $projects[] = $Project;
                }
                else    {
                    // search in merged array of contexts & links, in names and urls
                    foreach (array_merge((array) $Project->getContexts(), (array) $Project->getLinks()) as $testItem)  {
                        if (stristr($testItem->getName(), $filter))    {
                            $projects[] = $Project;
                            $uuids[] = $Project->getUuid();
                            break;
                        }
                        else if (stristr($testItem->getUrl(), $filter))    {
                            $projects[] = $Project;
                            $uuids[] = $Project->getUuid();
                            break;
                        }
                    }
                }
            }
            else    {
                $projects[] = $Project;
                $uuids[] = $Project->getUuid();
            }
        }


        // sort them by Name
        $sortProjects = function (ModelProject $a, ModelProject $b) {
            return strcmp($a->getName(), $b->getName());
        };

        usort($projects, $sortProjects);


        // mark duplicates on list
        foreach ($projects as $project) {
            if (count(array_keys($uuids, $project->getUuid())) > 1) {
                // this updates original array
                $project->addAdditionalData('uuid_duplicated', true);
            }
        }

        return $projects;
    }


    /**
     * Get projects as associative array, ready to output
     *
     * @param string $dataDir
     * @return array
     */
    static public function getProjects_assoc(string $dataDir): array
    {
        $projectsAssoc = [];
        foreach (static::getProjects($dataDir) as $Project) {
            $projectsAssoc[] = $Project->toArray();
        }

        return $projectsAssoc;
    }


    /**
     * Get array with misc reports about database condition, integrity, status
     * @return array
     */
    static public function getRepoDataSummary(): array
    {
        $summary = [];

        // META
        $repoDataMeta = static::getRepoDataMetaFile();
        $summary['repoDataMeta'] = $repoDataMeta;

        // AUDIT - Last check time
        $summary['audit']['last_check'] = $repoDataMeta->audit->last_check ?? -1;

        // AUDIT - Uuid
        $summary['audit']['uuid_uniqueness'] = $repoDataMeta->audit->duplicated_uuids;

        // AUDIT - Multi-project json files
        $summary['audit']['multiproject_json_files'] = $repoDataMeta->audit->multiproject_files;

        return $summary;
    }


    /**
     * READ Meta
     * The idea is that I keep some repo data metainfo in /data/.repo file.
     * @return stdClass|null
     * @throws Exception
     */
    static public function getRepoDataMetaFile(): ?stdClass
    {
        if (file_exists(XCore::App()->getDataPath() . '.meta')) {
            $data = json_decode(file_get_contents( XCore::App()->getDataPath() . '.meta'));
            if (!is_object($data)) {
                $data = new stdClass();
            }
            return $data;
        }
        else {
            Throw new Exception('Repo Meta file not found ('.XCore::App()->getDataPath() . '.meta)', 993425);
        }
    }


    /**
     * SAVE Meta
     * Note! that it replaces meta, so read it first, modify and pass here whole new meta
     * @param $data
     */
    static public function saveRepoDataStatusFile($data)
    {
        file_put_contents( XCore::App()->getDataPath() . '.meta', json_encode($data, JSON_PRETTY_PRINT));
    }
}


