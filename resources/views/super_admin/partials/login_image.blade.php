<div class="pos-tab-content active">
    <div class="row">
        <div class="col-sm-6">
                <div class="form-group">
                    {!! Form::label('image', __('Add Login Image') . ':') !!}
                    {!! Form::file('login_image', ['id' => 'upload_image', 'accept' => 'image/*']) !!}
                    <small>
                        <p class="help-block">@lang('purchase.max_file_size', ['size' => config('constants.document_size_limit') / 1000000]) <br> @lang('lang_v1.aspect_ratio_should_be_1_1')</p>
                    </small>
                </div>
        </div>
        <div class="col-sm-6">
            {!! Form::label('image', __('Current Login Image') . ':') !!}
            <div class="thumbnail">
                <img src="{{$business->login_image_url}}" alt="Product image">
            </div>
        </div>
    </div>  
</div>