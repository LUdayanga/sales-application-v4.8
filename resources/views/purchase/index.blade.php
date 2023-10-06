@extends('layouts.app')
@section('title', __('purchase.purchases'))

@section('content')

    <!-- Content Header (Page header) -->
    <section class="content-header no-print">
        <h1>@lang('purchase.purchases')
            <small></small>
        </h1>
        <!-- <ol class="breadcrumb">
                <li><a href="#"><i class="fa fa-dashboard"></i> Level</a></li>
                <li class="active">Here</li>
            </ol> -->
    </section>

    <!-- Main content -->
    <section class="content no-print">
        @component('components.filters', ['title' => __('report.filters')])
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('purchase_list_filter_location_id', __('purchase.business_location') . ':') !!}
                    {!! Form::select('purchase_list_filter_location_id', $business_locations, null, [
                        'class' => 'form-control select2',
                        'style' => 'width:100%',
                        'placeholder' => __('lang_v1.all'),
                    ]) !!}
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('purchase_list_filter_supplier_id', __('purchase.supplier') . ':') !!}
                    {!! Form::select('purchase_list_filter_supplier_id', $suppliers, null, [
                        'class' => 'form-control select2',
                        'style' => 'width:100%',
                        'placeholder' => __('lang_v1.all'),
                    ]) !!}
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('purchase_list_filter_status', __('purchase.purchase_status') . ':') !!}
                    {!! Form::select('purchase_list_filter_status', $orderStatuses, null, [
                        'class' => 'form-control select2',
                        'style' => 'width:100%',
                        'placeholder' => __('lang_v1.all'),
                    ]) !!}
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('purchase_list_filter_payment_status', __('purchase.payment_status') . ':') !!}
                    {!! Form::select(
                        'purchase_list_filter_payment_status',
                        [
                            'paid' => __('lang_v1.paid'),
                            'due' => __('lang_v1.due'),
                            'partial' => __('lang_v1.partial'),
                            'overdue' => __('lang_v1.overdue'),
                        ],
                        null,
                        ['class' => 'form-control select2', 'style' => 'width:100%', 'placeholder' => __('lang_v1.all')],
                    ) !!}
                </div>
            </div>
            <div class="col-md-3">
                <div class="form-group">
                    {!! Form::label('purchase_list_filter_date_range', __('report.date_range') . ':') !!}
                    {!! Form::text('purchase_list_filter_date_range', null, [
                        'placeholder' => __('lang_v1.select_a_date_range'),
                        'class' => 'form-control',
                        'readonly',
                    ]) !!}
                </div>
            </div>
        @endcomponent

        @component('components.widget', ['class' => 'box-primary', 'title' => __('purchase.all_purchases')])
            @can('purchase.create')
                @slot('tool')
                    <div class="box-tools">
                        <a class="btn btn-block btn-primary" href="{{ action('PurchaseController@create') }}">
                            <i class="fa fa-plus"></i> @lang('messages.add')</a>
                    </div>
                @endslot
            @endcan
            <input type="hidden" id="enable_short_cut_purchase" value="{{ $super_admin->enable_short_cut_purchase }}">
            @if ($super_admin->enable_short_cut_purchase != 1)
                @include('purchase.partials.purchase_table')
            @else
                @include('purchase.partials.purchase_table_form_pop')

                <div style="display: flex; width: 100%; margin-top: 10px;">
                    {!! Form::open([
                        'url' => action('PurchaseController@massDestroy'),
                        'method' => 'post',
                        'id' => 'mass_delete_form',
                    ]) !!}
                    {!! Form::hidden('selected_deleted_rows', null, ['id' => 'selected_deleted_rows']) !!}
                    {!! Form::submit(__('lang_v1.delete_selected'), ['class' => 'btn btn-xs btn-danger', 'id' => 'delete-selected']) !!}
                    {!! Form::close() !!}
                </div>
            @endif
        @endcomponent

        <div class="modal fade product_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
        </div>

        <div class="modal fade payment_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
        </div>

        <div class="modal fade edit_payment_modal" tabindex="-1" role="dialog" aria-labelledby="gridSystemModalLabel">
        </div>

        @include('purchase.partials.update_purchase_status_modal')

    </section>

    <section id="receipt_section" class="print_section"></section>

    <!-- /.content -->
@stop
@section('javascript')
    <script src="{{ asset('js/purchase.js?v=' . $asset_v) }}"></script>
    <script src="{{ asset('js/payment.js?v=' . $asset_v) }}"></script>
    <script>
        //Date range as a button
        dateRangeSettings.startDate = moment().startOf('year'); 
        dateRangeSettings.endDate = moment().endOf('year');
        $('#purchase_list_filter_date_range').daterangepicker(
            dateRangeSettings,
            function(start, end) {
                $('#purchase_list_filter_date_range').val(start.format(moment_date_format) + ' ~ ' + end.format(
                    moment_date_format));
                purchase_table.ajax.reload();
            }
        );
        $('#purchase_list_filter_date_range').on('cancel.daterangepicker', function(ev, picker) {
            $('#purchase_list_filter_date_range').val('');
            purchase_table.ajax.reload();
        });

        $(document).on('click', '.update_status', function(e) {
            e.preventDefault();
            $('#update_purchase_status_form').find('#status').val($(this).data('status'));
            $('#update_purchase_status_form').find('#purchase_id').val($(this).data('purchase_id'));
            $('#update_purchase_status_modal').modal('show');
        });

        $(document).on('submit', '#update_purchase_status_form', function(e) {
            e.preventDefault();
            var form = $(this);
            var data = form.serialize();

            $.ajax({
                method: 'POST',
                url: $(this).attr('action'),
                dataType: 'json',
                data: data,
                beforeSend: function(xhr) {
                    __disable_submit_button(form.find('button[type="submit"]'));
                },
                success: function(result) {
                    if (result.success == true) {
                        $('#update_purchase_status_modal').modal('hide');
                        toastr.success(result.msg);
                        purchase_table.ajax.reload();
                        $('#update_purchase_status_form')
                            .find('button[type="submit"]')
                            .attr('disabled', false);
                    } else {
                        toastr.error(result.msg);
                    }
                },
            });
        });

        $(document).on('click', '#select-all-purchase', function(e) {
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
    </script>

@endsection
