<?php

namespace App\Http\Controllers;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use Oneafricamedia\Horizon\ParserContract;


class ListingController extends Controller
{
    /**
     * Display a listing of the listings.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new listing.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(ParserContract $parser)
    {
        $schema = $parser->parseSchema('generic');
        return view('horizon::create', compact('schema'));
    }

    /**
     * Store a newly created listing in storage.
     *
     * @param  \App\Requests\ListingRequest  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Requests\ListingRequest $request)
    {
        return $request->all();
    }

    /**
     * Display the specified listing.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified listing.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified listing in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified listing from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
