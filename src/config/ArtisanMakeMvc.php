<?php


return [
    "template" => "default", //default, bootstrap4  
    "extends" => "@extends('layouts.app')",
    "section" => "@section('content')",
    "endsection" => "@endsection",
    "add_route" => true,
    //new
    "created_at" => false, //include 'created_at' field or not
    "updated_at" => false, //include 'updated_at' field or not

];
