@extends('layout')

@section('content')
{!! Form::open([ 'route' => [ 'listing.store' ], 'class' => 'pure-form pure-form-stacked' ]) !!}
{!! Form::hidden('schema', $schema['id']) !!}

<fieldset>

@foreach($schema['properties'] as $property)
<?php $type = $property['type']; ?>
{!! Form::label($property['id'], $property['title']) !!}
{!! Form::$type($property['id'], null, [ 'class' => 'form-control', 'required' => $property['required'] ? true : null ]) !!}
@endforeach

{!! Form::submit('Save', [ 'class' => 'pure-button pure-button-primary' ]) !!}
</fieldset>

{!! Form::close() !!}
@stop
