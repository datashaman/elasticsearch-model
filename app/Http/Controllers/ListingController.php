<?php

namespace App\Http\Controllers;

use App\Listing;
use App\Http\Requests;
use App\Http\Controllers\Controller;
use Guzzle\Ring\Future\FutureArray;
use Oneafricamedia\Horizon\IndexerContract;
use Oneafricamedia\Horizon\ParserContract;


class ListingController extends Controller
{

    public function __construct(IndexerContract $indexer, ParserContract $parser)
    {
        $this->indexer = $indexer;
        $this->parser = $parser;
    }

    /**
     * Display a listing of the listings.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $type = $this->parser->parseSchema('generic');
        $listings = $this->indexer->search();
        return view('horizon::index', compact('listings', 'type'));
    }

    /**
     * Show the form for creating a new listing.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $type = $this->parser->parseSchema('generic');
        $new = new Listing;
        $listing = $new->toArray();
        return view('horizon::create', compact('listing', 'type'));
    }

    protected function _update(Requests\ListingRequest $request, Listing $listing)
    {
        $listing->type = $request['type'];
        $type = $this->parser->parseSchema($listing->type);

        $properties = [];
        foreach ($type['properties'] as $property) {
            $id = $property['id'];
            $properties[$id] = $request[$id];
        }
        $listing->properties = $properties;
        $saved = $listing->save();

        if ($saved) {
            $future = $this->indexer->index($listing);
            $future->wait();
            sleep(1);
        }

        return $saved;
    }

    /**
     * Store a newly created listing in storage.
     *
     * @param  \App\Http\Requests\ListingRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Requests\ListingRequest $request)
    {
        $listing = new Listing;
        $listing->status = 'new';
        $updated = $this->_update($request, $listing);
        return redirect()->route('listing.index');
    }

    /**
     * Display the specified listing.
     *
     * @param \App\Listing $listing
     * @return \Illuminate\Http\Response
     */
    public function show($listing)
    {
        return view('horizon::show', compact('listing'));
    }

    /**
     * Show the form for editing the specified listing.
     *
     * @param \App\Listing $listing
     * @return \Illuminate\Http\Response
     */
    public function edit($listing)
    {
        $type = $this->parser->parseSchema('generic');
        return view('horizon::edit', compact('listing', 'type'));
    }

    /**
     * Update the specified listing in storage.
     *
     * @param  \App\Http\Requests\ListingRequest  $request
     * @param  \App\Listing  $listing
     * @return \Illuminate\Http\Response
     */
    public function update(Requests\ListingRequest $request, $listing)
    {
        $listing = Listing::findOrFail($listing['id']);
        $updated = $this->_update($request, $listing);
        return redirect()->route('listing.index');
    }

    /**
     * Remove the specified listing from storage.
     *
     * @param  \App\Listing  $listing
     * @return \Illuminate\Http\Response
     */
    public function destroy($listing)
    {
        $id = $listing['id'];
        $listing = Listing::find($id);

        if (!is_null($listing)) {
            $deleted = $listing->delete();
        }

        if (is_null($listing) || $deleted) {
            $future = $this->indexer->delete($id);
            $future->wait();
        }
        return redirect()->route('listing.index');
    }
}
