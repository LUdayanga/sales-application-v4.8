<div class="pos-tab-content">
    <div class="row">
            <div class="col-sm-4">
                <div class="form-group">
                    <label>
                        {!! Form::checkbox('common_settings[is_enabled_manufacturing]', true, !empty($business->manufacturing) ? 1 : 0,

                        [ 'class' => 'input-icheck']); !!} Enable Manufacturing
                    </label>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="form-group">
                    <label>
                        {!! Form::checkbox('common_settings[is_enabled_repair]', true,  !empty($business->repair) ? 1 : 0,
                        [ 'class' => 'input-icheck']); !!} Enable Repairing Module
                    </label>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="form-group">
                    <label>
                        {!! Form::checkbox('common_settings[is_enabled_essential]', true,  !empty($business->essential) ? 1 : 0,
                        [ 'class' => 'input-icheck']); !!} Enable Essential Module
                    </label>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="form-group">
                    <label>
                        {!! Form::checkbox('common_settings[is_enabled_woocommerce]', true,  !empty($business->woocommerce) ? 1 : 0,
                        [ 'class' => 'input-icheck']); !!} Enable Woocommerce Module
                    </label>
                </div>
            </div>
            
            <div class="col-sm-4">
                <div class="form-group">
                    <label>
                        {!! Form::checkbox('common_settings[is_enabled_accounting]', true,  !empty($business->accounting) ? 1 : 0,
                        [ 'class' => 'input-icheck']); !!} Enable Accounting Module
                    </label>
                </div>
            </div>

            <div class="col-sm-4">
                <div class="form-group">
                    <label>
                        {!! Form::checkbox('common_settings[is_enabled_product_catelogue]', true,  !empty($business->product_catelogue) ? 1 : 0,
                        [ 'class' => 'input-icheck']); !!} Enable Product Catelogue
                    </label>
                </div>
            </div>

            <div class="col-sm-4">
                <div class="form-group">
                    <label>
                        {!! Form::checkbox('common_settings[is_enabled_shop_module]', true,  !empty($business->shop_module) ? 1 : 0,
                        [ 'class' => 'input-icheck']); !!} Enable Shop Module
                    </label>
                </div>
            </div>
    </div>
     
  
   
</div>