import * as d3 from "https://cdn.jsdelivr.net/npm/d3@7/+esm";
import { iniciarInactividad } from './inactividad.js';

iniciarInactividad();

function unpack(rows, key) {
    return rows.map(function(row) { return row[key]; });
}

export function plotEURDataChart(defaultCurrency) {
    const currencies = {
        EUR: '€',
        USD: '$',
    };
    const currencySign = currencies[defaultCurrency]
d3.json("./PHP/EUR_rates_to_json.php")
    .then(data => {
        // Preparar los datos para Plotly
        const traces = [];
        for (const currency in data) {
            if (currency === 'USD') {
                traces.push({
                    x: data[currency].dates,
                    y: data[currency].values,
                    mode: 'lines',
                    name: currency,
                    line: { color: 'green', width: 2 },
                    marker: { size: 8 },
                });
            } else {
                const colors = {
                    'XAU': 'gold',
                    'XAG': 'silver',
                    'BTC': 'orange',
                    'ETH': 'DarkViolet'
                };

                traces.push({
                    x: data[currency].dates,
                    y: data[currency].values,
                    mode: 'lines',
                    name: currency,
                    line: { color: colors[currency], width: 2 },
                    marker: { size: 8 },
                    visible:'legendonly'
                });
            }
        }

        var config = {
            displayModeBar: false,
            responsive: true
        }

        const layout = {
            font: {
                color: 'white',
                family:'PPierSans'
            },
            title: {
                text: 'Evolución de Tasas de Cambio (EURXVAL)',
                font: {
                    color: 'white', // Ensure the title font color is explicitly set
                    size: 24 // Optional: Set the font size
                }
            },
            xaxis: {
                title: 'Fecha',
                type: 'date',
                gridcolor: 'rgba(100, 100, 100, 0.5)',
            },
            yaxis: {
                title: 'Tasa (EURXVAL)',
                type: 'log', // Escala logarítmica porque los valores varían mucho (BTC vs USD)
                gridcolor: 'rgba(100, 100, 100, 0.5)',
                tickprefix: currencySign == '€' ? '' : '$',
                ticksuffix: currencySign == '€' ? '€' : '',
            },
            margin: { t: 50, b: 50, l: 50, r: 50 },
            hovermode: 'closest',
            paper_bgcolor: "rgba(0,0,0,0", //background color of the chart container space
            plot_bgcolor: "rgba(0,0,0,0)" //background color of plot area
        };

        // Renderizar el gráfico
        Plotly.newPlot('chart', traces, layout, config);
    })
    .catch(error => {
        console.error('Error al cargar los datos:', error);
    });
}

export function plotUSDDataChart(defaultCurrency) {
    const currencies = {
        EUR: '€',
        USD: '$',
    };
    const currencySign = currencies[defaultCurrency]
    d3.json("./PHP/USD_rates_to_json.php")
        .then(data => {
            // Preparar los datos para Plotly
            const traces = [];
            for (const currency in data) {
                if (currency === 'EUR') {
                    traces.push({
                        x: data[currency].dates,
                        y: data[currency].values,
                        mode: 'lines',
                        name: currency,
                        line: { color: 'green', width: 2 },
                        marker: { size: 8 },
                    });
                } else {
                    const colors = {
                        'XAU': 'gold',
                        'XAG': 'silver',
                        'BTC': 'orange',
                        'ETH': 'DarkViolet'
                    };

                    traces.push({
                        x: data[currency].dates,
                        y: data[currency].values,
                        mode: 'lines',
                        name: currency,
                        line: { color: colors[currency], width: 2 },
                        marker: { size: 8 },
                        visible:'legendonly'

                    });
                }
            }
    
            var config = {
                displayModeBar: false,
                responsive: true
            }
    
            const layout = {
                font: {
                    color: 'white',
                    family:'PPierSans'
                },
                title: {
                    text: 'Evolución de Tasas de Cambio (USDXVAL)',
                    font: {
                        color: 'white', // Ensure the title font color is explicitly set
                        size: 24 // Optional: Set the font size
                    }
                },
                xaxis: {
                    title: 'Fecha',
                    type: 'date',
                    gridcolor: 'rgba(100, 100, 100, 0.5)',

                },
                yaxis: {
                    title: 'Tasa (EURXVAL)',
                    type: 'log', // Escala logarítmica porque los valores varían mucho (BTC vs USD)
                    gridcolor: 'rgba(100, 100, 100, 0.5)',
                    tickprefix: currencySign == '€' ? '' : '$',
                    ticksuffix: currencySign == '€' ? '€' : '',
                },
                margin: { t: 50, b: 50, l: 50, r: 50 },
                hovermode: 'closest',
                paper_bgcolor: "rgba(0,0,0,0", //background color of the chart container space
                plot_bgcolor: "rgba(0,0,0,0)" //background color of plot area
            };
    
            // Renderizar el gráfico
            Plotly.newPlot('chart', traces, layout, config);
        })
        .catch(error => {
            console.error('Error al cargar los datos:', error);
        });
    }

export function plotBalanceHistoryChart(data, currency) {
    const traces = [];
    const balanceHistory = data.balanceHistory;
    const currencies = {
        EUR: '€',
        USD: '$',
    };
    const currencySign = currencies[currency]
    const colors = {
        'USD': 'green',
        'EUR': 'blue',
        'XAU': 'gold',
        'XAG': 'silver',
        'BTC': 'orange',
        'ETH': 'DarkViolet'
    };

    // Prepare traces for each currency
    for (const date in balanceHistory) {
        for (const currency in balanceHistory[date]) {
            const traceIndex = traces.findIndex(trace => trace.name === currency);
            if (traceIndex === -1) {
                // Create a new trace for this currency
                
                traces.push({
                    x: [date],
                    y: [balanceHistory[date][currency]],
                    mode: 'lines+markers',
                    name: currency,
                    line: { color: colors[currency], width: 2 },
                    marker: { size: 8 },
                });
            } else {
                // Append data to the existing trace
                traces[traceIndex].x.push(date);
                traces[traceIndex].y.push(balanceHistory[date][currency]);
            }
        }
    }

    const config = {
        displayModeBar: false,
        responsive: true
    };

    const layout = {
        font: {
            color: 'white',
            family: 'PPierSans'
        },
        title: {
            text: `Histórico de ${currency} por Moneda`,
            font: {
                color: 'white', // Ensure the title font color is explicitly set
                size: 24 // Optional: Set the font size
            }
        },
        xaxis: {
            title: 'Fecha',
            type: 'date',
            gridcolor: 'rgba(100, 100, 100, 0.5)',
        },
        yaxis: {
            title: 'Balance',
            gridcolor: 'rgba(100, 100, 100, 0.5)',
            tickprefix: currencySign == '€' ? '' : '$',
            ticksuffix: currencySign == '€' ? '€' : '',
        },
        margin: { t: 50, b: 50, l: 50, r: 50 },
        hovermode: 'closest',
        paper_bgcolor: "rgba(0,0,0,0)", // background color of the chart container space
        plot_bgcolor: "rgba(0,0,0,0)" // background color of plot area
    };

    // Render the chart
    Plotly.newPlot('chart', traces, layout, config);
}
