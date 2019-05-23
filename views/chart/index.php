<?php
use yii\widgets\ActiveForm;

$this->title = 'Парсер html в график';
?>
<div id="title-row" class="row">
    <div class="col-md-12 text-center">
        <h1>Я превращу ваш html в график</h1>
    </div>
</div>
<div id="description-row" class="row">
    <div class="col-md-12">
        <h5>Значиния берутся начиная со столбца номер: <?= $parserForm->value_row ?></h5>
        <h5>Значения прекратят выбираться, когда в первом столбце появится строка не состоящая целиком из цифр</h5>
        <h5>Минимальное кол-во столбцов в строке: <?= $parserForm->min_columns ?></h5>
    </div>
</div>
<hr/>
<div id="form-row" class="row">
    <div class="col-md-12">
        <?php $form = ActiveForm::begin(['options' => ['enctype' => 'multipart/form-data']]) ?>
            <div class="form-group">
                <?= $form
                    ->field($parserForm, 'file')
                    ->label('Загрузите файл .html', ['class' => 'font-weight-bold'])
                    ->fileInput(['class' => 'form-control-file'])
                ?>
                <button type="submit" class="btn btn-primary">Распарсить</button>
            </div>
        <?php ActiveForm::end() ?>
    </div>
</div>
<hr/>
<?php if (isset($parseResult) && !empty($parseResult)): ?>
    <div id="time-row" class="row">
        <div class="col-md-12">
            <h5 class="d-inline-block">Парсинг занял: <?= round($parserForm->getLastParseTime(), 2) ?> сек;</h5>
            <h5 class="d-inline-block">Точек найдено: <?= count($parseResult) ?>;</h5>
        </div>
    </div>
    <hr/>
    <div id="result-row" class="row">
        <div class="col-md-12 text-center">
            <canvas id="myChart"></canvas>
        </div>
    </div>
    <script>
        $(document).ready(function () {
            drawChart(JSON.parse('<?php echo json_encode($parseResult) ?>'));
        })</script>
<?php endif; ?>