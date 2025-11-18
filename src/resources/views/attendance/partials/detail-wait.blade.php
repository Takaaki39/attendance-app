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
                    <span class="date-left">{{ $requestData->start_time->format("Y年") }}</span>
                    <span class="date-right">{{ $requestData->start_time->isoFormat('M月D日') }}</span>
                </div>
            </td>
        </tr>
        <tr>
            <th>出勤・退勤</th>
            <td>
                <div>
                    <span class="request-label">{{$requestData->start_time?->format('H:i')}}</span>
                    <span class="wave">〜</span>
                    <span class="request-label">{{$requestData->end_time?->format('H:i')}}</span>
                </div>
                @error('start_time')
                <div class="error">{{ $message }}</div>
                @enderror
                @error('end_time')
                <div class="error">{{ $message }}</div>
                @enderror
            </td>
        </tr>
        @foreach($requestData->restRequests as $index => $rest)
        <tr>
            <th>休憩{{ $loop->iteration > 1 ? $loop->iteration : '' }}</th>
            <td>
                <div>
                    <span class="request-label">
                        {{$rest?->start_time?->format('H:i')}}
                    </span>
                    <span class="wave">〜</span>
                    <span class="request-label">
                        {{$rest?->end_time?->format('H:i')}}
                    </span>
                </div>
            </td>
        </tr>
        @endforeach

        <tr>
            <th>備考</th>
            <td>
                <span class="request-notes noborder">{{$requestData->notes}}</span>
            </td>
        </tr>
    </table>
</div>

@if($requestData->state == 1)
<div class="btn-wrapper">
    <p class="error">※承認待ちのため修正はできません。</p>
</div>
@else
<div class="btn-wrapper">
    <button type="submit" class="btn-approved">承認済み</button>
</div>
@endif