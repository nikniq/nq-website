@extends('errors::minimal')
@section('code', '419')
@section('title', 'Page expired')
@section('message', 'The form sat open too long. Go back, refresh and try again — nothing was lost on our side.')
@section('actions')<a class="btn ghost" href="javascript:history.back()">Go back</a>@endsection
