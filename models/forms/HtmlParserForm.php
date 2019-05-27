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
        $readAllowed = false;
        $operationNumber = 1;
        foreach ($rows as $i => $row) {
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

            if (!$readAllowed && $this->shouldStartReading($columns)) {
                $readAllowed = true;
                continue;
            }

            if ($readAllowed && $this->shouldStopReading($columns)) {
                $readAllowed = false;
                continue;
            }

            if (!$readAllowed) {
                continue;
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
            $debugData[$i]['point'] =
            $resultData[] = [
                $this->x_key => $operationNumber,
                $this->y_key => round($balance, 2),
            ];
            $operationNumber++;
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

    /**
     * @param array $columns
     * @return bool
     */
    private function shouldStartReading(array $columns)
    {
        return
            count($columns) == 14 &&
            $columns[0]->plaintext == 'Ticket'
        ;
    }

    /**
     * @param array $columns
     * @return bool
     */
    private function shouldStopReading(array $columns)
    {
        return !preg_match('/^[0-9]+$/', $columns[0]->plaintext);
    }
}