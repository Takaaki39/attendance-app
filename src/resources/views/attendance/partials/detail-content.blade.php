<div class="detail-card">
    <table class="detail-table">
        <tr>
            <th>名前</th>
            <td>
                <div class="name">{{ $user->name }}</div>
                <input type="hidden" name="user_id" value="{{ $user->id }}">
            </td>
        </tr>
        <tr>
            <th>日付</th>
            <td>
                <div class="date-row">
                    <input name="year" type="text" value="{{ old('year', $attendance->date->format('Y年')) }}">
                    <input name="day" type="text" value="{{ old('day', $attendance->date->isoFormat('M月D日')) }}">
                </div>
                @error('year')
                <div class="error">{{ $message }}</div>
                @enderror
                @error('day')
                <div class="error">{{ $message }}</div>
                @enderror
            </td>
        </tr>
        <tr>
            <th>出勤・退勤</th>
            <td>
                <div>
                    @if($attendance->id)
                    <input type="hidden" name="attendance_id" value="{{ $attendance->id }}">
                    @endif

                    <input name="start_time" type="text" value="{{ old('start_time', $attendance->start_time?->format('H:i')) }}">
                    <span class="wave">〜</span>
                    <input name="end_time" type="text" value="{{ old('end_time', $attendance->end_time?->format('H:i')) }}">
                </div>
                @error('start_time')
                <div class="error">{{ $message }}</div>
                @enderror
                @error('end_time')
                <div class="error">{{ $message }}</div>
                @enderror
            </td>
        </tr>
        @foreach($attendance->rests as $index => $rest)
        <tr>
            <th>休憩{{ $loop->iteration > 1 ? $loop->iteration : '' }}</th>
            <td>
                <div>
                    <input name="rest_id[]" type="hidden" value="{{ $rest->id }}">
                    <input name="rest_start_time[{{ $index }}]" type="text" value="{{ old('rest_start_time[$index]', $rest->start_time?->format('H:i')) }}">
                    <span class="wave">〜</span>
                    <input name="rest_end_time[{{ $index }}]" type="text" value="{{ old('rest_end_time[$index]',$rest->end_time?->format('H:i')) }}">
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
        @php $newIndex = $attendance->rests->count(); @endphp
        <tr>
            <th>休憩{{ $newIndex + 1 > 1 ? $newIndex + 1 : '' }}</th>
            <td>
                <div>
                    <input name="new_rest_start_time" type="text" value="{{ old('new_rest_start_time') }}">
                    <span class="wave">〜</span>
                    <input name="new_rest_end_time" type="text" value="{{ old('new_rest_end_time') }}">
                </div>
                @error("new_rest_start_time") <div class="error">{{ $message }}</div> @enderror
                @error("new_rest_end_time") <div class="error">{{ $message }}</div> @enderror
            </td>
        </tr>
        <tr>
            <th>備考</th>
            <td>
                <div><textarea class="request-notes" name="notes">{{ old('notes', $attendance->notes ?? '') }}</textarea></div>
                @error('notes')
                <div class="error">{{ $message }}</div>
                @enderror
            </td>
        </tr>
    </table>
</div>

<div class="btn-wrapper">
    <button class="btn-submit">修正</button>
</div>