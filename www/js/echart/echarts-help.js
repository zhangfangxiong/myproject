function ShowEChartLineOrBar(div_name, xAxis, Series, LegendValue, title, subtext) {
    var myChart = echarts.init(document.getElementById(div_name));
    myChart.setOption({
        title: {
            text: title,
            subtext: subtext,
            x: 'left'
        },

        tooltip: {
            trigger: 'axis'
        },
        legend: {
            data: LegendValue,
            x: 'center'
        },
        toolbox: {
            show: true,
            feature: {
                mark: { show: false },
                dataView: { show: false, readOnly: false },
                magicType: { show: true, type: ['line', 'bar'] },
                restore: { show: true },
                saveAsImage: { show: true }
            }
        },
        calculable: true,
        xAxis: xAxis,
        yAxis: [
            {
                type: 'value',
                splitArea: { show: true }
            }
        ],
        series: Series
    });
}

function ShowEChartLineOrBarPer(div_name, xAxis, Series, LegendValue, title, subtext) {
    var myChart = echarts.init(document.getElementById(div_name));
    myChart.setOption({
        title: {
            text: title,
            subtext: subtext,
            x: 'center'
        },

        tooltip: {
            trigger: 'axis'
        },
        legend: {
            data: LegendValue,
            x: 'center'
        },
        toolbox: {
            show: true,
            feature: {
                mark: { show: false },
                dataView: { show: false, readOnly: false },
                magicType: { show: false, type: ['line', 'bar'] },
                restore: { show: true },
                saveAsImage: { show: true }
            }
        },
        calculable: true,
        xAxis: xAxis,
        yAxis: [
            {
                type: 'value',
                axisLabel: {
                    formatter: '{value} %'
                },

                splitArea: { show: false }
            }
        ],
        series: Series
    });
}

function ShowEChartLineOrBarRevert(div_name, yAxis, Series, LegendValue, title, subtext) {
    var myChart = echarts.init(document.getElementById(div_name));
    myChart.setOption({
        title: {
            text: title,
            subtext: subtext,
            x: 'left'
        },

        tooltip: {
            trigger: 'axis'
        },
        legend: {
            data: LegendValue,
            x: 'center'
        },
        toolbox: {
            show: true,
            feature: {
                mark: { show: false },
                dataView: { show: false, readOnly: false },
                magicType: { show: true, type: ['line', 'bar'] },
                restore: { show: true },
                saveAsImage: { show: true }
            }
        },
        calculable: true,
        yAxis: yAxis,
        xAxis: [
            {
                type: 'value',
                splitArea: { show: true }
            }
        ],
        series: Series
    });
}

function ShowEChartPie(div_name, Data, Legend, title, subtext) {
    var myChart = echarts.init(document.getElementById(div_name));
    myChart.setOption({

        title: {
            text: title,
            subtext: subtext,
            x: 'center'
        },
        tooltip: {
            trigger: 'item',
            formatter: "{b} : {c} ({d}%)"
        },
        legend: {
            orient: 'vertical',
            x: 'left',
            y: 'center',
            data: Legend
        },
        toolbox: {
            show: true,
            feature: {
//                dataView: { show: true, readOnly: false },
                restore: { show: true },
                saveAsImage: { show: true }
            }
        },
        calculable: true,
        series: [
        {
            type: 'pie',
            radius: '70%',
            center: ['50%', 140],
            data: Data
        }
    ]
    });
}