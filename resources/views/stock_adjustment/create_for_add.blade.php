@extends('layouts.app')
@section('title', __('stock_adjustment.add'))

@section('content')

    <!-- Content Header (Page header) -->
    <section class="content-header">
        <br>
        <h1>@lang('stock_adjustment.increase_qty')</h1>
        <!-- <ol class="breadcrumb">
                    <li><a href="#"><i class="fa fa-dashboard"></i> Level</a></li>
                    <li class="active">Here</li>
                </ol> -->
    </section>

    <!-- Main content -->
    <section class="content no-print">
        {!! Form::open([
            'url' => action('StockAdjustmentController@store'),
            'method' => 'post',
            'id' => 'stock_adjustment_form',
        ]) !!}
        <div class="box box-solid">
            <div class="box-body">
                <div class="row">
                    <div class="col-sm-3">
                        <div class="form-group">
                            {!! Form::label('location_id', __('purchase.business_location') . ':*') !!}
                            {!! Form::select('location_id', $business_locations, null, [
                                'class' => 'form-control select2',
                                'placeholder' => __('messages.please_select'),
                                'required',
                            ]) !!}
                        </div>
                    </div>
                    <div class="col-sm-3">
                        <div class="form-group">
                            {!! Form::label('ref_no', __('purchase.ref_no') . ':') !!}
                            {!! Form::text('ref_no', null, ['class' => 'form-control']) !!}
                        </div>
                    </div>
                    <div class="col-sm-3">
                        <div class="form-group">
                            {!! Form::label('transaction_date', __('messages.date') . ':*') !!}
                            <div class="input-group">
                                <span class="input-group-addon">
                                    <i class="fa fa-calendar"></i>
                                </span>
                                {!! Form::text('transaction_date', @format_datetime('now'), ['class' => 'form-control', 'readonly', 'required']) !!}
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-3">
                        <div class="form-group">
                            {!! Form::label('adjustment_type', __('stock_adjustment.adjustment_type') . ':*') !!} @show_tooltip(__('tooltip.adjustment_type'))
                            {!! Form::select(
                                'adjustment_type',
                                ['normal' => __('stock_adjustment.normal'), 'abnormal' => __('stock_adjustment.abnormal')],
                                null,
                                ['class' => 'form-control select2', 'placeholder' => __('messages.please_select'), 'required'],
                            ) !!}
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!--box end-->
        <div class="box box-solid">
            <div class="box-header">
                <h3 class="box-title">{{ __('stock_adjustment.search_products') }}</h3>
            </div>
            <div class="box-body">
                <div class="row">
                    <div class="col-sm-8 col-sm-offset-2">
                        <div class="form-group">
                            <div class="input-group">
                                <span class="input-group-addon">
                                    <i class="fa fa-search"></i>
                                </span>
                                {!! Form::text('search_product', null, [
                                    'class' => 'form-control',
                                    'id' => 'search_product_for_srock_increase',
                                    'placeholder' => __('stock_adjustment.search_product'),
                                    'disabled',
                                ]) !!}
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-sm-10 col-sm-offset-1">
                        <input type="hidden" id="product_row_index" value="0">
                        <input type="hidden" id="total_amount" name="final_total" value="0">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped table-condensed"
                                id="stock_adjustment_product_table_for_increase">
                                <thead>
                                    <tr>
                                        <th class="col-sm-4 text-center">
                                            @lang('sale.product')
                                        </th>
                                        <th class="col-sm-2 text-center">
                                            @lang('sale.qty')
                                        </th>
                                        <th class="col-sm-2 text-center">
                                            @lang('sale.unit_price')
                                        </th>
                                        <th class="col-sm-2 text-center">
                                            @lang('sale.subtotal')
                                        </th>
                                        <th class="col-sm-2 text-center"><i class="fa fa-trash" aria-hidden="true"></i></th>
                                    </tr>
                                </thead>
                                <tbody>
                                </tbody>
                                <tfoot>
                                    <tr class="text-center">
                                        <td colspan="3"></td>
                                        <td>
                                            <div class="pull-right"><b>@lang('stock_adjustment.total_amount'):</b> <span
                                                    id="total_adjustment">0.00</span></div>
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!--box end-->
        <div class="box box-solid">
            <div class="box-body">
                <div class="row">
                    <div class="col-sm-4">
                        <div class="form-group">
                            {!! Form::label('total_amount_recovered', __('stock_adjustment.total_amount_recovered') . ':') !!} @show_tooltip(__('tooltip.total_amount_recovered'))
                            {!! Form::text('total_amount_recovered', 0, [
                                'class' => 'form-control input_number',
                                'placeholder' => __('stock_adjustment.total_amount_recovered'),
                            ]) !!}
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="form-group">
                            {!! Form::label('additional_notes', __('stock_adjustment.reason_for_stock_adjustment') . ':') !!}
                            {!! Form::textarea('additional_notes', null, [
                                'class' => 'form-control',
                                'placeholder' => __('stock_adjustment.reason_for_stock_adjustment'),
                                'rows' => 3,
                            ]) !!}
                        </div>
                    </div>

                    {{-- hidden value for increase or decrease --}}
                    <div class="col-sm-4">
                        <div class="form-group">
                            <input type="hidden" name="incOrdesc" id="adjust_type" value="increase">
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-sm-12">
                        <button type="submit" class="btn btn-primary pull-right">@lang('messages.save')</button>
                    </div>
                </div>

            </div>
        </div>
        <!--box end-->


        {!! Form::close() !!}
    </section>
@stop
@section('javascript')
    {{-- <script src="{{ asset('js/stock_adjustment.js?v=' . $asset_v) }}"></script> --}}
    <script type="text/javascript">
        __page_leave_confirmation('#stock_adjustment_form');
    </script>
    <script type="text/javascript">
        $(document).ready(function() {
            //Add products
            if ($('#search_product_for_srock_increase').length > 0) {
                //Add Product

                $('#search_product_for_srock_increase')
                    .autocomplete({
                        source: function(request, response) {
                            $.getJSON(
                                '/products/list', {
                                    location_id: $('#location_id').val(),
                                    term: request.term,
                                },
                                response
                            );
                        },
                        minLength: 2,
                        response: function(event, ui) {
                            if (ui.content.length == 1) {
                                ui.item = ui.content[0];
                                if (ui.item.enable_stock == 1) {
                                    $(this)
                                        .data('ui-autocomplete')
                                        ._trigger('select', 'autocompleteselect', ui);
                                    $(this).autocomplete('close');
                                }
                            } else if (ui.content.length == 0) {
                                swal(LANG.no_products_found);
                            }
                        },
                        focus: function(event, ui) {
                            if (ui.item.qty_available == -1) {
                                return false;
                            }
                        },
                        select: function(event, ui) {
                            $(this).val(null);
                            stock_adjustment_product_row(ui.item.variation_id);
                        },
                    })
                    .autocomplete('instance')._renderItem = function(ul, item) {
                        if (item.qty_available <= 0) {
                            var string = '<div>' + item.name;
                            if (item.type == 'variable') {
                                string += '-' + item.variation;
                            }
                            string += ' (' + item.sub_sku + ') </div>';
                            return $('<li>')
                                .append(string)
                                .appendTo(ul);
                        } else if (item.enable_stock != 1) {
                            return ul;
                        } else {
                            var string = '<div>' + item.name;
                            if (item.type == 'variable') {
                                string += '-' + item.variation;
                            }
                            string += ' (' + item.sub_sku + ') </div>';
                            return $('<li>')
                                .append(string)
                                .appendTo(ul);
                        }
                    };
            }

            function stock_adjustment_product_row(variation_id) {

                var row_index = parseInt($('#product_row_index').val());
                var location_id = $('select#location_id').val();
                $.ajax({
                    method: 'POST',
                    url: '/stock-adjustments/get_product_row_for_increase',
                    data: {
                        row_index: row_index,
                        variation_id: variation_id,
                        location_id: location_id,
                        adjust_type: $("#adjust_type").val()
                    },
                    dataType: 'html',
                    success: function(result) {
                        $('table#stock_adjustment_product_table_for_increase tbody').append(result);
                        update_table_total();
                        $('#product_row_index').val(row_index + 1);
                    },
                });
            }

            $('select#location_id').change(function() {
                if ($(this).val()) {
                    $('#search_product_for_srock_increase').removeAttr('disabled');
                } else {
                    $('#search_product_for_srock_increase').attr('disabled', 'disabled');
                }
                $('table#stock_adjustment_product_table_for_increase tbody').html('');
                $('#product_row_index').val(0);
                update_table_total();
            });

            $(document).on('change', 'input.product_quantity', function() {
                update_table_row($(this).closest('tr'));
            });
            $(document).on('change', 'input.product_unit_price', function() {
                update_table_row($(this).closest('tr'));
            });

            $(document).on('click', '.remove_product_row', function() {
                swal({
                    title: LANG.sure,
                    icon: 'warning',
                    buttons: true,
                    dangerMode: true,
                }).then(willDelete => {
                    if (willDelete) {
                        $(this)
                            .closest('tr')
                            .remove();
                        update_table_total();
                    }
                });
            });

            //Date picker
            $('#transaction_date').datetimepicker({
                format: moment_date_format + ' ' + moment_time_format,
                ignoreReadonly: true,
            });

            $('form#stock_adjustment_form').validate();
        });

        function update_table_total() {
            var table_total = 0;
            $('table#stock_adjustment_product_table_for_increase tbody tr').each(function() {
                var this_total = parseFloat(__read_number($(this).find('input.product_line_total')));
                if (this_total) {
                    table_total += this_total;
                }
            });
            $('input#total_amount').val(table_total);
            $('span#total_adjustment').text(__number_f(table_total));
        }

        function update_table_row(tr) {
            var quantity = parseFloat(__read_number(tr.find('input.product_quantity')));
            var unit_price = parseFloat(__read_number(tr.find('input.product_unit_price')));
            var row_total = 0;
            if (quantity && unit_price) {
                row_total = quantity * unit_price;
            }
            tr.find('input.product_line_total').val(__number_f(row_total));
            update_table_total();
        }
    </script>
@endsection
