<?php

namespace App\Http\Requests;

use Carbon\Carbon;
use Exception;
use Illuminate\Foundation\Http\FormRequest;

class ApplicationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $timeRegex = 'regex:/^(?:[0-9]|[01][0-9]|2[0-3]):[0-5][0-9]$/';

        return [
            // 日にち
            'year' => ['required'],
            'day' => ['required'],

            // 出勤・退勤
            'start_time' => ['nullable', $timeRegex],
            'end_time'   => ['nullable', $timeRegex],

            // 休憩（配列）
            'rest_start_time'   => ['array'],
            'rest_start_time.*' => [$timeRegex],
            'rest_end_time'     => ['array'],
            'rest_end_time.*'   => [$timeRegex],

            // 新規休憩（未入力OK）
            'new_rest_start_time' => ['nullable', $timeRegex],
            'new_rest_end_time'   => ['nullable', $timeRegex],

            // 備考
            'notes' => ['required', 'string'],
        ];
    }

    public function messages()
    {
        return [
            // 日にち
            'year.required' => '年は必須です',
            'day.required' => '日にちは必須です',

            // 出勤・退勤
            'start_time.regex' => '出勤時間は時間形式で入力してください',
            'end_time.regex' => '退勤時間は時間形式で入力してください',

            // 休憩（配列）
            'rest_start_time.*.regex' => '休憩開始時間は時間形式で入力してください',
            'rest_end_time.*.regex' => '休憩終了時間は時間形式で入力してください',

            // 新規休憩（）
            'new_rest_start_time.regex' => '休憩開始時間は時間形式で入力してください',
            'new_rest_end_time.regex' => '休憩終了時間は時間形式で入力してください',

            // 備考
            'notes.required' => '備考を記入してください',
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {

            try {
                $start = Carbon::parse($this->start_time);
                $end   = Carbon::parse($this->end_time);
            } catch (Exception $e) {
                return; // パース不可能なら他のエラーで引っかかっているので無視
            }

            if ($this->end_time != null && $this->start_time == null) {
                $validator->errors()->add('start_time', '出勤時間が不適切な値です');
            }

            // ===== 出勤・退勤 =====
            if ($start->gte($end)) {
                $validator->errors()->add('start_time', '出勤時間もしくは退勤時間が不適切な値です');
            }

            // ===== 休憩(既存休憩) =====
            $restStarts = $this->input('rest_start_time', []);
            $restEnds   = $this->input('rest_end_time', []);

            foreach ($restStarts as $i => $restStart) {
                try {
                    $rStart = Carbon::parse($restStart);
                    $rEnd   = isset($restEnds[$i]) ? Carbon::parse($restEnds[$i]) : null;
                } catch (\Exception $e) {
                    continue;
                }
                if ($rEnd != null && $rStart == null) {
                    $validator->errors()->add("rest_start_time.$i", '休憩時間が不適切な値です');
                }

                // 休憩開始時間が出勤時間より前 or 退勤時間より後
                if ($rStart->lt($start) || $rStart->gt($end)) {
                    $validator->errors()->add("rest_start_time.$i", '休憩時間が不適切な値です');
                }
                // 休憩終了時間が退勤時間より後
                if ($rEnd && $rEnd->gt($end)) {
                    $validator->errors()->add("rest_end_time.$i", '休憩時間もしくは退勤時間が不適切な値です');
                }
                // 開始 < 終了
                if ($rEnd && $rEnd->lt($rStart)) {
                    $validator->errors()->add("rest_start_time.$i", '休憩時間が不適切な値です');
                }
            }

            // ===== 休憩（追加分） =====
            $newStart = $this->input('new_rest_start_time');
            $newEnd   = $this->input('new_rest_end_time');

            if ($newStart || $newEnd) {
                try {
                    $nStart = $newStart ? Carbon::parse($newStart) : null;
                    $nEnd   = $newEnd   ? Carbon::parse($newEnd)   : null;
                } catch (\Exception $e) {
                    return;
                }
                if ($nEnd != null && $nStart == null) {
                    $validator->errors()->add('new_rest_start_time', '休憩時間が不適切な値です');
                }

                // 休憩開始時間が出勤時間より前 or 退勤時間より後
                if ($nStart && ($nStart->lt($start) || $nStart->gt($end))) {
                    $validator->errors()->add('new_rest_start_time', '休憩時間が不適切な値です');
                }
                // 休憩終了時間が退勤時間より後
                if ($nEnd && $nEnd->gt($end)) {
                    $validator->errors()->add('new_rest_end_time', '休憩時間もしくは退勤時間が不適切な値です');
                }
                // 開始 < 終了
                if ($nStart && $nEnd && $nEnd->lt($nStart)) {
                    $validator->errors()->add('new_rest_start_time', '休憩時間が不適切な値です');
                }
            }
        });
    }
}
