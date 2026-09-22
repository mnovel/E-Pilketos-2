@extends('errors.layout')

@section('code', '429')
@section('title', 'Terlalu Banyak Permintaan')
@section('message', 'Anda melakukan terlalu banyak permintaan dalam waktu singkat. Silakan tunggu beberapa saat dan coba lagi.')

@section('icon', 'bi-hourglass-split')
@section('icon-color', '#cc9a06')
@section('icon-bg', '#fff3cd')

@section('actions')
    <a href="javascript:location.reload()" class="btn-error btn-primary-error">
        <i class="bi bi-arrow-clockwise"></i> Coba Lagi
    </a>
    <a href="{{ url('/') }}" class="btn-error btn-outline-error">
        <i class="bi bi-house"></i> Halaman Utama
    </a>
@endsection
