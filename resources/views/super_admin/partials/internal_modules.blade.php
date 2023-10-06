<div class="pos-tab-content">
    <div class="row">
            <div class="col-sm-4">
                <div class="form-group">
                    <label>
                        {!! Form::checkbox('super_admin[is_enabled_cheque_management]', true, !empty($super_admin->cheque_module) ? 1 : 0,

                        [ 'class' => 'input-icheck']); !!} Enable Cheque Management
                    </label>
                    <span>(Include cheque alert)</span>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="form-group">
                    <label>
                        {!! Form::checkbox('super_admin[is_enabled_bulk_payment]', true,  !empty($super_admin->bulk_payment) ? 1 : 0,
                        [ 'class' => 'input-icheck']); !!} Enable Bulk Payment
                    </label>
                </div>
            </div>     
            <div class="col-sm-4">
                <div class="form-group">
                    <label>
                        {!! Form::checkbox('super_admin[advance_stock_adjustment]', true,  !empty($super_admin->advance_stock_adjustment) ? 1 : 0,
                        [ 'class' => 'input-icheck']); !!} Increase/Decrease Adjustment
                    </label>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="form-group">
                    <label>
                        {!! Form::checkbox('super_admin[enable_sale_bulk_return]', true,  !empty($super_admin->enable_sale_bulk_return) ? 1 : 0,
                        [ 'class' => 'input-icheck']); !!} Enable Sale Bulk Return
                    </label>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="form-group">
                    <label>
                        {!! Form::checkbox('super_admin[enable_short_cut_purchase]', true,  !empty($super_admin->enable_short_cut_purchase) ? 1 : 0,
                        [ 'class' => 'input-icheck']); !!} Enable Shortcut Purchase
                    </label>
                </div>
            </div>   
    </div>
</div>