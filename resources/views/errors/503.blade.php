@extends('errors::minimal')
@section('code', '503')
@section('title', 'Back shortly')
@section('message', 'NikniQ is being updated. This usually takes under a minute.')
@section('actions')<a class="btn ghost" href="javascript:location.reload()">Refresh</a>@endsection
