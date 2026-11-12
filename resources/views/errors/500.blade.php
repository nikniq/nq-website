@extends('errors::minimal')
@section('code', '500')
@section('title', __('Something broke'))
@section('message', __('Our fault, not yours. Please try again in a moment.'))
@section('actions')<a class="btn ghost" href="javascript:location.reload()">{{ __('Try again') }}</a>@endsection
