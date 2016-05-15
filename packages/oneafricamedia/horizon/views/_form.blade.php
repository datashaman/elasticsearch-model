<fieldset>

@foreach($type['properties'] as $property)
<?php $id = $property['id']; ?>
<?php $type = $property['type']; ?>
<?php $properties = array_get($listing, 'properties', []); ?>
{!! Form::label($id, $property['title']) !!}
{!! Form::$type($id, Input::old($id, array_key_exists($id, $properties) ? $properties[$id] : null), [
    'class' => 'form-control',
    'required' => $property['required'] ? true : null,
    'placeholder' => $property['placeholder'],
]) !!}
@endforeach

</fieldset>
