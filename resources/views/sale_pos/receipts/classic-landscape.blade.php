<!-- business information here -->
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <!-- <link rel="stylesheet" href="style.css"> -->
    <title>Receipt-{{ $receipt_details->invoice_no }}</title>
</head>

<body>
    <div class="row">
        <div class="col-sm-4 pull-left">
            @if (!empty($receipt_details->logo))
                <div class="text-box centered">
                    <img style="max-height: 80px; width: auto;" src="{{ $receipt_details->logo }}" alt="Logo">
                </div>
            @endif
        </div>
        <div class="col-sm-4 pull-left" style="margin-left: 50px;">
            <p class="centered">
                <!-- Header text -->
                @if (!empty($receipt_details->header_text))
                    <span class="headings">{!! $receipt_details->header_text !!}</span>
                    <br />
                @endif

                <!-- business information here -->
                @if (!empty($receipt_details->display_name))
                    <span class="headings">
                        {{ $receipt_details->display_name }}
                    </span>
                    <br />
                @endif

                @if (!empty($receipt_details->address))
                    <span style="font-size: 22px;">{!! $receipt_details->landscape_address !!}</span>
                    {{-- <br /> --}}
                @endif

                @if (!empty($receipt_details->contact))
                    <br />
                    <span style="font-size: 22px; font-weight: bold">Mobile: </span> <span
                        style="font-size: 22px;">{!! $receipt_details->location_contact !!}</span>
                @endif
                @if (!empty($receipt_details->website))
                    <span style="font-size: 22px;">{{ $receipt_details->website }}</span>
                @endif
            </p>
        </div>
        <div class="col-sm-4 pull-left" style="margin-left: 20px; border: 3px solid; border-radius: 10px;">
            <h1 style="font-weight: bold;">INVOICE</h1>
        </div>
    </div>
    <div>
        <div class="row">
            <div class="col-sm-12">
                <div class="col-sm-6"
                    style="border: 3px solid; border-radius: 10px; float: left; margin-left: 8px; width: 630px;">
                    <table style="height: 75px;">
                        <tr>
                            <td><span style="font-size: 22px; font-weight: bold; padding: 10px;">Customer: </span></td>
                            <td><span
                                    style="font-size: 20px; margin-left: 5px; text-transform: uppercase;">{!! $receipt_details->customer_name !!}</span>
                            </td>
                        </tr>
                        <tr>
                            <td></td>
                            <td>
                                <span style="font-size: 20px; margin-left: 5px;">
                                    {{ $receipt_details->customer_address_inline }}
                                </span>
                            </td>
                        </tr>
                    </table>
                </div>
                <div class="col-sm-6"
                    style="border: 3px solid; border-radius: 10px; margin-left: 640px; margin-right: 10px;">
                    <table style="height: 75px;">
                        <tr>
                            <td><span style="font-size: 22px; font-weight: bold; padding: 10px;">Invoice No: </span>
                            </td>
                            <td><span
                                    style="font-size: 22px; margin-left: 5px;">{{ $receipt_details->invoice_no }}</span>
                            </td>
                        </tr>
                        <tr>
                            <td><span style="font-size: 20px; font-weight: bold; padding: 10px;">Invoice Date: </span>
                            </td>
                            <td><span
                                    style="font-size: 20px; margin-left: 5px;">{{ $receipt_details->invoice_date }}</span>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <div style="margin-left: 10px; margin-right: 10px;">
            <div style="border: 3px solid; border-radius: 6px; padding: 1px; margin-top: 6px;">
                <table class="border-bottom width-100 table-f-12 mb-10">
                    <tr>
                        <td width="10%"><span
                                style="font-size: 22px; font-weight: bold; margin-left: 3px;">Code</span></td>
                        <td width="45%"><span style="font-size: 22px; font-weight: bold;">Description</span></td>
                        <td width="15%" class="text-right"><span style="font-size: 22px; font-weight: bold;">Unit
                                Price</span></td>
                        <td width="15%" class="text-right"><span
                                style="font-size: 22px; font-weight: bold;">Qty.</span>
                        </td>
                        <td width="15%" class="text-right"><span
                                style="font-size: 22px; font-weight: bold; margin-right: 5px;">Total</span>
                        </td>
                    </tr>
                </table>
            </div>

            @if ($receipt_details->is_cheque_payment)
                <div style="height: 230px; border: 3px solid; border-radius: 6px; padding: 1px;">
                @else
                    <div style="height: 250px; border: 3px solid; border-radius: 6px; padding: 1px;">
            @endif
            <table style="border-spacing: 15px;" class="border-bottom width-100 table-f-12 mb-10">
                <tbody>
                    @forelse($receipt_details->lines as $line)
                        <tr>
                            <td width="10%"><span
                                    style="font-size: 20px; margin-left: 3px;">{{ $line['landscape_sub_sku'] }}</span>
                            </td>
                            <td width="45%"><span style="font-size: 20px;">{{ $line['name'] }}</span></td>
                            <td width="15%" class="text-right"><span
                                    style="font-size: 20px;">Rs.{{ $line['unit_price_before_discount'] }}</span>
                            </td>
                            <td width="15%" class="text-right"><span
                                    style="font-size: 20px;">{{ $line['quantity'] }}
                                    {{ $line['base_unit_name'] }}</span></td>
                            <td width="15%" class="text-right"><span
                                    style="font-size: 20px; margin-right: 5px;">Rs.{{ $line['line_total'] }}</span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @if (empty($receipt_details->hide_price))
        <div class="flex-box" style="margin-right: 100px;">
            <p class="left text-left">

            </p>
            <p class="width-50 text-right" style="margin-right: 12px;">
                <span style="font-size: 20px; font-weight: bold;">Sub Total: </span><strong
                    style="font-size: 20px; font-weight: bold;">{{ $receipt_details->subtotal }}</strong>
            </p>
        </div>
    @endif
    </div>
    <table style="width: 100%; margin-top: 10px; margin-right: 100px;">
        <tr>
            {{-- <td width="5%"></td> --}}
            <td class="text-center" width="30%"><span
                    style="font-size: 20px;">.........................................</span></td>
            <td class="text-center" width="30%"><span
                    style="font-size: 20px;">.........................................</span></td>
            <td class="text-center" width="30%"><span
                    style="font-size: 20px;">..........................................</span></td>
            <td width="5%"></td>
        </tr>
        <tr>
            {{-- <td width="5%"></td> --}}
            <td class="text-center" width="30%"><span style="font-size: 20px;">Enterd By</span></td>
            <td class="text-center" width="30%"><span style="font-size: 20px;">Good Recieved By</span></td>
            <td class="text-center" width="30%"><span style="font-size: 20px;">Check & Authorized By</span></td>
            <td width="5%"></td>
        </tr>
    </table>

    @if ($receipt_details->is_cheque_payment)
        <br>
        <div class="text-center">
            <span style="font-size: 22px;">Cheque should be drawn in favour of <strong style="font-size: 22px;">M/S
                    Ama production and master chef</strong>
                and
                Crossed A/C Payee</span>
        </div>
    @endif
</body>

</html>

<style type="text/css">
    @page {
        size: landscape;
    }

    .f-8 {
        font-size: 8px !important;
    }

    body {
        color: #000000;
    }

    @media print {
        * {
            font-size: 12px;
            font-family: 'Times New Roman';
            word-break: break-all;
        }

        .f-8 {
            font-size: 8px !important;
        }

        .headings {
            font-size: 30px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .sub-headings {
            font-size: 15px;
            font-weight: 700;
        }

        /* .border-top {
            border-top: 1px solid #242424;
        } */

        /* .border-bottom {
            border-bottom: 1px solid #242424;
        } */

        /* .border-bottom-dotted {
            border-bottom: 1px dotted darkgray;
        } */

        .centered {
            text-align: center;
            align-content: center;
        }

        .ticket {
            width: 100%;
            max-width: 100%;
        }

        img {
            max-width: inherit;
            width: auto;
        }

        .hidden-print,
        .hidden-print * {
            display: none !important;
        }
    }

    .logo {
        float: left;
        width: 35%;
        padding: 10px;
    }

    .text-with-image {
        float: left;
        width: 65%;
    }

    .text-box {
        width: 100%;
        height: auto;
    }

    .m-0 {
        margin: 0;
    }

    .textbox-info {
        clear: both;
    }

    .textbox-info p {
        margin-bottom: 0px
    }

    .flex-box {
        display: flex;
        width: 100%;
    }

    .flex-box p {
        width: 50%;
        margin-bottom: 0px;
        white-space: nowrap;
    }

    /* .table-f-12 th,
    .table-f-12 td {
        font-size: 12px;
        word-break: break-word;
    } */

    .bw {
        word-break: break-word;
    }
</style>
