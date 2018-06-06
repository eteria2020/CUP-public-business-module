
var script = document.createElement('script');
script.src = "//maps.googleapis.com/maps/api/js?sensor=false&callback=initialize";
document.body.appendChild(script);
var geocoder = null;

function initialize()
{
    geocoder = new google.maps.Geocoder();
    refreshTable(lastPeriod);
}

$("#rents-filter-select").change(function ()
{
    var selectedValue = $(this).find(":selected").val();
    refreshTable(selectedValue);
});

function refreshTable(period)
{
    $.get(rentsUrl + "?month=" + period, function (jsonData)
    {
        console.log(jsonData);
        var i = 0;
        resetTable();
        var columnClass1 = 'block-data-table-td';
        var columnClass2 = 'cw-1-6';

        var tripsCount = jsonData.data.length;

        var grandTotal = 0;
        var grandTotalToPay = 0;

        jsonData.data.forEach(function (trip)
        {
            console.log(trip);
            var tripPayment = trip['tripPayments'];
            var tripBonuses = trip['tripBonuses'];
            var tripFreeFares = trip['tripFreeFares'];

            var diffMinutes = trip['duration'];    //minutes

            // show GRATIS for trips lower than one minute
            var tripMinutes = diffMinutes;
            var parkingMinutes = Math.ceil(trip['parkSeconds'] / 60);
            var totalAmount = 'GRATIS';
            var totalAmountValue = 0;
            var mustPay = 'GRATIS';
            var mustPayValue = 0;
            var pinType = 'Privata';

            var pinTypeValue = trip['pinType'];
            if (pinTypeValue === 'company') {
                pinType = 'Aziendale';
                totalAmount = '-';
                mustPay = '-';
            }

            // show GRATIS for not accountable trips
            if (!trip['isAccountable']) {
                totalAmount = 'GRATIS';
                mustPay = 'GRATIS';
            }

            if (typeof tripPayment !== "undefined") {
                //tripMinutes = tripPayment['tripMinutes'];
                //parkingMinutes = tripPayment['parkingMinutes'];
                totalAmountValue = (tripPayment['totalCost'] / 100);
                totalAmount = formatCurrency(totalAmountValue);
                paymentStatus = tripPayment['status'];
                mustPayValue = tripPayment['mustPayValue'] / 100;
                mustPay = formatCurrency(mustPayValue);
            }

            grandTotal = grandTotal + totalAmountValue;
            grandTotalToPay = grandTotalToPay + mustPayValue;

            tripBonus = 0;
            if (typeof tripBonuses !== "undefined") {
                for (i = 0; i < tripBonuses.length; i++) {
                    tripBonus += tripBonuses[i]['minutes'];
                }

                if (tripBonus == diffMinutes) {
                    totalAmount = formatCurrency(0);
                    mustPay = formatCurrency(0);
                }
            }
            tripFree = 0;
            if (typeof tripFreeFares !== "undefined") {
                for (i = 0; i < tripFreeFares.length; i++) {
                    tripFree += tripFreeFares[0]['minutes'];
                }
            }

            if (tripMinutes<0) {
                tripMinutes = 0;
            }

            addRow(
                    0,
                    pinType,
                    trip['timestampBeginningString'],
                    trip['timestampEndString'],
                    tripMinutes,
                    parkingMinutes,
                    totalAmount,
                    mustPay,
                    trip['latitudeBeginning'],
                    trip['longitudeBeginning'],
                    trip['latitudeEnd'],
                    trip['longitudeEnd'],
                    tripBonus,
                    tripFree,
                    trip['addressBeginning'],
                    trip['addressEnd']
                    );

            // after last line is rendered...
            if (--tripsCount === 0) {
                addFinalRow(
                        1,
                        grandTotal + ' \u20ac',
                        grandTotalToPay + ' \u20ac'
                        );
            }
        });
    });
}

function resetTable()
{
    $('#rents-table-body').find('.block-data-table-row-group').remove();
}

var groupClass = 'block-data-table-row-group';
var clearfixClass = 'clearfix';
var datainfoClass = 'data-info';
var columnClass1 = 'block-data-table-td';
var columnClass2 = 'cw-1-7';
//var columnClass3 = 'table-row-fix';
var columnClass4 = 'cw-1-4';
var columnClass5 = 'cw-1-2';
var classCenter = 'text-center';
var classRight = 'text-right';
var cssBorderTop = 'border-top';
var hiddenRowClass = 'block-data-field';

function addRow(
        odd,
        pinType,
        startDate,
        endDate,
        tripMinutes,
        parkingMinutes,
        totalAmount,
        mustPay,
        latStart,
        lonStart,
        latEnd,
        lonEnd,
        bonusMinutes,
        freeMinutes,
        addressBeginning,
        addressEnd
        ) {

    var latStartPrintable = 'n.d.';
    if (latStart.length > 6) {
        latStartPrintable = latStart.substring(0, 6);
    }
    var lonStartPrintable = 'n.d.';
    if (lonStart.length > 6) {
        lonStartPrintable = lonStart.substring(0, 6);
    }
    var latEndPrintable = 'n.d.';
    if (latEnd && latEnd.length > 6) {
        latEndPrintable = latEnd.substring(0, 6);
    }
    var lonEndPrintable = 'n.d.';
    if (lonEnd && lonEnd.length > 6) {
        lonEndPrintable = lonEnd.substring(0, 6);
    }

// create the group for all the rows in a block
    var $group = $('<div>')
            .appendTo($('#rents-table-body'));
    $group.addClass(groupClass);
    $group.addClass(clearfixClass);

// create the visible row
    var $row = $('<div>')
            .appendTo($group);
    $row.addClass('block-data-table-row');
    $row.addClass(clearfixClass);
    $row.addClass((odd) ? 'odd' : 'even');

// create the date column
    var $tripType = $('<div>')
            .appendTo($row);
    $tripType.html(pinType);
    $tripType.addClass(columnClass1);
    $tripType.addClass(columnClass2);

// create the date column
    var $startDateCol = $('<div>')
            .appendTo($row);
    $startDateCol.html(startDate);
    $startDateCol.addClass(columnClass1);
    $startDateCol.addClass(columnClass2);

// create the hour column
    var $endDateCol = $('<div>')
            .appendTo($row);
    $endDateCol.html(endDate);
    $endDateCol.addClass(columnClass1);
    $endDateCol.addClass(columnClass2);

// create the start column
    var $tripMinutesCol = $('<div>')
            .appendTo($row);
    $tripMinutesCol.html(tripMinutes);
    $tripMinutesCol.addClass(columnClass1);
    $tripMinutesCol.addClass(columnClass2);
    $tripMinutesCol.addClass(classCenter);

// create the partial amount column
    var $parkingMinutesCol = $('<div>')
            .appendTo($row);
    $parkingMinutesCol.html(parkingMinutes);
    $parkingMinutesCol.addClass(columnClass1);
    $parkingMinutesCol.addClass(columnClass2);
    $parkingMinutesCol.addClass(classCenter);

// create the total amount column
    var $totalAmountCol = $('<div>')
            .appendTo($row);
    $totalAmountCol.html(totalAmount);
    $totalAmountCol.addClass(columnClass1);
    $totalAmountCol.addClass(columnClass2);
    $totalAmountCol.addClass(classRight);

// create the total amount column
    var $mustPayCol = $('<div>')
            .appendTo($row);
    $mustPayCol.html(mustPay);
    $mustPayCol.addClass(columnClass1);
    $mustPayCol.addClass(columnClass2);
    $mustPayCol.addClass(classRight);

// create the first hidden row
    var $hiddenRow1 = $('<div>')
            .appendTo($group);
    $hiddenRow1.addClass('block-data-table-row');
    $hiddenRow1.addClass(datainfoClass);
    $hiddenRow1.addClass(clearfixClass);

// create the start address column
    var $startAddressCol = $('<div>')
            .appendTo($hiddenRow1);
    $startAddressCol.html('');
    $startAddressCol.addClass(columnClass1);
    $startAddressCol.addClass(columnClass5);

    var $startAddressSpan = $('<span>')
            .appendTo($startAddressCol);
    $startAddressSpan.html('Partenza: ');
    $startAddressSpan.addClass(hiddenRowClass);

    $startAddressCol.html($startAddressCol.html() + '<a href="#">' + addressBeginning + '</a>');
    $startAddressCol.click(function () {
        loadMapPopup(latStart, lonStart);
        return false;
    });

// create the end address column
    var $endAddressCol = $('<div>')
            .appendTo($hiddenRow1);
    $endAddressCol.html('');
    $endAddressCol.addClass(columnClass1);
    $endAddressCol.addClass(columnClass5);

    var $endAddressSpan = $('<span>')
            .appendTo($endAddressCol);
    $endAddressSpan.html('Destinazione: ');
    $endAddressSpan.addClass(hiddenRowClass);

    if (latEndPrintable != 'n.d.' && lonEndPrintable != 'n.d.') {
        $endAddressCol.html($endAddressCol.html() + '<a href="#">' + addressEnd + '</a>');
    } else {
        $endAddressCol.html($endAddressCol.html() + '' + addressEnd );
    }
    $endAddressCol.click(function () {
        loadMapPopup(latEnd, lonEnd);
        return false;
    });

    // create the second hidden row
    if (bonusMinutes !== 0 ||
            freeMinutes !== 0) {
        var $hiddenRow2 = $('<div>')
                .appendTo($group);
        $hiddenRow2.addClass('block-data-table-row');
        $hiddenRow2.addClass(datainfoClass);
        $hiddenRow2.addClass(clearfixClass);

        // create the start address column
        var $bonusMinutesCol = $('<div>')
                .appendTo($hiddenRow2);
        $bonusMinutesCol.html('');
        $bonusMinutesCol.addClass(columnClass1);
        $bonusMinutesCol.addClass(columnClass5);

        var $bonusMinutesSpan = $('<span>')
                .appendTo($bonusMinutesCol);
        $bonusMinutesSpan.html('Minuti bonus consumati: ' + bonusMinutes);
        $bonusMinutesSpan.addClass(hiddenRowClass);

        // create the end address column
        var $freeMinutesCol = $('<div>')
                .appendTo($hiddenRow2);
        $freeMinutesCol.html('');
        $freeMinutesCol.addClass(columnClass1);
        $freeMinutesCol.addClass(columnClass5);

        var $freeMinutesSpan = $('<span>')
                .appendTo($freeMinutesCol);
        $freeMinutesSpan.html('Minuti gratuiti fruiti: ' + freeMinutes);
        $freeMinutesSpan.addClass(hiddenRowClass);

    }
}

function addFinalRow(
        odd,
        totalAmount,
        mustPay
        ) {
    // create the group for all the rows in a block
    var $group = $('<div>')
            .appendTo($('#rents-table-body'));
    $group.addClass(groupClass);
    $group.addClass(clearfixClass);
    $group.addClass(cssBorderTop);

    // create the visible row
    var $row = $('<div>')
            .appendTo($group);
    $row.addClass('block-data-table-row');
    $row.addClass(clearfixClass);
    $row.addClass((odd) ? 'odd' : 'even');

    // create first column
    var $typologyCol = $('<div>')
            .appendTo($row);
    $typologyCol.html('');
    $typologyCol.addClass(columnClass1);
    $typologyCol.addClass(columnClass2);

    // create second column
    var $startDateCol = $('<div>')
            .appendTo($row);
    $startDateCol.html('');
    $startDateCol.addClass(columnClass1);
    $startDateCol.addClass(columnClass2);

    // create third column
    var $endDateCol = $('<div>')
            .appendTo($row);
    $endDateCol.html('');
    $endDateCol.addClass(columnClass1);
    $endDateCol.addClass(columnClass2);

    // create fourth column
    var $tripMinutesCol = $('<div>')
            .appendTo($row);
    $tripMinutesCol.html('');
    $tripMinutesCol.addClass(columnClass1);
    $tripMinutesCol.addClass(columnClass2);

    // create fifth column
    var $parkingMinutesCol = $('<div>')
            .appendTo($row);
    $parkingMinutesCol.html('<strong>Totali periodo</strong>');
    $parkingMinutesCol.addClass(columnClass1);
    $parkingMinutesCol.addClass(columnClass2);

    // create the total amount column
    var $totalAmountCol = $('<div>')
            .appendTo($row);
    $totalAmountCol.html('<strong>' + formatCurrency(totalAmount) + '</strong>');
    $totalAmountCol.addClass(columnClass1);
    $totalAmountCol.addClass(columnClass2);
    $totalAmountCol.addClass(classRight);

    // create the total amount column
    var $mustPayCol = $('<div>')
            .appendTo($row);
    $mustPayCol.html('<strong>' + formatCurrency(mustPay) + '</strong>');
    $mustPayCol.addClass(columnClass1);
    $mustPayCol.addClass(columnClass2);
    $mustPayCol.addClass(classRight);

}

var $mapPopup = $('#map-popup');
$mapPopup.click(function () {
    hideMapPopup();
});

function formatCurrency(value) {
    return accounting.formatMoney(value, "€ ", 2, ".", ",");
}

function loadMapPopup(lat, lng)
{
    $mapPopup.html(
            '<img id="map-popup-img" src="' +
            'https://www.google.it/maps/api/staticmap?center=' +
            lat + ',' + lng +
            '&zoom=16&sensor=false&size=800x600&markers=color:green%7C' +
            lat + ',' + lng +
            '" class="map-popup-img">');
    $mapPopup.show();
}

function hideMapPopup()
{
    $mapPopup.hide();
}
