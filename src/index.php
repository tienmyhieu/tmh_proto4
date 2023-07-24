<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once(__DIR__ . '/defines.php');
require_once(__DIR__ . '/TmhJson.php');
require_once(__DIR__ . '/TmhProvider.php');
require_once(__DIR__ . '/TmhSpecimen.php');
$json = new TmhJson();
$specimen = new TmhSpecimen();
$provider = new TmhProvider($json, $specimen);
$isPdf = 'pdf' == $provider->mimeType();
if ($isPdf) {
    $specimens = $provider->specimens();
    if ($specimens) {
        $language = substr($provider->locale(), 0, 2);
        $font = [
            'en' => 'dejavusans',
            'vi' => 'dejavusans',
            'zh' => 'msungstdlight'
        ];
//        $title = [
//            'en' => 'Thien bat ai dao dia bat ai bao',
//            'vi' => 'Thiên bất ái đạo địa bất ái bảo',
//            'zh' => '天不愛道地不愛寶'
//        ];
        require_once (__DIR__ . '/pdf/TCPDF/tcpdf.php');
        $pdf = new TCPDF();
        $path1 = TMH_IMAGES . '128/2898ffb1b6734c29bc8de33c682cc590-1.png';
        $path2 = TMH_IMAGES . '128/2898ffb1b6734c29bc8de33c682cc590-2.png';

        $pages = $specimens;
        foreach ($pages as $pageTitle => $page) {
            $pdf->AddPage();
            $pdf->SetFont($font[$language], '', 22);
            $pageTitleParts = explode('_', $pageTitle);
            if (is_numeric($pageTitleParts[count($pageTitleParts) - 1])) {
                unset($pageTitleParts[count($pageTitleParts) - 1]);
                $pageTitle = implode('_', $pageTitleParts);
            }
            $pdf->writeHTML($pageTitle . "<br/>");
//            $pdf->Image($path1, 20);
//            $pdf->Image($path2, 65);
            $pdf->SetFont($font[$language], '', 14);
            $pdf->writeHTML($provider->pageHtml($page), false);
        }
        $pdf->Output('tien_my_hieu.pdf');
    }
} else {
    $specimenKey = $provider->specimenKey();
    $specimens = $provider->specimens();
    echo "<pre>";
//    echo $specimenKey . PHP_EOL;
//    print_r($specimens);
    $descendantRoutes = $provider->descendantRoutes();
    foreach ($descendantRoutes as $descendantRoute) {
        if ($descendantRoute['active']) {
            if ($descendantRoute['descendant'] === '.' || $descendantRoute['descendant'] === 'x') {
                echo $descendantRoute['href'] . PHP_EOL;
            } else {
                $images = null;
                if (array_key_exists($specimenKey, $specimens)) {
                    if (array_key_exists($descendantRoute['descendant'], $specimens[$specimenKey])) {
                        $images = $specimens[$specimenKey][$descendantRoute['descendant']];
                    }
                }
//                if ($images) {
//                    $i = 0;
//                    foreach ($images as $image) {
//                        $eol = $i < count($images) - 1 ? '' : PHP_EOL;
//                        echo '<a href="' . $descendantRoute['href'] . '">';
//                        echo '<img src="' . $image . '" />';
//                        echo '</a>' . $eol;
//                        $i++;
//                    }
//                } else {
//                    echo '<a href="' . $descendantRoute['href'] . '">';
//                    echo $descendantRoute['href'];
//                    echo '</a>' . PHP_EOL;
//                }
                echo '<a href="' . $descendantRoute['href'] . '">';
                echo $descendantRoute['href'];
                echo '</a>' . PHP_EOL;
            }
        }
    }
    echo "</pre>";
}
