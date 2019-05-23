<?php

namespace app\models\forms;

use yii\base\Model;
use yii\web\UploadedFile;

/*
 * @TODO: дописать комменты и доки
 * @TODO: отрубать цикл, если найден пробел на первом месте
 * @TODO: найти на что ругается JSON.parse
 * @TODO: выставить правильно оси
 */

/**
 * Class HtmlParserForm
 * @package app\models\forms
 */
class HtmlParserForm extends Model
{
    /**
     * @var int
     */
    public $value_row = 3;

    /**
     * @var UploadedFile
     */
    public $file;

    /**
     * @var int
     */
    public $min_columns = 5;

    /**
     * @var string
     */
    public $x_key = 'x';

    /**
     * @var string
     */
    public $y_key = 'y';

    /**
     * @var null|float
     */
    private $lastParseTime = null;

    /**
     * @return array
     */
    public function rules()
    {
        return [
            [
                'file',
                'file',
                'skipOnEmpty' => false,
                'checkExtensionByMimeType' => false,
                'extensions' => 'html',
                'maxSize' => 1024 * 1024,
            ],
        ];
    }

    /**
     * @return float
     */
    public function getLastParseTime()
    {
        return floatval($this->lastParseTime);
    }

    /**
     * @return array|bool
     */
    public function parse()
    {
        if (empty($this->file)) {
            $this->addError('file', 'Не удалось провести парсинг, так как файл не был загружен');
            return false;
        }

        $time = microtime(1);

        $html = file_get_html($this->file->tempName);
        $rows = $html->find('tr');

        if (!$this->rowsFormatMatches($rows)) {
            $this->addError('file', "Не удалось обнаружить необходимое количество строк в файле. Ожидается, что значения будут указаны в {$this->value_col} строке");
            return false;
        }

        $resultData = [];
        $balance = 0;
        foreach (array_slice($rows, $this->value_row) as $i => $row) {
            $columns = $row->find('td');
            if (!$this->columnsFormatMatches($columns)) {
                continue;
            }
            if (!preg_match('/^[0-9]+$/', $columns[0]->plaintext)) {
                break;
            }

            $last = array_pop($columns);
            $balance += floatval($last->plaintext);
            $resultData[] = [
                $this->x_key => $i,
                $this->y_key => round($balance, 1),
            ];
        }

        $this->lastParseTime = microtime(1) - $time;

        return $resultData;
    }

    /**
     * @param array $rows
     * @return bool
     */
    private function rowsFormatMatches(array $rows)
    {
        return count($rows) >= $this->min_columns;
    }

    /**
     * @param array $columns
     * @return bool
     */
    private function columnsFormatMatches(array $columns)
    {
        $total = count($columns);
        return $total >= $this->min_columns || $columns[$total-1];
    }
}