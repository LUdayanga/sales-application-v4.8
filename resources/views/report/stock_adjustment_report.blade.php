@extends('layouts.app')
@section('title', __('report.stock_adjustment_report'))

@section('content')

    <!-- Content Header (Page header) -->
    <section class="content-header">
        <h1>@lang('report.stock_adjustment_report')
        </h1>
    </section>

    <!-- Main content -->
    <section class="content">
        <div class="row">
            <div class="col-md-3 col-md-offset-7 col-xs-6">
                <div class="input-group">
                    <span class="input-group-addon bg-light-blue"><i class="fa fa-map-marker"></i></span>
                    <select class="form-control select2" id="stock_adjustment_location_filter">
                        @foreach ($business_locations as $key => $value)
                            <option value="{{ $key }}">{{ $value }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="col-md-2 col-xs-6">
                <div class="form-group pull-right">
                    <div class="input-group">
                        <button type="button" class="btn btn-primary" id="stock_adjustment_date_filter">
                            <span>
                                <i class="fa fa-calendar"></i> {{ __('messages.filter_by_date') }}
                            </span>
                            <i class="fa fa-caret-down"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <br>
        <div class="row">
            <div class="col-sm-6">
                @component('components.widget')
                    <table class="table no-border">
                        <tr>
                            <th>{{ __('report.total_normal') }}:</th>
                            <td>
                                <span class="total_normal">
                                    <i class="fas fa-sync fa-spin fa-fw"></i>
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th>{{ __('report.total_abnormal') }}:</th>
                            <td>
                                <span class="total_abnormal">
                                    <i class="fas fa-sync fa-spin fa-fw"></i>
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th>{{ __('report.total_stock_adjustment') }}:</th>
                            <td>
                                <span class="total_amount">
                                    <i class="fas fa-sync fa-spin fa-fw"></i>
                                </span>
                            </td>
                        </tr>
                    </table>
                @endcomponent
            </div>

            <div class="col-sm-6">
                @component('components.widget')
                    <table class="table no-border">
                        <tr>
                            <th>{{ __('report.total_recovered') }}:</th>
                            <td>
                                <span class="total_recovered">
                                    <i class="fas fa-sync fa-spin fa-fw"></i>
                                </span>
                            </td>
                        </tr>

                        @if ($super_admin->advance_stock_adjustment != 0)
                            <tr>
                                <th>Total Increase:</th>
                                <td>
                                    <span class="total_increase">
                                        <i class="fas fa-sync fa-spin fa-fw"></i>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <th>Total Decrease:</th>
                                <td>
                                    <span class="total_descrease">
                                        <i class="fas fa-sync fa-spin fa-fw"></i>
                                    </span>
                                </td>
                            </tr>
                        @else
                            <tr>
                                <td>&nbsp;</td>
                            </tr>
                            <tr>
                                <td>&nbsp;</td>
                            </tr>
                        @endif

                    </table>
                @endcomponent
            </div>
        </div>

        <div class="row">
            <div class="col-sm-12">
                @component('components.widget', ['class' => 'box-primary', 'title' => __('stock_adjustment.stock_adjustments')])
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="stock_adjustment_table">
                            <thead>
                                <tr>
                                    <th>@lang('messages.action')</th>
                                    <th>@lang('messages.date')</th>
                                    <th>@lang('purchase.ref_no')</th>
                                    <th>@lang('business.location')</th>
                                    <th>@lang('stock_adjustment.adjustment_type')</th>
                                    <th>@lang('stock_adjustment.total_amount')</th>
                                    <th>@lang('stock_adjustment.total_amount_recovered')</th>
                                    <th>@lang('stock_adjustment.reason_for_stock_adjustment')</th>
                                    <th>@lang('lang_v1.added_by')</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                @endcomponent
            </div>
        </div>

        @if ($super_admin->advance_stock_adjustment != 0)
                <div class="col-sm-12">
                    @component('components.widget', ['class' => 'box-danger', 'title' => 'Stock Adjustment Variations'])
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped" id="stock_adjustment_variations">
                                <thead>
                                    <tr>
                                        <th>@lang('product.product_name')</th>
                                        <th>@lang('product.sku')</th>
                                        <th>Total Increase</th>
                                        <th>Total Decrease</th>
                                        <th>Amount <small>(total increase - total decrease)</small></th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    @endcomponent
                </div>
            </div>
        @endif

    </section>
    <!-- /.content -->
@stop
@section('javascript')
    <script src="{{ asset('js/stock_adjustment.js?v=' . $asset_v) }}"></script>
    <script src="{{ asset('js/report.js?v=' . $asset_v) }}"></script>

    <script>
        $(document).ready(function() {
            var stock_adjustment_variations = $('#stock_adjustment_variations').DataTable({
                processing: true,
                serverSide: true,
                ajax: '/stock-adjustments-products',
                buttons: [],
                "ajax": {
                    "url": "/stock-adjustments-products",
                    "data": function(d) {
                        d.location_id = $('#stock_adjustment_location_filter').val();

                        // var start = $('#stock_adjustment_date_filter')
                        //     .data('daterangepicker')
                        //     .startDate.format('YYYY-MM-DD');
                        // var end = $('#stock_adjustment_date_filter')
                        //     .data('daterangepicker')
                        //     .endDate.format('YYYY-MM-DD');
                        // d.start_date = start;
                        // d.end_date = end;

                        d = __datatable_ajax_callback(d);
                    }
                },

                columns: [{
                        data: 'name',
                        name: 'name'
                    },
                    {
                        data: 'sku',
                        name: 'sku'
                    },
                    {
                        data: 'total_increase',
                        name: 'total_increase'
                    },
                    {
                        data: 'total_decrease',
                        name: 'total_decrease'
                    },
                    {
                        data: 'amount',
                        name: 'amount'
                    },
                ],
            });

            // filter dropdown change
            $(document).on('change', '#stock_adjustment_location_filter', function() {
                stock_adjustment_variations.ajax.reload();
            });

            //date filter
            if ($('#stock_adjustment_date_filter').length == 1) {
                $('#stock_adjustment_date_filter').daterangepicker(dateRangeSettings, function(start, end) {
                    $('#stock_adjustment_date_filter span').html(
                        start.format(moment_date_format) + ' ~ ' + end.format(moment_date_format)
                    );
                    stock_adjustment_variations.ajax.reload();
                });
            }

        });
    </script>
@endsection
