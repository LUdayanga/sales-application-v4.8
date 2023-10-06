<div class="pos-tab-content">
    <div class="row">
            <div class="col-sm-4">
                <div class="form-group">
                    <label>
                        {!! Form::checkbox('common_settings[show_product_second_name]', true, $business->show_product_second_name == 1 ? 1 : 0,

                        [ 'class' => 'input-icheck']); !!} Enable Product Second Name
                    </label>
                </div>
            </div>
            <div class="col-sm-8">
                <div class="form-group">
                    <label>
                        {!! Form::checkbox('common_settings[product_variation_on_purchase]', true, $business->product_variation_on_purchase == 1 ? 1 : 0,

                        [ 'class' => 'input-icheck']); !!} Enable Auto Variation Products On Purchase
                    </label>
                </div>
            </div>    
    </div>
</div>