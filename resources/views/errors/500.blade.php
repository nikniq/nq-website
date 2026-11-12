@extends('errors::minimal')
@section('code', '500')
@section('title', 'Something broke')
@section('message', 'Our fault, not yours. Please try again in a moment.')
@section('actions')<a class="btn ghost" href="javascript:location.reload()">Try again</a>@endsection
