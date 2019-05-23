function drawChart(data) {
    if (data.length <= 0) {
        return;
    }
    var min = data[0].y;
    var max = data[0].y;

    for (var i = 0; i < data.length; i++) {
        if (data[i].y > max) {
            max = data[i].y;
        }
        if (data[i].y < min) {
            min = data[i].y;
        }
        console.log(data[i].y);
    }

    var ctx = document.getElementById('myChart').getContext('2d');
    var myChart = new Chart(ctx, {
        type: 'line',
        responsive: true,
        data: {
            labels: ['y', 'x'],
            datasets: [{
                label: 'Зависимость прибыли от номера операции',
                data: data,
                borderColor: 'rgba(54, 162, 235, 1)',
                borderWidth: 1,
                pointRadius: 1
            }]
        },
        options: {
            scales: {
                yAxes: [{
                    ticks: {
                        suggestedMax: max,
                        suggestedMin: min,
                        stepSize: 200
                    }
                }],
                xAxes: [{
                    type: 'linear',
                    position: 'bottom',
                }]
            }
        }
    });
}

