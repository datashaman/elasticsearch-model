<?php

namespace App\Http\Controllers;

use App\Listing;
use App\Http\Requests;
use App\Http\Controllers\Controller;
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
        $listing = new Listing;
        $listing->type = 'generic';
        return view('horizon::create', compact('listing'));
    }

    protected function _update(Requests\ListingRequest $request, Listing $listing)
    {
        $listing->type = $request->input('type');
        $properties = [];
        foreach ($listing->type['properties'] as $property) {
            $id = $property['id'];
            $properties[$id] = $request->input($id);
        }
        $listing->properties = $properties;
        $saved =$listing->save();

        if ($saved) {
            $this->indexer->index($listing);
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
        $result = $this->_update($request, $listing);
        return redirect()->route('listing.index');
    }

    /**
     * Display the specified listing.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $listing = Listing::findOrFail($id);
        return view('horizon::show', compact('listing'));
    }

    /**
     * Show the form for editing the specified listing.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $listing = Listing::findOrFail($id);
        return view('horizon::edit', compact('listing'));
    }

    /**
     * Update the specified listing in storage.
     *
     * @param  \App\Http\Requests\ListingRequest  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Requests\ListingRequest $request, $id)
    {
        $listing = Listing::findOrFail($id);
        $result = $this->_update($request, $listing);
        return redirect()->route('listing.index');
    }

    /**
     * Remove the specified listing from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $listing = Listing::findOrFail($id);
        $deleted = $listing->delete();
        if ($deleted) {
            $this->indexer->delete($id);
            sleep(1);
        }
        return redirect()->route('listing.index');
    }
}
