/**
 * Matomo - free/libre analytics platform
 *
 * DataTable UI class for JqplotGraph/Evolution.
 *
 * @link http://www.jqplot.com
 * @link https://matomo.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

(function ($, require) {

    var exports = require('piwik/UI'),
        JqplotGraphDataTable = exports.JqplotGraphDataTable,
        JqplotGraphDataTablePrototype = JqplotGraphDataTable.prototype;

    exports.JqplotEvolutionGraphDataTable = function (element) {
        JqplotGraphDataTable.call(this, element);
    };

    $.extend(exports.JqplotEvolutionGraphDataTable.prototype, JqplotGraphDataTablePrototype, {

        _setJqplotParameters: function (params) {
            JqplotGraphDataTablePrototype._setJqplotParameters.call(this, params);

            var defaultParams = {
                axes: {
                    xaxis: {
                        pad: 1.0,
                        renderer: $.jqplot.CategoryAxisRenderer,
                        tickOptions: {
                            showGridline: false
                        }
                    }
                },
                piwikTicks: {
                    showTicks: true,
                    showGrid: true,
                    showHighlight: true,
                    tickColor: this.tickColor
                }
            };

            if (this.props.show_line_graph) {
                defaultParams.seriesDefaults = {
                    lineWidth: 1,
                    markerOptions: {
                        style: "filledCircle",
                        size: 6,
                        shadow: false
                    }
                };
            } else {
                defaultParams.seriesDefaults = {
                    renderer: $.jqplot.BarRenderer,
                    rendererOptions: {
                        shadowOffset: 1,
                        shadowDepth: 2,
                        shadowAlpha: .2,
                        fillToZero: true,
                        barMargin: this.data[0].length > 10 ? 2 : 10
                    }
                };
            }

            var overrideParams = {
                legend: {
                    show: false
                },
                canvasLegend: {
                    show: true
                }
            };
            this.jqplotParams = $.extend(true, {}, defaultParams, this.jqplotParams, overrideParams);
        },

        _bindEvents: function () {
            JqplotGraphDataTablePrototype._bindEvents.call(this);

            var self = this;
            var lastTick = false;

            $('#' + this.targetDivId)
                .on('jqplotMouseLeave', function (e, s, i, d) {
                    $(this).css('cursor', 'default');
                    JqplotGraphDataTablePrototype._destroyDataPointTooltip.call(this, $(this));
                })
                .on('jqplotClick', function (e, s, i, d) {
                    if (lastTick !== false && typeof self.jqplotParams.axes.xaxis.onclick != 'undefined'
                        && typeof self.jqplotParams.axes.xaxis.onclick[lastTick] == 'string') {
                        var url = self.jqplotParams.axes.xaxis.onclick[lastTick];

                        broadcast.propagateNewPage(url);
                    }
                })
                .on('jqplotPiwikTickOver', function (e, tick) {
                    lastTick = tick;
                    var label;

                    var dataByAxis = {};
                    for (var d = 0; d < self.data.length; ++d) {
                        var valueUnformatted = self.data[d][tick];
                        if (typeof valueUnformatted === 'undefined' || valueUnformatted === null) {
                            continue;
                        }

                        var axis = self.jqplotParams.series[d]._xaxis || 'xaxis';
                        if (!dataByAxis[axis]) {
                            dataByAxis[axis] = [];
                        }

                        var value = self.formatY(valueUnformatted, d);
                        var series = self.jqplotParams.series[d].label;

                        var seriesColor = self.jqplotParams.seriesColors[d];

                        dataByAxis[axis].push('<span class="tooltip-series-color" style="background-color: ' + seriesColor + ';"></span>' + '<strong>' + value + '</strong> ' + piwikHelper.htmlEntities(series));
                    }

                    var xAxisCount = 0;
                    Object.keys(self.jqplotParams.axes).forEach(function (axis) {
                        if (axis.substring(0, 1) === 'x') {
                            ++xAxisCount;
                        }
                    });

                    var content = '';
                    for (var i = 0; i < xAxisCount; ++i) {
                        var axisName = i === 0 ? 'xaxis' : 'x' + (i + 1) + 'axis';
                        if (!dataByAxis[axisName] || !dataByAxis[axisName].length) {
                            continue;
                        }

                        if (typeof self.jqplotParams.axes[axisName].labels != 'undefined') {
                            label = self.jqplotParams.axes[axisName].labels[tick];
                        } else {
                            label = self.jqplotParams.axes[axisName].ticks[tick];
                        }

                        if (typeof label === 'undefined') { // sanity check
                            continue;
                        }

                        content += '<h3 class="evolution-tooltip-header">'+piwikHelper.htmlEntities(label)+'</h3>'+dataByAxis[axisName].join('<br />');

                        var last_n = null;
                        switch (self.param.period) {
                            case 'day':
                                last_n = self.param.evolution_day_last_n;
                                break;
                            case 'week':
                                last_n = self.param.evolution_week_last_n;
                                break;
                            case 'month':
                                last_n = self.param.evolution_month_last_n;
                                break;
                            case 'year':
                                last_n = self.param.evolution_year_last_n;
                                break;
                        }
                        if (last_n) {
                            var piwikPeriods = piwikHelper.getAngularDependency('piwikPeriods');
                            if (self.param.hasOwnProperty('dateUsedInGraph') && self.param.dateUsedInGraph.indexOf(',') > 0) {
                                var graphDateRange = self.param.dateUsedInGraph.split(',');
                                if (graphDateRange.length == 2) {
                                    var hoverDateRange = piwikPeriods.RangePeriod.getLastNRangeChild(self.param.period, graphDateRange[1], (last_n - lastTick)-1);
                                    if (hoverDateRange.containsToday()) {
                                        content += '<br />(' + self._lang.incompletePeriod + ')';
                                    }
                                }
                            }
                        }
                    }

                    $(this).tooltip({
                        track:   true,
                        items:   'div',
                        content: content,
                        show: false,
                        hide: false
                    }).trigger('mouseover');
                    if (typeof self.jqplotParams.axes.xaxis.onclick != 'undefined'
                        && typeof self.jqplotParams.axes.xaxis.onclick[lastTick] == 'string') {
                        $(this).css('cursor', 'pointer');
                    }
                });

            this.setYTicks();
        },

        _destroyDataPointTooltip: function () {
            // do nothing, tooltips are destroyed in the jqplotMouseLeave event
        },

        render: function () {
            JqplotGraphDataTablePrototype.render.call(this);

            if (initializeSparklines) {
                initializeSparklines();
            }
        }
    });

})(jQuery, require);
