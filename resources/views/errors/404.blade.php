@extends('errors.layout')

@section('code', '404')
@section('title', 'Halaman Tidak Ditemukan')
@section('message', 'Halaman yang Anda cari tidak ditemukan atau sudah dipindahkan. Silakan periksa kembali URL atau kembali ke halaman utama.')

@section('icon', 'bi-search')
@section('icon-color', '#0d6efd')
@section('icon-bg', '#cfe2ff')

@section('actions')
    <a href="{{ url('/') }}" class="btn-error btn-primary-error">
        <i class="bi bi-house"></i> Halaman Utama
    </a>
    <a href="{{ url()->previous() }}" class="btn-error btn-outline-error">
        <i class="bi bi-arrow-left"></i> Kembali
    </a>
@endsection
