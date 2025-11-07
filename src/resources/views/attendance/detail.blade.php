@extends('layout.app')
@extends('layout.header')

@section('css')
<link rel="stylesheet" href="{{asset('css/attendance/detail.css')}}">
@endsection

@section('content')
<div class="attendance-detail-container">
    <div class="content-wrapper">
        <h2 class="page-title">勤怠詳細</h2>
        @if ($errors->any())
        <div class="error-all">
            <ul>
                @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif
        <form action="{{ route('attendance.request') }}" method="post" class="login-form" novalidate>
            @csrf
            <div class="detail-card">
                <table class="detail-table">
                    <tr>
                        <th>名前</th>
                        <td>
                            <div class="name">{{ $user->name }}</div>
                        </td>
                    </tr>
                    <tr>
                        <th>日付</th>
                        <td>
                            <div class="date-row">
                                <span class="date-left">{{ $attendance->start_time->format("Y年") }}</span>
                                <span class="date-right">{{ $attendance->start_time->isoFormat('M月D日') }}</span>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <th>出勤・退勤</th>
                        <td>
                            <div>
                                <input name="attendance_id" type="hidden" value="{{$attendance->id}}">

                                @if($requestData == null || $requestData->state != 1)
                                <input name="start_time" type="text" value="{{$attendance->start_time?->format('H:i')}}">
                                <span class="wave">〜</span>
                                <input name="end_time" type="text" value="{{$attendance->end_time?->format('H:i')}}">
                                @else
                                <span class="request-label">{{$requestData->start_time?->format('H:i')}}</span>
                                <span class="wave">〜</span>
                                <span class="request-label">{{$requestData->end_time?->format('H:i')}}</span>
                                @endif
                            </div>
                            @error('start_time')
                            <div class="error">{{ $message }}</div>
                            @enderror
                            @error('end_time')
                            <div class="error">{{ $message }}</div>
                            @enderror
                        </td>
                    </tr>
                    @if($requestData == null || $requestData->state != 1)
                    @foreach($attendance->rests as $index => $rest)
                    <tr>
                        <th>休憩{{ $loop->iteration > 1 ? $loop->iteration : '' }}</th>
                        <td>
                            <div>
                                <input name="rest_id[]" type="hidden" value="{{ $rest->id }}">
                                <input name="rest_start_time[{{ $index }}]" type="text" value="{{ $rest->start_time?->format('H:i') }}">
                                <span class="wave">〜</span>
                                <input name="rest_end_time[{{ $index }}]" type="text" value="{{ $rest->end_time?->format('H:i') }}">
                            </div>
                            @error("rest_start_time.$index")
                            <div class="error">{{ $message }}</div>
                            @enderror
                            @error("rest_end_time.$index")
                            <div class="error">{{ $message }}</div>
                            @enderror
                        </td>
                    </tr>
                    @endforeach
                    @else
                    @foreach($attendance->rests as $index => $rest)
                    <tr>
                        <th>休憩{{ $loop->iteration > 1 ? $loop->iteration : '' }}</th>
                        <td>
                            <div>
                                <span class="request-label">
                                    {{$requestData->restRequests[$loop->index]?->start_time?->format('H:i')}}
                                </span>
                                <span class="wave">〜</span>
                                <span class="request-label">
                                    {{$requestData->restRequests[$loop->index]?->end_time?->format('H:i')}}
                                </span>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                    @endif

                    @if($requestData == null || $requestData->state != 1)
                    @php $newIndex = $attendance->rests->count(); @endphp
                    <tr>
                        <th>休憩{{ $newIndex + 1 > 1 ? $newIndex + 1 : '' }}</th>
                        <td>
                            <div>
                                <input name="new_rest_start_time" type="text" value="">
                                <span class="wave">〜</span>
                                <input name="new_rest_end_time" type="text" value="">
                            </div>
                            @error("new_rest_start_time") <div class="error">{{ $message }}</div> @enderror
                            @error("new_rest_end_time") <div class="error">{{ $message }}</div> @enderror
                        </td>
                    </tr>
                    @endif
                    <tr>
                        <th>備考</th>
                        <td>
                            @if($requestData == null || $requestData->state != 1)
                            <div><textarea name="notes"></textarea></div>
                            @error('notes')
                            <div class="error">{{ $message }}</div>
                            @enderror
                            @else
                            <span class="request-label">{{$requestData->notes}}</span>
                            @endif
                        </td>
                    </tr>
                </table>
            </div>

            <div class="btn-wrapper">
                @if($requestData == null || $requestData->state != 1)
                <button class="btn-submit">修正</button>
                @else
                <p class="error">※承認待ちのため修正はできません。</p>
                @endif
            </div>
        </form>
    </div>
</div>
@endsection