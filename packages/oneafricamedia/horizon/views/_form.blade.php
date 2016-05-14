<fieldset>

@foreach($listing->type['properties'] as $property)
<?php $id = $property['id']; ?>
<?php $type = $property['type']; ?>
<?php $properties = $listing->properties; ?>
{!! Form::label($id, $property['title']) !!}
{!! Form::$type($id, Input::old($id, property_exists($properties, $id) ? $properties->$id : null), [
    'class' => 'form-control',
    'required' => $property['required'] ? true : null,
    'placeholder' => $property['placeholder'],
]) !!}
@endforeach

</fieldset>
