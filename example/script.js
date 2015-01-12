var App = function () {


    /*-----------------------------------------------------------------------------------*/
    /*	Date Range Picker
     /*-----------------------------------------------------------------------------------*/
    var handleDateTimePickers = function () {
       
        $('#reportrange').daterangepicker(
                {
                    startDate: moment().subtract('days', 29),
                    endDate: moment(),
                    minDate: '01/01/2012',
                    maxDate: '12/31/2014',
                    dateLimit: {days: 60},
                    showDropdowns: true,
                    showWeekNumbers: true,
                    timePicker: false,
                    timePickerIncrement: 1,
                    timePicker12Hour: true,
                    ranges: {
                        'Ontem': [moment().subtract('days', 1), moment().subtract('days', 1)],
                        'Últimos 30 dias': [moment().subtract('days', 29), moment()],
                        'Esse mês': [moment().startOf('month'), moment().endOf('month')]
                    },
                    opens: 'left',
                    buttonClasses: ['btn btn-default'],
                    applyClass: 'btn-small btn-primary',
                    cancelClass: 'btn-small cancel_filter',
                    cancelId: 'cancel_filter',
                    format: 'DD/MM/YYYY',
                    separator: ' para ',
                    locale: {
                        applyLabel: 'Filtrar',
                        cancelLabel: 'Cancelar',
                        fromLabel: 'De',
                        toLabel: 'Até',
                        customRangeLabel: 'Personalizado',
                        daysOfWeek: ['Dom', 'Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb'],
                        monthNames: ['Janeiro', 'Fevereiro', 'Março', 'Abril', 'Maio', 'Junho', 'Julho', 'Agosto', 'Setembro', 'Outubro', 'Novembro', 'Dezembro'],
                        firstDay: 1
                    }
                },
        function (start, end) {
            console.log("Callback has been called!");
            $('#reportrange span').html(start.format('D MMMM, YYYY') + ' - ' + end.format('D MMMM, YYYY'));
        }
        );
        //Set the initial state of the picker label
        $('#reportrange span').html('Data');
    };

    return {
        //Initialise theme pages
        init: function () {

            if (App.isPage("index")) {
                handleDateTimePickers(); //Function to display Date Timepicker
                handleSparkline();		//Function to display Sparkline charts
                handleDashFlotCharts(); //Function to display flot charts in dashboard
            }
        },
        //Set page
        setPage: function (name) {
            currentPage = name;
        },
        isPage: function (name) {
            return currentPage === name ? true : false;
        }
       
    };
}();
