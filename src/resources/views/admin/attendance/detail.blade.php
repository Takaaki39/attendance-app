@extends('layout.app')
@extends('layout.header')

@section('css')
<link rel="stylesheet" href="{{asset('css/attendance/detail.css')}}">
@endsection

@section('content')
<div class="attendance-detail-container">
    <div class="content-wrapper">
        <h1 class="page-title">勤怠詳細</h1>
        @if($requestData == null)
        <form action="{{ route('admin.attendance.request') }}" method="post" novalidate>
            @csrf
            @include('attendance.partials.detail-content')
        </form>
        @else
        @include('attendance.partials.detail-approval')
        @endif
    </div>
</div>
@endsection