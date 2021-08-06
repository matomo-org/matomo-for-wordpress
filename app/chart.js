jQuery(document).ready(function(){
    jQuery('*[data-chart]').each(function() {
       let $this = jQuery(this);
       let $table = $this.parent('div.postbox').find('table');
       $table.hide();
       let $canvas = jQuery('<canvas/>',{'id':$this.attr('data-chart')});
       $canvas.insertAfter($table);
       let data = [];
       let labels = [];
       let title = $this.text();
       let $row;
       let value;
       $table.find('tr').each(function() {
           $row = jQuery(this);
           value = $row.find('td:nth-child(2)').text();
           if ( '-' === value ) {
               value = 0;
           }
           data.push(value);
           labels.push($row.find('td:nth-child(1)').text());
       });

        var myChart = new Chart($canvas, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: title,
                    data: data
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    });
});