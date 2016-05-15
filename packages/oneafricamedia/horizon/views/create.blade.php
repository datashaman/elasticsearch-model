@extends('crud')

@section('crud')
{!! Form::open([ 'route' => [ 'listing.store' ], 'class' => 'pure-form pure-form-stacked' ]) !!}
{!! Form::hidden('type', $type['id']) !!}

@include('horizon::_form')

{!! Form::submit('Save', [ 'class' => 'pure-button pure-button-primary' ]) !!}
{!! Form::close() !!}
@stop
