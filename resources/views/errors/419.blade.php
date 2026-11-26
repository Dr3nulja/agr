@extends('errors.layout')

@section('code', '419')
@section('title', 'Seanss aegus')
@section('message', 'Leht oli liiga kaua avatud ja turvakood aegus. Värskenda lehte ja saada vorm uuesti — sisestatud andmed tuleb kahjuks uuesti täita.')
@section('actions')
    <button type="button" onclick="history.back()">← Tagasi vormi juurde</button>
    <a href="/login" class="primary">Logi sisse</a>
@endsection
