jQuery(document).ready(function(){
    jQuery('.matomo-table[data-chart]').each(function() {
        var LABEL_EVERY_N_TICKS = 2;
        var colors = [
            "#55bae7",
            "#ddb745",
            "#4c2a9c",
            "#3e7b2d"
        ];
        var currentColorIndex = 0;
        var $this = jQuery(this);
        var $postbox = $this.parents('div.postbox');
        var $table = $postbox.find('table');
        $table.hide();
        var $canvas = jQuery('<canvas/>',{'id':$this.attr('data-chart')});
        $canvas.insertAfter($table);
        var labels = [];
        var metrics = JSON.parse($table.attr('data-metrics'));
        var datasets = {};
        $table.find('tr').each(function() {
            var $row = jQuery(this);

            var labelCell = $row.find('td:nth-child(1)');
            labels.unshift(labelCell.attr('data-label') || labelCell.text());

            Object.keys(metrics).forEach((metricName, i) => {
                var metricTitle = metrics[metricName];

                if (!datasets[metricName]) {
                    var color = colors[currentColorIndex];
                    currentColorIndex = (currentColorIndex + 1) % colors.length;

                    datasets[metricName] = {
                        label: metricTitle,
                        data: [],
                        borderColor: color,
                        pointBackgroundColor: color,
                        pointBorderColor: color,
                        pointHoverBackgroundColor: color,
                        pointHoverBorderColor: color,
                    };
                }

                var value = $row.find('td:nth-child(' + (i + 2) + ')').text();
                if ( '-' === value ) {
                    value = 0;
                }

                datasets[metricName].data.unshift(value);
            });
        });

        var myChart = new Chart($canvas, {
            type: 'line',
            data: {
                labels: labels,
                datasets: Object.values(datasets)
            },
            options: {
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    x: {
                        ticks: {
                            callback: function (value, index, values) {
                                if ( index % LABEL_EVERY_N_TICKS !== 0 ) {
                                    return '';
                                }

                                return labels[index];
                            },
                            maxRotation: 0,
                        }
                    },
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    });
});
