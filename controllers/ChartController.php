<?php

namespace app\controllers;

use app\models\forms\HtmlParserForm;
use yii\web\Controller;
use yii\web\UploadedFile;

/**
 * Class ChartController
 * @package app\controllers
 */
class ChartController extends Controller
{
    /**
     * @var string
     */
    public $layout = 'chart';

    /**
     * Страница с графиком
     * @return string
     */
    public function actionIndex()
    {
        $parserForm = new HtmlParserForm();
        $response = [
            'parserForm' => $parserForm,
            'parseResult' => null,
        ];

        if (\Yii::$app->request->isPost) {
            $parserForm->file = UploadedFile::getInstance($parserForm, 'file');
            if ($parserForm->validate()) {
                $response['parseResult'] = $parserForm->parse();
            }
        }

        return $this->render('index', $response);
    }
}