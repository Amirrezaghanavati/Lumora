<?php

namespace App\Http\Controllers;

class HomeController extends Controller
{

    public function index()
    {
        echo 'index';
    }
    public function create()
    {
        echo 'create';
    }
    public function store()
    {
        echo 'store';
    }
    public function edit($id)
    {
        echo 'edit';
    }
    public function update($id)
    {
        echo 'update';
    }
    public function destroy($id)
    {
        echo 'destroy';
    }

}