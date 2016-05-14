{!! Form::open([ 'route' => [ 'listing.store' ]]) !!}
{!! Form::hidden('schema', $schema['id']) !!}

@foreach($schema['properties'] as $property)
<?php $type = $property['type']; ?>
{!! Form::label($property['id'], $property['title']) !!}
{!! Form::$type($property['id'], null, [ 'class' => 'form-control', 'required' => $property['required'] ? true : null ]) !!}
@endforeach

{!! Form::submit('Save') !!}
{!! Form::close() !!}
