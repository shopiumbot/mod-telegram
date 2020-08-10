<?php

namespace shopium\mod\telegram\controllers;

use core\components\controllers\AdminController;
use function GuzzleHttp\Psr7\parse_query;
use panix\engine\CMS;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use shopium\mod\csv\components\Helper;
use simplehtmldom\HtmlDocument;
use simplehtmldom\HtmlNode;
use simplehtmldom\HtmlWeb;
use Yii;
use yii\helpers\FileHelper;


class SettingsController extends AdminController
{

    public $icon = 'settings';

    public function actionTest()
    {
        $url = 'https://amimore.ru/category/jaschericy';
        $doc = new HtmlWeb();
        $html = $doc->load($url);

        $data = [];


        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('List');


        foreach ($html->find('div.pop-mat') as $product) {
            $data['products'][] = $this->product($product);
        }
        $find_pager = $html->find('div.pagination a');
        if ($find_pager) {
            $pag = last($find_pager);
            if ($pag) {
                $pagParams = parse_query($pag->href);
                foreach (range(2, (int)$pagParams['page']) as $k => $pagination) {
                    $doc2 = new HtmlWeb();
                    $html2 = $doc2->load($url . '&page=' . $pagination);
                    foreach ($html2->find('div.pop-mat') as $product) {
                        $data['products'][] = $this->product($product);
                    }

                    // $sheet->setCellValue(Helper::num2alpha($alpha) . $index, $l);
                }
            }
        }


        $index = 1;
        foreach ($data['products'] as $key => $row) {
            $alpha = 1;
            foreach ($row as $l) {
                $sheet->setCellValue(Helper::num2alpha($alpha) . $index, $l);
                $alpha++;
            }
            $index++;
        }


        $writer = new Xlsx($spreadsheet);
        $writer->save(Yii::getAlias('@runtime') . DIRECTORY_SEPARATOR . basename($url).'.xlsx');
        CMS::dump($data, 20);

        die;
    }

    /**
     * @param  HtmlDocument $product
     * @return array
     */
    public function product($product)
    {


        /** @var HtmlDocument $product */
        $dd = $product->find('h3 a');
        $img = $product->find('a img');
        $doc3 = new HtmlWeb();
        $html3 = $doc3->load('https://amimore.ru' . $dd[0]->href);
        $productData = [];
        //connect product page

        $productData['Тип'] = 'Игрушки';
        $productData['name'] = $dd[0]->innertext;
        // $productData['url'] = 'https://amimore.ru' . $dd[0]->href;
        $productData['Цена'] = 500;
        $bc = $html3->find('.breadcrumbs a');
        $productData['category'] = trim('Игрушки/' . $bc[1]->innertext . '/' . str_replace('схемы крючком', '', $bc[2]->innertext));


        $productData['img'] = 'https://amimore.ru' . str_replace('/img/mi', '/img/bi', $img[0]->src);

        foreach ($html3->find('article > p') as $detail) {
            $productData['text'] = trim(preg_replace("/.*\./Us", "", $detail->innertext, 1));
        }


        foreach ($html3->find('.bottom-block > object') as $pdf) {
            /** @var HtmlNode $pdf */
            $path = Yii::getAlias('@runtime/pdf') . DIRECTORY_SEPARATOR . $productData['category'];
            if (!file_exists($path)) {
                FileHelper::createDirectory($path);
            }
            $pdf_url = 'https://amimore.ru' . $pdf->getAttribute('data');
            if (!file_exists($path . DIRECTORY_SEPARATOR . basename($pdf->getAttribute('data')))) {
                file_put_contents($path . '/' . basename($pdf->getAttribute('data')), file_get_contents($pdf_url));
            }

        }

        return $productData;
    }
}
