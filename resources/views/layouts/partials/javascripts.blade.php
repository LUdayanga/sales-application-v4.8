<script type="text/javascript">
    base_path = "{{ url('/') }}";
    //used for push notification
    APP = {};
    APP.PUSHER_APP_KEY = '{{ config('broadcasting.connections.pusher.key') }}';
    APP.PUSHER_APP_CLUSTER = '{{ config('broadcasting.connections.pusher.options.cluster') }}';
    APP.INVOICE_SCHEME_SEPARATOR = '{{ config('constants.invoice_scheme_separator') }}';
    //variable from app service provider
    APP.PUSHER_ENABLED = '{{ $__is_pusher_enabled }}';
    @auth
    @php
        $user = Auth::user();
    @endphp
    APP.USER_ID = "{{ $user->id }}";
    @else
        APP.USER_ID = '';
    @endauth
</script>

<!--[if lt IE 9]>
<script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js?v=$asset_v"></script>
<script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js?v=$asset_v"></script>
<![endif]-->
<script src="{{ asset('js/vendor.js?v=' . $asset_v) }}"></script>

{{-- load login image --}}
<script>
    $.ajax({
        type: "GET",
        // url: "https://notifications.clickypos.com/api/get-login-image",
        url: "/set-login-image",
        success: function(result) {
            $(".left-col").css("background", "url(" + result + ")");
            $(".left-col").css("background-repeat", "no-repeat");
            $(".left-col").css("background-size", "cover");
            // $(".left-col").css("background-size", "100% 100%");
            $(".left-col").css("background-position", "center");
        },
        error: function(xhr, status, error) {
            var url = "/img/home-bg.jpg"
            $(".left-col").css("background", "url(" + url + ")");
            $(".left-col").css("background-repeat", "no-repeat");
            $(".left-col").css("background-size", "cover");
            $(".left-col").css("background-position", "center");
        }
    });
</script>

@if (file_exists(public_path('js/lang/' . session()->get('user.language', config('app.locale')) . '.js')))
    <script src="{{ asset('js/lang/' . session()->get('user.language', config('app.locale')) . '.js?v=' . $asset_v) }}">
    </script>
@else
    <script src="{{ asset('js/lang/en.js?v=' . $asset_v) }}"></script>
@endif
@php
    $business_date_format = session('business.date_format', config('constants.default_date_format'));
    $datepicker_date_format = str_replace('d', 'dd', $business_date_format);
    $datepicker_date_format = str_replace('m', 'mm', $datepicker_date_format);
    $datepicker_date_format = str_replace('Y', 'yyyy', $datepicker_date_format);
    
    $moment_date_format = str_replace('d', 'DD', $business_date_format);
    $moment_date_format = str_replace('m', 'MM', $moment_date_format);
    $moment_date_format = str_replace('Y', 'YYYY', $moment_date_format);
    
    $business_time_format = session('business.time_format');
    $moment_time_format = 'HH:mm';
    if ($business_time_format == 12) {
        $moment_time_format = 'hh:mm A';
    }
    
    $business_start_date = session('business.start_date', config('constants.default_date_format'));
    
    $common_settings = !empty(session('business.common_settings')) ? session('business.common_settings') : [];
    
    $default_datatable_page_entries = !empty($common_settings['default_datatable_page_entries']) ? $common_settings['default_datatable_page_entries'] : 25;
@endphp

<script>
    Dropzone.autoDiscover = false;
    moment.tz.setDefault('{{ Session::get('business.time_zone') }}');
    $(document).ready(function() {
        $.ajaxSetup({
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });

        @if (config('app.debug') == false)
            $.fn.dataTable.ext.errMode = 'throw';
        @endif
    });

    var financial_year = {
        start: moment('{{ Session::get('financial_year.start') }}'),
        end: moment('{{ Session::get('financial_year.end') }}'),
    }
    @if (file_exists(public_path('AdminLTE/plugins/select2/lang/' . session()->get('user.language', config('app.locale')) . '.js')))
        //Default setting for select2
        $.fn.select2.defaults.set("language", "{{ session()->get('user.language', config('app.locale')) }}");
    @endif

    var datepicker_date_format = "{{ $datepicker_date_format }}";
    var moment_date_format = "{{ $moment_date_format }}";
    var moment_time_format = "{{ $moment_time_format }}";
    var business_start_date = "{{ $business_start_date }}";

    var app_locale = "{{ session()->get('user.language', config('app.locale')) }}";

    var non_utf8_languages = [
        @foreach (config('constants.non_utf8_languages') as $const)
            "{{ $const }}",
        @endforeach
    ];

    var __default_datatable_page_entries = "{{ $default_datatable_page_entries }}";

    var __new_notification_count_interval = "{{ config('constants.new_notification_count_interval', 60) }}000";
</script>

@if (file_exists(public_path('js/lang/' . session()->get('user.language', config('app.locale')) . '.js')))
    <script src="{{ asset('js/lang/' . session()->get('user.language', config('app.locale')) . '.js?v=' . $asset_v) }}">
    </script>
@else
    <script src="{{ asset('js/lang/en.js?v=' . $asset_v) }}"></script>
@endif

<script src="{{ asset('js/functions.js?v=' . $asset_v) }}"></script>
<script src="{{ asset('js/common.js?v=' . $asset_v) }}"></script>
<script src="{{ asset('js/app.js?v=' . $asset_v) }}"></script>
<script src="{{ asset('js/help-tour.js?v=' . $asset_v) }}"></script>
<script src="{{ asset('js/documents_and_note.js?v=' . $asset_v) }}"></script>

<script>
    //Default settings for daterangePicker
    var ranges = {};
    ranges[LANG.today] = [moment(), moment()];
    ranges[LANG.yesterday] = [moment().subtract(1, 'days'), moment().subtract(1, 'days')];
    ranges[LANG.last_7_days] = [moment().subtract(6, 'days'), moment()];
    ranges[LANG.last_30_days] = [moment().subtract(29, 'days'), moment()];
    ranges[LANG.this_month] = [moment().startOf('month'), moment().endOf('month')];
    ranges[LANG.last_month] = [
        moment()
        .subtract(1, 'month')
        .startOf('month'),
        moment()
        .subtract(1, 'month')
        .endOf('month'),
    ];
    ranges[LANG.this_month_last_year] = [
        moment()
        .subtract(1, 'year')
        .startOf('month'),
        moment()
        .subtract(1, 'year')
        .endOf('month'),
    ];
    ranges[LANG.this_year] = [moment().startOf('year'), moment().endOf('year')];
    ranges[LANG.last_year] = [
        moment().startOf('year').subtract(1, 'year'),
        moment().endOf('year').subtract(1, 'year')
    ];
    ranges[LANG.this_financial_year] = [financial_year.start, financial_year.end];
    ranges[LANG.last_financial_year] = [
        moment(financial_year.start._i).subtract(1, 'year'),
        moment(financial_year.end._i).subtract(1, 'year')
    ];

    ranges['Since Business Start'] = [moment(business_start_date), moment()];

    var dateRangeSettings = {
        ranges: ranges,
        startDate: financial_year.start,
        endDate: financial_year.end,
        locale: {
            cancelLabel: LANG.clear,
            applyLabel: LANG.apply,
            customRangeLabel: LANG.custom_range,
            format: moment_date_format,
            toLabel: '~',
        },
    };
</script>

<!-- TODO -->
@if (file_exists(public_path('AdminLTE/plugins/select2/lang/' . session()->get('user.language', config('app.locale')) . '.js')))
    <script
        src="{{ asset('AdminLTE/plugins/select2/lang/' . session()->get('user.language', config('app.locale')) . '.js?v=' . $asset_v) }}">
    </script>
@endif
@php
    $validation_lang_file = 'messages_' . session()->get('user.language', config('app.locale')) . '.js';
@endphp
@if (file_exists(public_path() . '/js/jquery-validation-1.16.0/src/localization/' . $validation_lang_file))
    <script src="{{ asset('js/jquery-validation-1.16.0/src/localization/' . $validation_lang_file . '?v=' . $asset_v) }}">
    </script>
@endif

@if (!empty($__system_settings['additional_js']))
    {!! $__system_settings['additional_js'] !!}
@endif
@yield('javascript')

@if (Module::has('Essentials'))
    @includeIf('essentials::layouts.partials.footer_part')
@endif

<script type="text/javascript">
    $(document).ready(function() {

        var locale = "{{ session()->get('user.language', config('app.locale')) }}";
        var isRTL =
            @if (in_array(session()->get('user.language', config('app.locale')), config('constants.langs_rtl')))
                true;
            @else
                false;
            @endif

        $('#calendar').fullCalendar('option', {
            locale: locale,
            isRTL: isRTL
        });

        var intervalId = window.setInterval(function() {
            $("#sms_refresh_icon").attr("class", "fas fa-sync fa-spin");
            $.ajax({
                url: '/sms-balance',
                success: function(result) {
                    if ($.trim(result)) {
                        var js = JSON.parse(result);
                        if (js.hasOwnProperty('balance')) {
                            $("#sms_balance").text(js.balance + " Messeges available");
                            $("#sms_refresh_icon").attr("class", "fas fa-sync");
                        } else {
                            $("#sms_balance").text('');
                            $("#sms_refresh_icon").attr("class", "fas fa-comment-slash");
                            $("[data-toggle=tooltip]").mouseenter(function() {
                                var $this = $(this);
                                $this.attr('title', 'SMS Service Not Available');
                            });
                        }
                    } else {
                        $("#sms_balance").text('');
                        $("#sms_refresh_icon").attr("class", "fas fa-comment-slash");
                        $("[data-toggle=tooltip]").mouseenter(function() {
                            var $this = $(this);
                            $this.attr('title', 'SMS Service Not Available');
                        });
                    }

                }
            });
            // smBalance();
        }, 60 * 60 * 1000);

        //If the customer hasn't taken the SMS service
        $("#btn_no_sms").click(function() {
            $('#sms_banner_modal').modal();
        });

    });
</script>
