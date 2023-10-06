<div class="pos-tab-content">
    <div class="row">
            <div class="col-sm-6">
                <div class="form-group">
                    <label>
                        {!! Form::checkbox('super_admin[cheque_payment_set_as_paid]', true, !empty($super_admin->cheque_payment_set_as_paid) ? 1 : 0,

                        [ 'class' => 'input-icheck']); !!} Trnsaction Payment Status Set As Paid
                    </label>
                </div>
            </div>
            <div class="col-sm-6">
                <div class="form-group">
                    <label>
                        {!! Form::checkbox('super_admin[increase_chque_due_in_reports]', true,  !empty($super_admin->increase_chque_due_in_reports) ? 1 : 0,
                        [ 'class' => 'input-icheck']); !!} Increase Cheque Due Amount In Reports
                    </label>
                </div>
            </div>      
    </div>
</div>