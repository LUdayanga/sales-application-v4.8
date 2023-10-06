<!-- business information here -->
<style>
	
</style>
<div class="row">

    <!-- Logo -->
    @if (!empty($receipt_details->logo))
        <img style="max-height: 120px; width: auto;" src="{{ $receipt_details->logo }}"
            class="img img-responsive center-block">
    @endif

    <!-- Header text -->
    @if (!empty($receipt_details->header_text))
        <div class="col-xs-12">
            {!! $receipt_details->header_text !!}
        </div>
    @endif

    <!-- business information here -->
    <div class="col-xs-12 text-center">
        <p class="text-center" style="font-size: 28px;">
			<b>
            <!-- Shop & Location Name  -->
            @if (!empty($receipt_details->display_name))
                {{ $receipt_details->display_name }}
            @endif
		</b>
        </p>

        <!-- Address -->
        <p style="font-size: 22px;">
            @if (!empty($receipt_details->address))
                <small class="text-center">
                    {!! $receipt_details->address !!}
                </small>
            @endif
            @if (!empty($receipt_details->contact))
                <br />{!! $receipt_details->contact !!}
            @endif
        </p>

        <!-- Invoice  number, Date  -->
        <p style="width: 100% !important; font-size: 18px;">
            <span class="pull-right">
                <b>Ref No:</b> {{ $receipt_details->invoice_no }}
            </span>
            <br>
            <span class="pull-right">
                <b>Date:</b> {{ $receipt_details->transaction_date }}
            </span>
            <span class="pull-left">
                <!-- customer info -->
                @if (!empty($receipt_details->supplier_info))
                    <b>Supplier:</b> <br> {!! $receipt_details->supplier_info !!} <br>
                @endif
            </span>
        </p>
    </div>
</div>

<div class="row">
    <div class="col-xs-12">
        <br />
        @php
            $p_width = 45;
        @endphp
        @if (!empty($receipt_details->item_discount_label))
            @php
                $p_width -= 10;
            @endphp
        @endif
        @if (!empty($receipt_details->discounted_unit_price_label))
            @php
                $p_width -= 10;
            @endphp
        @endif
        <table class="table table-responsive table-slim" style="font-size: 18px;">
            <thead>
                <tr>
                    <th>Products</th>
                    <th class="text-right">QTY</th>
                    <th class="text-right">Unit Price</th>
                    @if (!empty($receipt_details->discounted_unit_price_label))
                        <th class="text-right">{{ $receipt_details->discounted_unit_price_label }}</th>
                    @endif
                    @if (!empty($receipt_details->item_discount_label))
                        <th class="text-right">{{ $receipt_details->item_discount_label }}</th>
                    @endif
                    <th class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                @forelse($receipt_details->lines as $line)
                    <tr>
                        <td>
                            {{ $line['name'] }} {{ $line['product_variation'] }} {{ $line['variation'] }}
                            {{-- @if (!empty($line['sub_sku'])), {{$line['sub_sku']}} @endif @if (!empty($line['brand'])), {{$line['brand']}} @endif @if (!empty($line['cat_code'])), {{$line['cat_code']}}@endif --}}
                            @if (!empty($line['product_custom_fields']))
                                , {{ $line['product_custom_fields'] }}
                            @endif
                            @if (!empty($line['sell_line_note']))
                                <br>
                                <small>
                                    {!! $line['sell_line_note'] !!}
                                </small>
                            @endif
                            @if (!empty($line['lot_number']))
                                <br> {{ $line['lot_number_label'] }}: {{ $line['lot_number'] }}
                            @endif
                            @if (!empty($line['product_expiry']))
                                , {{ $line['product_expiry_label'] }}: {{ $line['product_expiry'] }}
                            @endif

                            @if (!empty($line['warranty_name']))
                                <br><small>{{ $line['warranty_name'] }} </small>
                                @endif @if (!empty($line['warranty_exp_date']))
                                    <small>- {{ @format_date($line['warranty_exp_date']) }} </small>
                                @endif
                                @if (!empty($line['warranty_description']))
                                    <small> {{ $line['warranty_description'] ?? '' }}</small>
                                @endif

                                @if ($line['quantity'] && $line['base_unit_multiplier'] !== 1)
                                    <br><small>
                                        1 {{ $line['units'] }} = {{ $line['base_unit_multiplier'] }}
                                        {{ $line['base_unit_name'] }} <br>
                                        {{ $line['unit_price_inc_tax'] }} x {{ $line['quantity'] }} =
                                        {{ $line['line_total'] }}
                                    </small>
                                @endif
                        </td>
                        <td class="text-right">
                            {{ $line['quantity'] }}
                            {{-- {{$line['units']}}  --}}

                            @if ($line['quantity'] && $line['base_unit_multiplier'] !== 1)
                                <br><small>
                                    {{ $line['quantity'] }} x {{ $line['base_unit_multiplier'] }} =
                                    {{ $line['orig_quantity'] }} {{ $line['base_unit_name'] }}
                                </small>
                            @endif
                        </td>
                        <td class="text-right">Rs {{ $line['unit_price_before_discount'] }}</td>
                        @if (!empty($receipt_details->item_discount_label))
                            <td class="text-right">
                                {{ $line['total_line_discount'] ?? '0.00' }}

                                @if (!empty($line['line_discount_percent']))
                                    ({{ $line['line_discount_percent'] }}%)
                                @endif
                            </td>
                        @endif
                        <td class="text-right">Rs {{ $line['line_total'] }}</td>
                    </tr>
                    @if (!empty($line['modifiers']))
                        @foreach ($line['modifiers'] as $modifier)
                            <tr>
                                <td>
                                    {{ $modifier['name'] }} {{ $modifier['variation'] }}
                                    @if (!empty($modifier['sub_sku']))
                                        , {{ $modifier['sub_sku'] }}
                                        @endif @if (!empty($modifier['cat_code']))
                                            , {{ $modifier['cat_code'] }}
                                        @endif
                                        @if (!empty($modifier['sell_line_note']))
                                            ({!! $modifier['sell_line_note'] !!})
                                        @endif
                                </td>
                                <td class="text-right">{{ $modifier['quantity'] }} {{ $modifier['units'] }} </td>
                                <td class="text-right">{{ $modifier['unit_price_inc_tax'] }}</td>
                                @if (!empty($receipt_details->discounted_unit_price_label))
                                    <td class="text-right">{{ $modifier['unit_price_exc_tax'] }}</td>
                                @endif
                                @if (!empty($receipt_details->item_discount_label))
                                    <td class="text-right">0.00</td>
                                @endif
                                <td class="text-right">{{ $modifier['line_total'] }}</td>
                            </tr>
                        @endforeach
                    @endif
                @empty
                    <tr>
                        <td colspan="4">&nbsp;</td>
                        @if (!empty($receipt_details->discounted_unit_price_label))
                            <td></td>
                        @endif
                        @if (!empty($receipt_details->item_discount_label))
                            <td></td>
                        @endif
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
<hr>
<br>
<div class="col-xs-12">
    <div class="table-responsive">
        <table class="table table-slim" style="font-size: 20px;">
            <tbody>
                <!-- Total -->
                <tr>
                    <th>
                    </th>
                    <td class="text-right">
                        <b>Sub Total:</b> Rs {{ number_format($receipt_details->total_in_words, 2) }}
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

</div>

<style type="text/css">
    body {
        color: #000000;
    }
</style>
