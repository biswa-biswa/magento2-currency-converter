define(['jquery'], function ($) {
    'use strict';

    return function (config, element) {
        var $root = $(element),
            $from = $root.find('#cc-from'),
            $to = $root.find('#cc-to'),
            $amount = $root.find('#cc-amount'),
            $result = $root.find('#cc-result'),
            $rate = $root.find('#cc-rate'),
            $date = $root.find('#cc-date'),
            $error = $root.find('#cc-error'),
            $chart = $root.find('#cc-chart'),
            $legend = $root.find('#cc-chart-legend'),
            currentRate = null,
            currencies = [];

        function request(url, data) {
            return $.getJSON(url, data);
        }

        function currencyCode(item) {
            return item.iso_code || item.code || item.currency || '';
        }

        function currencyName(item) {
            return item.name || item.iso_code || item.code || '';
        }

        function formatNumber(value, code) {
            try {
                return new Intl.NumberFormat(undefined, {
                    style: 'currency',
                    currency: code,
                    maximumFractionDigits: 4
                }).format(value);
            } catch (e) {
                return Number(value).toFixed(4) + ' ' + code;
            }
        }

        function showError(message) {
            $error.text(message || 'Unable to load exchange-rate data.');
        }

        function clearError() {
            $error.text('');
        }

        function drawChart(rows) {
            var values = (rows || []).filter(function (row) {
                    return Number.isFinite(Number(row.rate));
                }),
                width = 900, height = 300, left = 72, right = 24, top = 24, bottom = 48,
                rates, min, max, range, points, path, i, x, y, ticks = 4, markup = '';

            if (!values.length) {
                $chart.html('<text x="450" y="150" text-anchor="middle">No historical data available.</text>');
                $legend.empty();
                return;
            }

            values.sort(function (a, b) { return a.date.localeCompare(b.date); });
            rates = values.map(function (row) { return Number(row.rate); });
            min = Math.min.apply(null, rates);
            max = Math.max.apply(null, rates);
            range = max - min || Math.max(Math.abs(max) * 0.02, 0.000001);
            min -= range * 0.08;
            max += range * 0.08;
            range = max - min;

            for (i = 0; i <= ticks; i++) {
                y = top + (height - top - bottom) * i / ticks;
                var label = (max - range * i / ticks).toPrecision(5);
                markup += '<line x1="' + left + '" y1="' + y + '" x2="' + (width - right) +
                    '" y2="' + y + '" class="chart-grid"/>';
                markup += '<text x="' + (left - 10) + '" y="' + (y + 4) +
                    '" text-anchor="end" class="chart-axis-label">' + label + '</text>';
            }

            points = values.map(function (row, index) {
                x = left + (values.length === 1 ? 0 : (width - left - right) * index / (values.length - 1));
                y = top + (height - top - bottom) * (max - Number(row.rate)) / range;
                return x.toFixed(2) + ',' + y.toFixed(2);
            });
            path = points.join(' ');
            markup += '<polyline points="' + path + '" class="chart-line" fill="none"/>';
            markup += '<text x="' + left + '" y="' + (height - 12) + '" class="chart-axis-label">' +
                values[0].date + '</text>';
            markup += '<text x="' + (width - right) + '" y="' + (height - 12) +
                '" text-anchor="end" class="chart-axis-label">' + values[values.length - 1].date + '</text>';
            $chart.html(markup);
            $legend.text($from.val() + ' → ' + $to.val() + ' · ' + values.length + ' observations');
        }

        function refresh() {
            var from = $from.val(), to = $to.val(), amount = Math.max(0, Number($amount.val()) || 0),
                days = Number($root.find('#cc-period').val()) || 30;

            if (!from || !to) {
                return;
            }
            clearError();
            $rate.text('Loading current rate…');
            $result.text('—');

            $.when(
                request(config.rateUrl, {from: from, to: to}),
                request(config.historyUrl, {from: from, to: to, days: days})
            ).done(function (rateResponse, historyResponse) {
                var rateData = rateResponse[0].rate,
                    history = historyResponse[0].history || [];

                currentRate = Number(rateData.rate);
                $rate.text('1 ' + from + ' = ' + currentRate + ' ' + to);
                $result.text(formatNumber(amount * currentRate, to));
                $date.text('Reference date: ' + (rateData.date || ''));
                drawChart(history);
            }).fail(function (xhr) {
                currentRate = null;
                showError(xhr.responseJSON && xhr.responseJSON.error ?
                    xhr.responseJSON.error : 'Unable to retrieve exchange rates. Please try again.');
                $rate.text('Exchange rate unavailable');
                $result.text('—');
                drawChart([]);
            });
        }

        function loadCurrencies() {
            request(config.currenciesUrl).done(function (response) {
                currencies = response.currencies || [];
                currencies.sort(function (a, b) {
                    return currencyCode(a).localeCompare(currencyCode(b));
                });
                var options = currencies.map(function (item) {
                    var code = currencyCode(item);
                    return code ? $('<option>').val(code).text(code + ' — ' + currencyName(item)) : null;
                }).filter(Boolean);

                $from.empty().append(options.map(function (option) { return option.clone(); }));
                $to.empty().append(options.map(function (option) { return option.clone(); }));
                $from.val('USD');
                $to.val('EUR');
                if (!$from.val()) { $from.prop('selectedIndex', 0); }
                if (!$to.val()) { $to.prop('selectedIndex', Math.min(1, options.length - 1)); }
                refresh();
            }).fail(function (xhr) {
                showError(xhr.responseJSON && xhr.responseJSON.error ?
                    xhr.responseJSON.error : 'Unable to load supported currencies.');
            });
        }

        $root.on('change', '#cc-from, #cc-to, #cc-period', refresh);
        $root.on('input', '#cc-amount', function () {
            if (currentRate !== null) {
                $result.text(formatNumber((Number($amount.val()) || 0) * currentRate, $to.val()));
            }
        });
        $root.on('click', '.cc-swap', function () {
            var from = $from.val(), to = $to.val();
            $from.val(to);
            $to.val(from);
            refresh();
        });

        loadCurrencies();
    };
});
