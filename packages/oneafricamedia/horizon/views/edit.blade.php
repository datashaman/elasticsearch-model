@extends('crud')

@section('crud')
{!! Form::model($listing, [ 'method' => 'PUT', 'route' => [ 'listing.update', 'id' => $listing->id ], 'class' => 'pure-form pure-form-stacked' ]) !!}
{!! Form::hidden('type', $listing->type['id']) !!}

@include('horizon::_form')

{!! Form::submit('Save', [ 'class' => 'pure-button pure-button-primary' ]) !!}
{!! Form::close() !!}
@stop
