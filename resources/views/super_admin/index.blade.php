@extends('layouts.app')
@section('title', 'SUPER ADMIN PANEL')

@section('content')

<!-- Content Header (Page header) -->
<section class="content-header">
    <h1>SUPER ADMIN LOGIN</h1>
    <br>
    {{-- @include('layouts.partials.search_settings') --}}
</section>

<!-- Main content -->
<section class="content">
    <div class="row">
        <div class="col-md-12">
            {!! Form::open(['url' => action('SuperAdminController@clickySuperadmin'), 'method' => 'post' ]) !!}
            @component('components.widget')
            <div class="col-md-4"></div>
            <div class="col-md-4">
                <div class="form-group">
                  <label for="">Password:*</label>
                  <input type="password" class="form-control" name="super_admin_password" id="super_admin_password" required>
                  <br>
                  <button class="btn btn-primary pull-right">Go</button>
                </div>
              </div>
              <div class="col-md-4"></div>
            @endcomponent
            {!! Form::close() !!}
        </div>
    </div>
</section>
<!-- /.content -->

@stop
@section('javascript')
<script type="text/javascript">
    
</script>
@endsection