@extends('layout')

@section('content')
<div class="menu">
    <div class="pure-menu pure-menu-horizontal">
        <ul class="pure-menu-list">
            <li class="pure-menu-item">{!! Html::linkRoute('listing.index', 'index') !!}</li>
            <li class="pure-menu-item">{!! Html::linkRoute('listing.create', 'new listing') !!}</li>
        </ul>
    </div>
</div>

@yield('crud')
@stop
