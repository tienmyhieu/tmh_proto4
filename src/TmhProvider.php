<?php

class TmhProvider
{
//    private $currentYear;
    private $data;
    private $json;
    private $routeMap;
    private $specimen;
    public function __construct(TmhJson $json, TmhSpecimen $specimen)
    {
//        $this->currentYear = date('Y');
        $this->data = [];
        $this->json = $json;
        $this->routeMap = $this->json->routes(__DIR__ . '/resources/', 'routes');
        $this->specimen = $specimen;
        $this->initialize();
    }

    public function ancestorRoutes(): array
    {
        return array_map(function($ancestor) { return $ancestor['route']; }, $this->data['ancestors']);
    }

    public function descendantRoutes(): array
    {
        return array_map(function($descendant) {
            $useBaseRoute = $descendant['relative'] == '1';
            $urlPrefix = $useBaseRoute ? $this->data['baseRoute'] : '';
            return [
                'active' => $descendant['active'],
                'descendant' => $descendant['descendant'],
                'entity' => $descendant['entity'],
                'href' => $urlPrefix . '/' . $descendant['route'],
                'innerHtml' => $descendant['entity'],
                'title' => $descendant['entity']
            ];
        }, array_filter($this->data['descendants'], function ($descendant) {
            return 0 < strlen($descendant['route']);
        }));
    }

    public function locale()
    {
        return $this->data['locale'];
    }

    public function mimeType()
    {
        return $this->data['mime_type'];
    }

    public function pageHtml($page): string
    {
        $html = '<table>';
        $i = 0;
        foreach ($page as $specimenTitle => $images) {
            $html .= '<tr><td colspan="2">' .  $specimenTitle . '</td></tr>';
            $html .= $this->specimenRowHtml($images);
            if ($i < count($page) - 1) {
                $html .= '<tr><td colspan="2">&nbsp;</td></tr>';
            }
            $i++;
        }
        $html .= '</table>';
        return $html;
    }

    public function specimenRowHtml($images): string
    {
        $cellHtml = '';
        $rowHtml = '';
        $isMultiple = false;
        foreach ($images as $image) {
            if (is_array($image)) {
                $isMultiple = true;
                $rowHtml .= $this->specimenRowHtml($image);
            } else {
                $isMultiple = false;
//                $image = str_replace('/128/', '/256/', $image);
//                $image = str_replace('.png', '.jpg', $image);
                $cellHtml .= '<td style="vertical-align: top; width:192px;">';
                $cellHtml .= '<img style="margin:0 auto;" src="' . $image . '"/>';
                $cellHtml .= '</td>';
            }
        }
        if (!$isMultiple) {
            $rowHtml = '<tr>';
            $rowHtml .= $cellHtml;
            $rowHtml .= '</tr>';
        }

        return $rowHtml;
    }

    public function specimens(): array
    {
        return $this->specimen->specimens();
    }

    public function specimenKey()
    {
        return $this->specimen->specimenKey();
    }

    public function route()
    {
        return $this->data['route'];
    }

    private function domains(): array
    {
        return  [
            'en.' . TMH_DOMAIN => 'en-GB', 'fr.' . TMH_DOMAIN => 'fr-FR', 'ja.' . TMH_DOMAIN => 'ja-JP',
            TMH_DOMAIN => 'vi-VN', 'www.' . TMH_DOMAIN => 'vi-VN', 'vi.' . TMH_DOMAIN => 'vi-VN',
            'zh-hans.' . TMH_DOMAIN => 'zh-Hans', 'zh-hant.' . TMH_DOMAIN => 'zh-Hant'
        ];
    }

    private function faultTolerance()
    {
        if (!$this->data['route']) {
            foreach ($this->data['descendants'] as $descendant) {
                if (0 == strlen($descendant['route'])) {
                    $this->data['route'] = $descendant;
                }
            }
        }
    }

    private function initialize()
    {
        $this->setLocale();
        $this->setRoute();
        $this->faultTolerance();
        $this->setBaseRoute();
        $this->setDescendants();
        $this->specimen->initialize($this->data, $this->json);
//        echo "<pre>";
//        print_r($this->data['route']);
//        print_r($this->specimens());
//        echo "</pre>";
    }

    private function routeFile($routePath): string
    {
        return array_key_exists($routePath, $this->routeMap) ? $this->routeMap[$routePath]: 'home';
    }

    private function routePath()
    {
        return  str_replace('/routes/' . $this->data['locale'], '', $this->data['baseDirectory']);
    }

    private function routes()
    {
        $routePath = $this->routePath();
        // echo 'routePath: ' . $routePath . PHP_EOL;
        $routeFile = $this->routeFile($routePath);
        // echo 'routeFile: ' . $routeFile . PHP_EOL;
        // echo 'baseDirectory: ' . $this->data['baseDirectory'] . PHP_EOL;
        $directory = '/routes/' . $this->data['locale'] . '/';
        return $this->json->routes(__DIR__ . $directory, $routeFile);
    }

    private function routeSegments()
    {
        parse_str($_SERVER['REDIRECT_QUERY_STRING'], $fields);
        $this->data['mime_type'] = 'html';
        $routeSegments = explode("/", $fields['title']);
        if (in_array($routeSegments[0], ['pdf', 'api'])) {
            $this->data['mime_type'] = $routeSegments[0];
            unset($routeSegments[0]);
        }
        return $routeSegments;
    }

    private function setBaseRoute()
    {
        $baseRoute = '/';
        if ($this->data['ancestors']) {
            $baseRoute .= implode('/', $this->ancestorRoutes());
        }
        $this->data['baseRoute'] = $baseRoute == '/' ? '' : $baseRoute;
    }

    private function setDescendants()
    {
        if ($this->data['route']) {
            if ($this->data['route']['descendant']) {
                $this->data['descendants'] = $this->routes();
            }
        }
    }

    private function setLocale()
    {
        $domains = $this->domains();
        $domainExists = array_key_exists($_SERVER['SERVER_NAME'], $domains);
        $this->data['locale'] = $domainExists ? $domains[$_SERVER['SERVER_NAME']] : $domains[TMH_DOMAIN];
    }

    private function setRoute()
    {
        $this->data['baseDirectory'] = '/';
        $this->data['route'] = [];
        $this->data['ancestors'] = [];
        foreach ($this->routeSegments() as $routeSegment) {
            $routes = $this->routes();
            $this->data['descendants'] = $routes;
            foreach ($routes as $route) {
                if ($route['route'] == $routeSegment) {
                    $this->data['route'] = $route;
                    $this->data['ancestors'][$route['entity']] = $route;
                    if ($route['descendant']) {
                        $this->data['baseDirectory'] .= $route['descendant'] . '/';
                        $this->data['ancestors'][$route['entity']]['baseDirectory'] = $this->data['baseDirectory'];
                    }
                }
            }
        }
    }
}
