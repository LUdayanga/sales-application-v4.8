@extends('layouts.app')
@section('title', __('Bulk Sell Return'))

@section('content')

    <!-- Content Header (Page header) -->
    <section class="content-header">
        <h1>Bulk Sell Return
            <small></small>
        </h1>
    </section>

    <!-- Main content -->
    <section class="content">
        @component('components.filters', ['title' => __('report.filters')])
            <div class="col-md-4">
                <div class="form-group">
                    {!! Form::label('filter_customer_id', __('contact.customer') . ':') !!}
                    {!! Form::select('filter_customer_id', $customers, null, [
                        'class' => 'form-control select2',
                        'style' => 'width:100%',
                        'placeholder' => __('lang_v1.all'),
                    ]) !!}
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('filter_date_range', __('report.date_range') . ':') !!}
                    {!! Form::text('filter_date_range', null, [
                        'placeholder' => __('lang_v1.select_a_date_range'),
                        'class' => 'form-control',
                        'readonly',
                    ]) !!}
                </div>
            </div>
        @endcomponent

        @component('components.widget', [
            'class' => 'box-primary',
            'title' => __('All bulk sell return'),
        ])
            <div class="table-responsive">
                <table class="table table-bordered table-striped ajax_view" id="sell_bulk_return_table">
                    <thead>
                        <tr>
                            <th>@lang('messages.action')</th>
                            <th>@lang('messages.date')</th>
                            <th>@lang('purchase.ref_no')</th>
                            <th>@lang('Customer')</th>
                            <th>@lang('business.location')</th>
                            <th>@lang('Payment Status')</th>
                            <th>@lang('Total Return')</th>
                            <th>@lang('Total Refund')</th>
                            <th>@lang('Payment Due')</th>
                            <th>@lang('lang_v1.added_by')</th>
                        </tr>
                    </thead>
                </table>
            </div>
        @endcomponent

    </section>
    <!-- /.content -->
    <div class="modal fade payment_modal" tabindex="-1" role="dialog" 
    aria-labelledby="gridSystemModalLabel">
</div>

<div class="modal fade edit_payment_modal" tabindex="-1" role="dialog" 
    aria-labelledby="gridSystemModalLabel">
</div>
@stop
@section('javascript')
    <script>
        $(document).ready(function() {
            //Date range as a button
            dateRangeSettings.startDate = moment().startOf('year'); 
            dateRangeSettings.endDate = moment().endOf('year');
            $('#filter_date_range').daterangepicker(
                dateRangeSettings,
                function(start, end) {
                    $('#filter_date_range').val(start.format(moment_date_format) + ' ~ ' + end.format(
                        moment_date_format));
                    sell_bulk_return_table.ajax.reload();
                }
            );
            $('#filter_date_range').on('cancel.daterangepicker', function(ev, picker) {
                $('#filter_date_range').val('');
                sell_bulk_return_table.ajax.reload();
            });

            sell_bulk_return_table = $('#sell_bulk_return_table').DataTable({
                processing: true,
                serverSide: true,
                ajax: '/bulk-sell-return',
                "ajax": {
                    "url": "/bulk-sell-return",
                    "data": function(d) {
                        if ($('#filter_date_range').val()) {
                            var start = $('#filter_date_range').data('daterangepicker')
                                .startDate.format('YYYY-MM-DD');
                            var end = $('#filter_date_range').data('daterangepicker').endDate
                                .format('YYYY-MM-DD');
                            d.start_date = start;
                            d.end_date = end;
                        }
                        d.contact_id = $('#filter_customer_id').val();
                        d.condition = $('#status_filter').val();
                        d = __datatable_ajax_callback(d);
                    }
                },
                columnDefs: [{
                    targets: 0,
                    orderable: false,
                    searchable: false,
                }, ],
                aaSorting: [
                    [1, 'desc']
                ],
                columns: [{
                        data: 'action',
                        name: 'action'
                    },
                    {
                        data: 'transaction_date',
                        name: 'transaction_date'
                    },
                    {
                        data: 'ref_no',
                        name: 'ref_no'
                    },
                    {
                        data: 'customer',
                        name: 'customer'
                    },
                    {
                        data: 'location_name',
                        name: 'BL.name'
                    },
                    {
                        data: 'payment_status',
                        name: 'payment_status'
                    },
                    {
                        data: 'total_return',
                        name: 'total_return'
                    },
                    {
                        data: 'total_refund',
                        name: 'total_refund'
                    },
                    {
                        data: 'payment_due',
                        name: 'payment_due'
                    },
                    {
                        data: 'added_by',
                        name: 'u.first_name'
                    },
                ],
                fnDrawCallback: function(oSettings) {
                    __currency_convert_recursively($('#sell_bulk_return_table'));
                },
            });

            // filter dropdown change
            $(document).on('change', '#status_filter, #filter_customer_id', function() {
                sell_bulk_return_table.ajax.reload();
            });

            $(document).on('click', 'a.delete_stock_adjustment', function(e) {
                e.preventDefault();
                swal({
                    title: LANG.sure,
                    icon: 'warning',
                    buttons: true,
                    dangerMode: true,
                }).then(willDelete => {
                    if (willDelete) {
                        var href = $(this).data('href');
                        $.ajax({
                            method: 'DELETE',
                            url: href,
                            dataType: 'json',
                            success: function(result) {
                                if (result.success) {
                                    toastr.success(result.msg);
                                    sell_bulk_return_table.ajax.reload();
                                } else {
                                    toastr.error(result.msg);
                                }
                            },
                        });
                    }
                });
            });

            $(document).on('click', 'a.add_payment_modal', function(e) {
                e.preventDefault();
                var container = $('.payment_modal');

                $.ajax({
                    url: $(this).data('href'),
                    dataType: 'json',
                    success: function(result) {
                        if (result.status == 'due') {
                            container.html(result.view).modal('show');
                            __currency_convert_recursively(container);
                            $('#paid_on').datetimepicker({
                                format: moment_date_format + ' ' + moment_time_format,
                                ignoreReadonly: true,
                            });
                            container.find('form#transaction_payment_add_form').validate();
                            set_default_payment_account();

                            $('.payment_modal')
                                .find('input[type="checkbox"].input-icheck')
                                .each(function() {
                                    $(this).iCheck({
                                        checkboxClass: 'icheckbox_square-blue',
                                        radioClass: 'iradio_square-blue',
                                    });
                                });
                        } else {
                            toastr.error(result.msg);
                        }
                    },
                });
            });

            $(document).on('click', '.view_payment_modal', function(e) {
        e.preventDefault();
        var container = $('.payment_modal');

        $.ajax({
            url: $(this).data('href'),
            dataType: 'html',
            success: function(result) {
                $(container)
                    .html(result)
                    .modal('show');
                __currency_convert_recursively(container);
            },
        });
    });

        });
    </script>
@endsection
