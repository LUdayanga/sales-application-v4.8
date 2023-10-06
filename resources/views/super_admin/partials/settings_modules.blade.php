<div class="pos-tab-content">
	<div class="row">
	@if(!empty($modules))
		@foreach($modules as $k => $v)
            <div class="col-sm-4">
                <div class="form-group">
                    <div class="checkbox">
                      <label>
                        {!! Form::checkbox('enabled_modules[]', $k,  in_array($k, $enabled_modules) , 
                        ['class' => 'input-icheck']); !!} {{$v['name']}}
                      </label>
                      @if(!empty($v['tooltip'])) @show_tooltip($v['tooltip']) @endif
                    </div>
                </div>
            </div>
        @endforeach
	@endif
	</div>
</div>