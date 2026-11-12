@extends('errors::minimal')
@section('code', '503')
@section('title', __('Back shortly'))
@section('message', __('NikniQ is being updated. This usually takes under a minute.'))
@section('actions')<a class="btn ghost" href="javascript:location.reload()">{{ __('Refresh') }}</a>@endsection
