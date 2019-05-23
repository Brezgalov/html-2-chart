<?php

namespace app\models\forms;

use yii\base\Model;
use yii\web\UploadedFile;

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
            $this->addError('file', "Не удалось обнаружить необходимое количество строк в файле. Ожидается, что значения будут указаны начиная со строки номер {$this->value_row} строке");
            return false;
        }

        $debugData = [];
        $resultData = [];
        $balance = 0;
        foreach (array_slice($rows, $this->value_row) as $i => $row) {
            $columns = $row->find('td');
            $debugData[$i] = [
                'row' => $i + 1,
                'columns' => count($columns),
                'text' => $row->plaintext,
                'point' => null,
                'lastval' => null,
                'balance' => $balance,
            ];

            if (!$this->columnsFormatMatches($columns)) {
                continue;
            }
            if (!preg_match('/^[0-9]+$/', $columns[0]->plaintext)) {
                break;
            }

            $lastColumn = array_pop($columns);
            $lastVal = str_replace(' ', '', $lastColumn->plaintext);
            preg_match('/^-?[0-9]+.?[0-9]*$/', $lastVal, $lastValMatches);
            if (empty($lastValMatches)) {
                continue;
            }

            $lastVal = $lastValMatches[0];
            $balance += $lastVal;
            $debugData[$i]['lastval'] = $lastVal;
            $debugData[$i]['balance'] = $balance;
            $debugData[$i]['point'] = $resultData[] = [
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