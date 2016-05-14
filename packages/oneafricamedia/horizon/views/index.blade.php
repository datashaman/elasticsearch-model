@extends('crud')

@section('crud')
@if(empty($listings))
No listings
@else
<table class="pure-table">
    <thead>
        <tr>
            <th>ID</th>
            @foreach($type['properties'] as $property)
            <th>{{ $property['title'] }}</th>
            @endforeach
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
    @foreach($listings['hits']['hits'] as $hit)
    <?php $listing = $hit['_source']; ?>
    <tr data-id="{{ $listing['id'] }}">
        <td>{!! Html::linkRoute('listing.edit', $listing['id'], [ 'id' => $listing['id'] ]) !!}</td>
        @foreach($type['properties'] as $property)
            <th>{{ $listing['properties'][$property['id']] }}</th>
        @endforeach
        <td>
            {!! Form::open([ 'route' => [ 'listing.destroy', $listing['id']], 'method' => 'DELETE' ]) !!}
            {!! Form::submit('Delete', [ 'class' => 'pure-button' ]) !!}
            {!! Form::close() !!}
        </td>
    </tr>
    @endforeach
    </tbody>
</table>
@endif
@stop
