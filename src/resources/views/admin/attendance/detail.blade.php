@extends('layout.app')
@extends('layout.header')

@section('css')
<link rel="stylesheet" href="{{asset('css/attendance/detail.css')}}">
@endsection

@section('content')
<div class="attendance-detail-container">
    <div class="content-wrapper">
        <h2 class="page-title">勤怠詳細</h2>

        <div class="detail-card">
            <table class="detail-table">
                <tr>
                    <th>名前</th>
                    <td>
                        <div>西　怜奈</div>
                    </td>
                </tr>
                <tr>
                    <th>日付</th>
                    <td>
                        <div class="date-row">
                            <span class="date-left">2023年</span>
                            <span class="date-right">6月1日</span>
                        </div>
                    </td>
                </tr>
                <tr>
                    <th>出勤・退勤</th>
                    <td>
                        <div>
                            <input type="text" value="09:00">
                            <span class="wave">〜</span>
                            <input type="text" value="20:00">
                        </div>
                    </td>
                </tr>
                <tr>
                    <th>休憩</th>
                    <td>
                        <div>
                            <input type="text" value="12:00">
                            <span class="wave">〜</span>
                            <input type="text" value="13:00">
                        </div>
                    </td>
                </tr>
                <tr>
                    <th>休憩2</th>
                    <td>
                        <div>
                            <input type="text">
                            <span class="wave">〜</span>
                            <input type="text">
                        </div>
                    </td>
                </tr>
                <tr>
                    <th>備考</th>
                    <td>
                        <div><textarea></textarea></div>
                    </td>
                </tr>
            </table>
        </div>

        <div class="btn-wrapper">
            <button class="btn-submit">修正</button>
        </div>
    </div>
</div>
@endsection