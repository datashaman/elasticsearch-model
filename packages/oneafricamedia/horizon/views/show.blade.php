@extends('layout')

@section('content')
<table class="pure-table">
    <tr>
        <th align="right">ID</th><td>{{ $listing->id }}</td>
    </tr>

    @foreach($type['properties'] as $property)
    <tr>
    <?php $id = $property['id']; ?>
    <th align="right">{{ $property['title'] }}</th><td>{{ $listing->$id }}</td>
    </tr>
    @endforeach
</table>
@stop
