@extends('layouts.app')
@section('title', __('Cheque Management'))

@section('content')

    <!-- Content Header (Page header) -->
    <section class="content-header">
        <h1>@lang('Cheque Management')</h1>
    </section>

    <!-- Main content -->
    <section class="content">
        <div class="row">
            <div class="col-md-12">
                @component('components.filters', ['title' => __('report.filters')])
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Cheque number:</label>
                            <div class="input-group input-group-sm">
                                <input id="cheque_number" name="cheque_number" value="" type="text" class="form-control">
                                <span class="input-group-btn">
                                    <button id="cheque_number_search_button" type="button" class="btn btn-info btn-flat"><i
                                            class="fa fa-search"></i></button>
                                </span>
                            </div>
                            <!-- /input-group -->
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('contact_id', __('contact.contact') . ':') !!}
                            {!! Form::select('contact_id', $contacts, null, [
                                'class' => 'form-control select2',
                                'id' => 'contact_id',
                                'style' => 'width:100%',
                                'placeholder' => __('lang_v1.all'),
                            ]) !!}
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Cheque Realize Date:</label>
                            <div class="form-group">
                                {!! Form::text('cheques_filter_date_range', null, [
                                    'placeholder' => __('lang_v1.select_a_date_range'),
                                    'class' => 'form-control',
                                    'id' => 'cheques_filter_date_range',
                                    'readonly',
                                ]) !!}
                            </div>
                            <!-- /.input group -->
                        </div>
                        <!-- /.form group -->
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            {!! Form::label('account_id', __('lang_v1.payment_account') . ':') !!}
                            {!! Form::select('account_id', $accounts, null, [
                                'class' => 'form-control select2',
                                'id' => 'account_id',
                                'style' => 'width:100%',
                            ]) !!}
                        </div>
                    </div>
                @endcomponent
            </div>
        </div>
        <div class="row">
            <div class="col-md-12">
                @component('components.widget', ['class' => 'box-primary', 'title' => __('All Cheques')])
                    @can('account.access')
                        @slot('tool')
                            <div class="box-tools">
                                <a class="btn btn-block btn-primary add-cheque" href="{{ action('ChequesController@create') }}">
                                    <i class="fa fa-plus"></i> @lang('messages.add')</a>
                            </div>
                        @endslot
                    @endcan
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped" id="cheque_table">
                            <thead>
                                <tr>
                                    <th><input type="checkbox" id="select-all-cheques" data-table-id="cheque_table"></th>
                                    <th>@lang('messages.action')</th>
                                    <th>@lang('account.account_name')</th>
                                    <th>@lang('Cheque Number')</th>
                                    <th>Cheque Issue Date</th>
                                    <th>Cheque Realize Date</th>
                                    <th>@lang('sale.total_amount')</th>
                                    <th>@lang('Cheque Status')</th>
                                    <th>@lang('Cheque Type')</th>
                                    {{--                                <th>@lang('account.return_fee')</th> --}}
                                </tr>
                            </thead>
                            <tfoot>
                                <tr class="bg-gray font-17 text-center footer-total">
                                    <td colspan="6"><strong>@lang('sale.total'):</strong></td>
                                    <td class="cheque_table_total"></td>
                                    <td colspan="2"></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <div style="display: flex; width: 100%; margin-top: 10px;">
                        {!! Form::open([
                            'url' => action('ChequesController@massDestroy'),
                            'method' => 'post',
                            'id' => 'mass_delete_form',
                        ]) !!}
                        {!! Form::hidden('selected_deleted_rows', null, ['id' => 'selected_deleted_rows']) !!}
                        {!! Form::submit(__('lang_v1.delete_selected'), ['class' => 'btn btn-xs btn-danger', 'id' => 'delete-selected']) !!}
                        {!! Form::close() !!}

                        &nbsp;
                        {!! Form::open([
                            'url' => action('ChequesController@massReturn'),
                            'method' => 'post',
                            'id' => 'mass_return_form',
                        ]) !!}
                        {!! Form::hidden('selected_return_rows', null, ['id' => 'selected_return_rows']) !!}
                        {!! Form::submit(__('Mark As Return'), ['class' => 'btn btn-xs btn-primary', 'id' => 'mark-as-return-selected']) !!}
                        {!! Form::close() !!}
                    </div>
                @endcomponent
            </div>
        </div>

    </section>
    <!-- /.content -->
    <!-- /.content -->
    <div class="modal fade" id="modal-default"></div><!-- /.modal -->
@stop
@section('javascript')
    <script>
        $(document).ready(function() {
            //Date range as a button
            dateRangeSettings.startDate = moment().startOf('year'); 
            dateRangeSettings.endDate = moment().endOf('year');
            $('#cheques_filter_date_range').daterangepicker(
                dateRangeSettings,
                function(start, end) {
                    $('#cheques_filter_date_range').val(start.format(moment_date_format) + ' ~ ' + end.format(
                        moment_date_format));
                    cheque_table.ajax.reload();
                }
            );
            $('#cheques_filter_date_range').on('cancel.daterangepicker', function(ev, picker) {
                $('#cheques_filter_date_range').val('');
                cheque_table.ajax.reload();
            });

            cheque_table = $('#cheque_table').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    method: 'GET',
                    url: "{{ action('ChequesController@index') }}",
                    data: function(d) {
                        d.cheque_number = $('#cheque_number').val();
                        d.contact_id = $('#contact_id').val();
                        d.account_id = $('#account_id').val();

                        if ($('#cheques_filter_date_range').val()) {
                            var start = $('#cheques_filter_date_range').data('daterangepicker')
                                .startDate.format('YYYY-MM-DD');
                            var end = $('#cheques_filter_date_range').data('daterangepicker').endDate
                                .format('YYYY-MM-DD');
                            d.start_date = start;
                            d.end_date = end;
                        }
                    }
                },
                columnDefs: [{
                    "targets": [0, 1, 2],
                    "orderable": false,
                    'searchable': false
                }],
                "footerCallback": function(row, data, start, end, display) {
                    var api = this.api(),
                        data;
                    // converting to interger to find total
                    var intVal = function(i) {
                        return typeof i === 'string' ?
                            i.replace(/[\$,]/g, '') * 1 :
                            typeof i === 'number' ?
                            i : 0;
                    };

                    // final_total
                    // let final_total = api.column(1).data().reduce(function (a, b) {
                    //     return intVal(a) + intVal(b);
                    // }, 0);
                    //
                    // $(api.column(1).footer()).html(numFormat(final_total));

                },
                columns: [{
                        data: 'mass_delete'
                    },
                    {
                        name: 'action',
                        data: 'action',
                        searchable: false
                    },
                    {
                        name: 'name',
                        data: 'name',
                        searchable: false
                    },
                    {
                        name: 'cheque_number',
                        data: 'cheque_number'
                    },
                    {
                        name: 'cheque_issued_date',
                        data: 'cheque_issued_date'
                    },
                    {
                        name: 'cheque_date',
                        data: 'cheque_date'
                    },
                    {
                        name: 'cheque_amount',
                        data: 'cheque_amount'
                    },
                    {
                        name: 'cheque_status',
                        data: 'cheque_status'
                    },
                    {
                        name: 'cheque_type',
                        data: 'cheque_type'
                    },
                    // {name: 'cheque_return_fee', data: 'cheque_return_fee'}
                ],
                "fnDrawCallback": function(oSettings) {
                    __currency_convert_recursively($('#cheque_table'));
                },
                "footerCallback": function(row, data, start, end, display) {
                    var cheque_table_total = 0;
                    var cheque_table_return_total = 0;
                    for (var r in data) {
                        cheque_table_total += $(data[r].cheque_amount).data('orig-value') ? parseFloat(
                            $(data[r].cheque_amount).data('orig-value')) : 0;
                        cheque_table_return_total += $(data[r].cheque_return_amount).data(
                                'orig-value') ?
                            parseFloat(
                                $(data[r].cheque_return_amount).data('orig-value')) : 0;
                    }
                    var final = cheque_table_total - cheque_table_return_total;
                    $('.cheque_table_total').html(__currency_trans_from_en(final) +
                        '<br><b><small style="font-size: 12px;">(Total Paid- Total Return)</small>');
                },
            });

            // let numFormat = $.fn.dataTable.render.number('\,', '.', 2, '').display;

            $(document).on('click', '#cheque_number_search_button', function() {
                cheque_table.ajax.reload();
            });
            $(document).on('change', '#contact_id, #datepicker, #account_id', function() {
                cheque_table.ajax.reload();
            });

            $('table#cheque_table tbody').on('click', 'a.mark-as-paid', function(e) {
                e.preventDefault();
                swal({
                    title: LANG.sure,
                    icon: "warning",
                    buttons: true,
                    dangerMode: true,
                }).then((willDelete) => {
                    if (willDelete) {
                        var href = $(this).attr('href');
                        $.ajax({
                            method: "GET",
                            url: href,
                            success: function(result) {
                                if (result.success === true) {
                                    toastr.success(result.msg);
                                    cheque_table.ajax.reload();
                                } else {
                                    toastr.error(result.msg);
                                }
                            }
                        });
                    }
                });
            });

            $('table#cheque_table tbody').on('click', 'a.mark-as-returned', function(e) {
                e.preventDefault();
                swal({
                    title: LANG.sure,
                    icon: "warning",
                    buttons: true,
                    dangerMode: true,
                }).then((willDelete) => {
                    if (willDelete) {
                        var href = $(this).attr('href');
                        $.ajax({
                            method: "GET",
                            url: href,
                            success: function(result) {
                                if (result.success === true) {
                                    toastr.success(result.msg);
                                    cheque_table.ajax.reload();
                                } else {
                                    toastr.error(result.msg);
                                }
                            }
                        });
                    }
                });
            });

            $('table#cheque_table tbody').on('click', 'a.delete-cheque', function(e) {
                e.preventDefault();
                swal({
                    title: LANG.sure,
                    icon: "warning",
                    buttons: true,
                    dangerMode: true,
                }).then((willDelete) => {
                    if (willDelete) {
                        var href = $(this).attr('href');
                        $.ajax({
                            method: "DELETE",
                            url: href,
                            success: function(result) {
                                if (result.success === true) {
                                    toastr.success(result.msg);
                                    cheque_table.ajax.reload();
                                } else {
                                    toastr.error(result.msg);
                                }
                            }
                        });
                    }
                });
            });

            $('table#cheque_table tbody').on('click', 'a.view-cheque', function(e) {
                e.preventDefault();
                var href = $(this).attr('href');
                $('#modal-default').load(href, function() {
                    $('#modal-default').modal('show');
                });
            });

            $(document).on('click', '#select-all-cheques', function(e) {
                var table_id = $(this).data('table-id');
                if (this.checked) {
                    $('#' + table_id)
                        .find('tbody')
                        .find('input.row-select')
                        .each(function() {
                            if (!this.checked) {
                                $(this)
                                    .prop('checked', true)
                                    .change();
                            }
                        });
                } else {
                    $('#' + table_id)
                        .find('tbody')
                        .find('input.row-select')
                        .each(function() {
                            if (this.checked) {
                                $(this)
                                    .prop('checked', false)
                                    .change();
                            }
                        });
                }
            });

            $(document).on('click', '#delete-selected', function(e) {
                e.preventDefault();
                var selected_deleted_rows = getSelectedRows();

                if (selected_deleted_rows.length > 0) {
                    $('input#selected_deleted_rows').val(selected_deleted_rows);
                    swal({
                        title: LANG.sure,
                        icon: "warning",
                        buttons: true,
                        dangerMode: true,
                    }).then((willDelete) => {
                        if (willDelete) {
                            $('form#mass_delete_form').submit();
                        }
                    });
                } else {
                    $('input#selected_deleted_rows').val('');
                    swal('@lang('lang_v1.no_row_selected')');
                }
            });

            $(document).on('click', '#mark-as-return-selected', function(e) {
                e.preventDefault();
                var selected_return_rows = getSelectedRows();

                if (selected_return_rows.length > 0) {
                    $('input#selected_return_rows').val(selected_return_rows);
                    swal({
                        title: LANG.sure,
                        icon: "warning",
                        buttons: true,
                        dangerMode: true,
                    }).then((willDelete) => {
                        if (willDelete) {
                            $('form#mass_return_form').submit();
                        }
                    });
                } else {
                    $('input#selected_return_rows').val('');
                    swal('@lang('lang_v1.no_row_selected')');
                }
            });
        });

        function getSelectedRows() {
            var selected_rows = [];
            var i = 0;
            $('.row-select:checked').each(function() {
                selected_rows[i++] = $(this).val();
            });

            return selected_rows;
        }
    </script>
@endsection
