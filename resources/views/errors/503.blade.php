@extends('errors.layout')

@section('code', '503')
@section('title', 'Sedang Dalam Perbaikan')
@section('message', 'Sistem sedang dalam pemeliharaan rutin. Kami akan segera kembali. Terima kasih atas kesabaran Anda.')

@section('icon', 'bi-tools')
@section('icon-color', '#0d6efd')
@section('icon-bg', '#cfe2ff')

@section('actions')
    <a href="javascript:location.reload()" class="btn-error btn-primary-error">
        <i class="bi bi-arrow-clockwise"></i> Refresh
    </a>
    <a href="{{ url('/') }}" class="btn-error btn-outline-error">
        <i class="bi bi-house"></i> Halaman Utama
    </a>
@endsection
