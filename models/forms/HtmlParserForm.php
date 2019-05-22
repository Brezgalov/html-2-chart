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
    public $first_name = 'x';

    /**
     * @var string
     */
    public $last_name = 'y';

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
        foreach (array_slice($rows, $this->value_row) as $row) {
            $rowParseResult = $this->parseTableRow($row);
            if (is_array($rowParseResult)) {
                $resultData[] = $rowParseResult;
            }
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
        return count($columns) >= $this->min_columns;
    }

    private function parseTableRow(\simple_html_dom_node $row)
    {
        $columns = $row->find('td');
        if (!$this->columnsFormatMatches($columns)) {
            return true;
        }

        //вернуть false если формат первой ячейки не катит и убить цикл

        $last = array_pop($columns);
        return [
            $this->first_name => $columns[0]->plaintext,
            $this->last_name => floatval($last->plaintext),//str_replace('.', ',', ),
        ];
    }
}