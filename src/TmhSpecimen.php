<?php

class TmhSpecimen
{
    private $category;
//    private $config;
    private $directory;
    private $entityMetadata;
    private $file;
    private $isPdf = false;
    private $specimens = [];
    private $specimenKey;
    private $rawSpecimens = [];
    private $type;

    public function initialize($data, TmhJson $json)
    {
        $this->isPdf = $data['mime_type'] == 'pdf';
        $this->type = $data['route']['type'];
        $this->setDirectory($data);
        $this->setConfig($data);
        $this->setRawSpecimens($json);
        $this->setSpecimens();
//        echo "<pre>";
//        echo $this->directory . PHP_EOL;
//        echo $this->type . PHP_EOL;
//        echo $this->category . PHP_EOL;
//        print_r($this->config);
//        print_r($this->rawSpecimens);
//        print_r($this->specimens);
//        print_r($data);
//        echo "</pre>";
    }

    public function specimens(): array
    {
        return $this->specimens;
    }

    public function specimenKey()
    {
        return $this->specimenKey;
    }

    private function setConfig($data)
    {
//        $this->config = [];
//        $keys = [];
//        $parts = explode('/', $this->directory);
//        if (0 < count($parts)) {
//            unset($parts[count($parts) - 1]);
//            unset($parts[0]);
//            $parts = array_values($parts);
//            if (3 == count($parts)) {
//                $keys = [$parts[count($parts) - 1]];
//                unset($parts[count($parts) - 1]);
//                $parts = array_values($parts);
//            }
//            if (0 < count($parts)) {
//                foreach ($data['descendants'] as $descendant) {
//                    if ($descendant['route']) {
//                        $keys[] = $descendant['descendant'];
//                    }
//                }
//                $this->config['path'] = $parts[0];
//                $this->config['file'] = $this->category;
//                if (1 < count($parts)) {
//                    $this->config['file'] = $parts[1];
//                    $this->category = $parts[1];
//                } else {
//                    if ($this->type == 'emperor_metal') {
//                        $this->config['file'] = $this->category;
//                    } else {
//                        $this->config['file'] = $this->type;
//                    }
//                    $keys = [];
//                }
//                $this->config['keys'] = $keys;
//            }
//        }
    }

    private function isEmperor($value1, $value2): bool
    {
        $emperors = ['minh_menh', 'thieu_tri', 'tu_duc', 'types'];
        return in_array($value1, $emperors) || in_array($value2, $emperors);
    }

    private function setDirectory($data)
    {
        $this->directory = '';
        $baseDirectory = $data['ancestors'][$data['route']['entity']]['baseDirectory'] ?? '/';
        $pathParts = explode('/', $baseDirectory);
        $entities = ['auction', 'book', 'collection', 'magazine', 'museum', 'web_article'];
        $emperors = ['minh_menh', 'thieu_tri', 'tu_duc', 'types'];
        switch (count($pathParts)) {
            case 2:
                $x = 1;
                break;
            case 3:
                $x = 2;
                break;
            case 4:
                $isEmperor1 = in_array($pathParts[1], $emperors);
                $isEmperor2 = in_array($pathParts[2], $emperors);
                $isTypes = $pathParts[2] == 'types';
                if ($isEmperor1 || $isEmperor2) {
                    if ($isEmperor1) {
                        $this->file = $pathParts[1];
                        $this->directory = $pathParts[2];
                        $this->category = $pathParts[1];
                    }
                    if ($isEmperor2) {
                        $this->file = $pathParts[2];
                        $this->directory = $pathParts[1];
                        $this->category = $pathParts[2];
                    }
                }
                $isEntity = in_array($pathParts[1], $entities);
                if ($isEntity) {
                    $this->directory = $pathParts[1] . "/" . $pathParts[2];
                    $this->file = $pathParts[3];
                }
                if ($isTypes) {
                    $this->file = $pathParts[2];
                    $this->directory = $pathParts[1];
                    $this->category = $pathParts[1];
                }
                break;
            case 5:
                $isEmperor1 = in_array($pathParts[1], $emperors);
                $isEmperor2 = in_array($pathParts[2], $emperors);
                $isTypes = $pathParts[2] == 'types';
                if ($isEmperor1 || $isEmperor2) {
                    if ($isEmperor1) {
                        $this->file = $pathParts[3];
                        $this->directory = $pathParts[2];
                        $this->category = $pathParts[1];
                    }
                    if ($isEmperor2) {
                        $this->file = $pathParts[3];
                        $this->directory = $pathParts[1];
                        $this->category = $pathParts[2];
                    }
                }
                $isEntity = in_array($pathParts[1], $entities);
                if ($isEntity) {
                    $this->directory = $pathParts[1] . "/" . $pathParts[2];
                    $this->file = $pathParts[3];
                }
                if ($isTypes) {
                    $this->file = $pathParts[3];
                    $this->directory = $pathParts[1];
                    $this->category = $pathParts[1];
                }
                break;
        }
    }

    private function setSpecimens()
    {
        $this->specimens = [];
        if ($this->type == 'coin') {
            $this->specimenKey = $this->file;
            foreach ($this->rawSpecimens as $specimen) {
                $this->specimens[$this->file][$specimen['emperor']] = $specimen['images'][0];
            }
        }

        if ($this->type == 'emperor_coin') {
            $i = 0;
            $types = [];
            foreach ($this->rawSpecimens as $specimen) {
                $types[$specimen['type']] = '';
            }
            $hasTwoTypes = 1 < count($types);
            $pdfLimit = $hasTwoTypes ? 6 : 3;
            $limit = $this->isPdf ? $pdfLimit : count($this->rawSpecimens);
            $this->specimenKey = $this->category;
            $type1 = 0;
            $type2 = 0;
            foreach ($this->rawSpecimens as $key => $specimen) {
                if ($i < $limit) {
                      if ($specimen['type'] == '1' && 3 > $type1) {
                        $this->specimens[$this->category][$key] = $specimen['images'][0];
                        $type1++;
                    }
                    if ($specimen['type'] == '2' && 3 > $type2) {
                        $this->specimens[$this->category][$key] = $specimen['images'][0];
                        $type2++;
                    }
                }
                $i++;
            }
        }

        if ($this->type == 'coin') {
            $doubleTypes = ['ttttllaa'];
            $uniqueEmperors = [];
            if (in_array($this->file, $doubleTypes) && $this->directory == 'big_zinc') {
                $this->specimens = [];
                if ($this->file == 'ttttllaa') {
                    $this->specimens['ttttllaa1']['minh_menh'] = $this->rawSpecimens['chcoin_2178122_1']['images'][0];
                    $this->specimens['ttttllaa1']['thieu_tri'] = $this->rawSpecimens['mitsuru_169']['images'][0];
                    $this->specimens['ttttllaa1']['tu_duc'] = $this->rawSpecimens['mitsuru_246']['images'][0];
                    $this->specimens['ttttllaa2']['minh_menh'] = $this->rawSpecimens['mitsuru_92']['images'][0];
                    $this->specimens['ttttllaa2']['tu_duc'] = $this->rawSpecimens['mitsuru_252']['images'][0];
                }
            } else {
                $i = 0;
                $page = 0;
                $this->specimens = [];
                foreach ($this->rawSpecimens as $key => $specimen) {
                    $this->specimenKey = $key;
                    if ($this->entityMetadata && $this->entityMetadata['multiple'] == '1') {
                        if ($i % 3 == 0) {
                            $page++;
                        }
                        if (1 < count($specimen['images'])) {
                            $this->specimens[$page][$specimen['emperor']] = [$specimen['images'][0], $specimen['images'][1]];
                            $i = $i + 2;
                        } else {
                            $this->specimens[$page][$specimen['emperor']] = $specimen['images'][0];
                            $i++;
                        }
                    } else {
                        if (!in_array($specimen['emperor'], $uniqueEmperors)) {
                            $uniqueEmperors[] = $specimen['emperor'];
                            $this->specimens[$this->file][$specimen['emperor']] = $specimen['images'][0];
                            $i++;
                        }
                    }
                }
            }
        }

        if ($this->type == 'types') {
            foreach ($this->rawSpecimens as $type => $emperors) {
                $this->specimenKey = $type;
                foreach ($emperors as $emperor => $specimen) {
                    $this->specimens[$type][$emperor] = $specimen['images'][0];
                }
            }
        }

        if ($this->type == "emperor_metal") {
            $i = 0;
            $page = 0;
            foreach ($this->rawSpecimens as $emperor => $coins) {
                foreach ($coins as $coin => $specimen) {
                    if ($i % 3 == 0) {
                        $page++;
                    }
                    $this->specimenKey = $emperor . '_' . $page;
                    $this->specimens[$emperor . '_' . $page][$coin] = $specimen['images'][0];
                    $i++;
                }
            }
        }

        $entities = ['auction', 'book', 'collection', 'magazine', 'museum', 'web_article'];
        if (in_array($this->type, $entities)) {
            $i = 0;
            $page = 0;
            foreach ($this->rawSpecimens as $emperor => $coins) {
                foreach ($coins as $coin => $specimen) {
                    if ($i % 3 == 0) {
                        $page++;
                    }
                    $this->specimenKey = $emperor . '_' . $page;
                    if ($this->entityMetadata && $this->entityMetadata['multiple'] == '1') {
                        if (1 < count($specimen['images'])) {
                            $this->specimens[$page][$coin] = [$specimen['images'][0], $specimen['images'][1]];
                            $i = $i + 2;
                        } else {
                            $this->specimens[$page][$coin] = $specimen['images'][0];
                            $i++;
                        }
                    } else {
                        $this->specimens[$page][$coin] = $specimen['images'][0];
                        $i++;
                    }
                }
            }
        }
    }

    private function setRawSpecimens(TmhJson $json)
    {
        $entities = ['auction', 'book', 'collection', 'magazine', 'museum', 'web_article'];
        $specimens = $json->specimens(__DIR__ . '/specimens/' . $this->directory . '/', $this->file);
        if ($this->directory == 'big_bronze' && $this->file == 'hhttllll' && $this->type == 'coin') {
            $specimens = $json->specimens(__DIR__ . '/specimens/' . $this->directory . '/types//', $this->file);
        }
        foreach ($specimens as $key => $specimen) {
            if (in_array($this->type, $entities)) {
                if ($key != "metadata") {
                    foreach ($specimen as $emperor => $emperorSpecimen) {
                        if ($emperor != "metadata") {
                            $this->rawSpecimens[$key][$emperor] = $this->transformSpecimen($emperorSpecimen);
                        }
                    }
                } else {
                    $this->entityMetadata = $specimen;
                }
            }
            if ($this->type == "types") {
                foreach ($specimen as $emperor => $emperorSpecimen) {
                    $this->rawSpecimens[$key][$emperor] = $this->transformSpecimen($emperorSpecimen);
                }
            }
            if ($this->type == 'emperor_coin') {
                if ($specimen['emperor'] == $this->category) {
                    $this->rawSpecimens[$key] = $this->transformSpecimen($specimen);
                }
            }
            if ($this->type == "emperor_metal") {
                $this->rawSpecimens[$this->category][$key] = $this->transformSpecimen($specimen);
            }

            if ($this->type == 'coin') {
                if ($this->directory == 'big_bronze' && $this->file == 'hhttllll' && $this->type == 'coin') {
                    if ($key != "metadata") {
                        $this->rawSpecimens[$key] = $this->transformSpecimen($specimen);
                    } else {
                        $this->entityMetadata = $specimen;
                    }
                } else {
                    $this->rawSpecimens[$key] = $this->transformSpecimen($specimen);
                }
            }
        }
    }

    private function transformSpecimen($specimen)
    {
        $transformedImageGroups = [];
        foreach ($specimen['images'] as $images) {
            $transformedImages = [];
            foreach ($images as $image) {
                if ($image == '.') {
                    $image = SPACER_IMAGE;
                }
                $transformedImages[] = TMH_IMAGES . '128/' . $image;
            }
            $transformedImageGroups[] = $transformedImages;
        }
        $specimen['images'] = $transformedImageGroups;

        $transformedUploadGroups = [];
        foreach ($specimen['uploads'] as $images) {
            $transformedUploads = [];
            foreach ($images as $image) {
                $transformedUploads[] = TMH_UPLOADS . '128/' . $image;
            }
            $transformedUploadGroups[] = $transformedUploads;
        }
        $specimen['uploads'] = $transformedUploadGroups;
        return $specimen;
    }
}